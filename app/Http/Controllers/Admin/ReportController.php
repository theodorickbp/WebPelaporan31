<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\Report;
use App\Models\User;
use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function home(ReportService $service): View
    {
        // Tanpa cron: cek "sambil lalu" di halaman yang paling sering dibuka Toolman.
        // Proses hapusnya sendiri di-debounce di dalam method ini (maks 1x/jam).
        $service->maybePurgePhotosPastRetention();

        return view('admin.home', [
            'counts' => self::statusCounts(),
            'pendingAccounts' => User::query()->where('account_status', AccountStatus::Pending->value)->count(),
            'queue' => Report::with(['user', 'photos', 'toolman'])
                ->where('status', ReportStatus::Submitted->value)->oldest()->limit(5)->get(),
            'review' => Report::with(['user', 'photos', 'toolman'])
                ->where('status', ReportStatus::Review->value)->oldest('updated_at')->limit(5)->get(),
            'working' => Report::with(['user', 'photos', 'toolman'])
                ->whereIn('status', [ReportStatus::Pending->value, ReportStatus::InRepair->value])
                ->latest('updated_at')->limit(5)->get(),
            'limits' => [
                'active' => (int) Report::whereIn('status', ReportStatus::activeValues())->count(),
                'max_active' => (int) config('sipasar.max_approved_at_once'),
                'max_queue' => (int) config('sipasar.max_queue'),
            ],
        ]);
    }

    public function index(Request $request): View
    {
        $status = $request->query('status', ReportStatus::Submitted->value);
        $category = $request->query('category');

        $query = Report::with(['user', 'photos', 'toolman'])->search($request->query('q'));

        if (ReportStatus::tryFrom((string) $status)) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }

        if (ReportCategory::tryFrom((string) $category)) {
            $query->where('category', $category);
        } else {
            $category = null;
        }

        // Antrean (baru/menunggu antrean): yang paling lama menunggu di atas. Lainnya: yang terbaru diubah di atas.
        in_array($status, [ReportStatus::Submitted->value, ReportStatus::Waiting->value], true)
            ? $query->oldest() : $query->latest('updated_at');

        return view('admin.reports.index', [
            'reports' => $query->paginate(12)->withQueryString(),
            'counts' => self::statusCounts(),
            'status' => $status,
            'category' => $category,
            'q' => (string) $request->query('q', ''),
            'categories' => ReportCategory::cases(),
        ]);
    }

    public function show(Report $report): View
    {
        $report->load(['user', 'photos', 'toolman', 'logs.user', 'logs.toolman']);

        return view('admin.reports.show', [
            'report' => $report,
            'toolmen' => User::where('role', UserRole::Toolman->value)->orderBy('name')->get(['id', 'name']),
            'workload' => Report::whereIn('status', [ReportStatus::InRepair->value, ReportStatus::Review->value])
                ->whereNotNull('toolman_id')
                ->selectRaw('toolman_id, count(*) as total')->groupBy('toolman_id')
                ->pluck('total', 'toolman_id')->all(),
            'activeCount' => (int) Report::whereIn('status', ReportStatus::activeValues())->count(),
            'maxActive' => (int) config('sipasar.max_approved_at_once'),
        ]);
    }

    /** Admin: setujui, tolak, selesaikan, atau kembalikan hasil ke Toolman. (Penugasan Toolman lewat assign().) */
    public function transition(Request $request, Report $report, ReportService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                ReportStatus::Pending->value, ReportStatus::Declined->value,
                ReportStatus::Done->value, ReportStatus::InRepair->value,
            ])],
            'note' => [
                Rule::requiredIf(in_array($request->input('status'), [
                    ReportStatus::Declined->value, ReportStatus::Done->value, ReportStatus::InRepair->value,
                ], true)),
                'nullable', 'string', 'max:500',
            ],
        ], [
            'status.in' => 'Aksi tidak dikenali.',
            'note.required' => $request->input('status') === ReportStatus::Declined->value
                ? 'Tulis alasan penolakan supaya siswa tahu.'
                : ($request->input('status') === ReportStatus::Done->value
                    ? 'Tulis informasi untuk siswa sebelum menyelesaikan laporan.'
                    : 'Tulis catatan revisi untuk Toolman.'),
        ]);

        $to = ReportStatus::from($data['status']);

        // Menuju "Dikerjakan" dari Disetujui hanya boleh lewat penugasan Toolman (assign).
        if ($to === ReportStatus::InRepair && $report->status !== ReportStatus::Review) {
            return back()->withErrors(['status' => 'Pilih Toolman untuk menugaskan laporan ini.']);
        }

        $service->transition($report, $to, $request->user(), $data['note'] ?? null);

        return redirect()->route('admin.reports.show', $report)->with('toast', $to->adminToast());
    }

    /** Admin memilih Toolman secara manual untuk laporan yang sudah disetujui. */
    public function assign(Request $request, Report $report, ReportService $service): RedirectResponse
    {
        $data = $request->validate([
            'toolman_id' => ['required', Rule::exists('users', 'id')->where('role', UserRole::Toolman->value)],
        ], [
            'toolman_id.required' => 'Pilih Toolman yang akan mengerjakan laporan ini.',
            'toolman_id.exists' => 'Toolman tidak valid.',
        ]);

        $toolman = User::findOrFail($data['toolman_id']);
        $service->assign($report, $toolman, $request->user());

        return redirect()->route('admin.reports.show', $report)
            ->with('toast', "Laporan ditugaskan ke {$toolman->name}.");
    }

    /**
     * Dipakai di Ringkasan dan Daftar laporan sekaligus, jadi di-cache singkat -
     * cukup untuk menghindari query berulang dalam waktu berdekatan, tapi tetap
     * terasa langsung karena di-hapus (Cache::forget) setiap ada laporan baru
     * atau status berubah, lihat ReportService.
     *
     * @return array<string, int>
     */
    public static function statusCounts(): array
    {
        return Cache::remember('sipasar:report-status-counts', 30, function () {
            return Report::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($n) => (int) $n)
                ->all();
        });
    }
}
