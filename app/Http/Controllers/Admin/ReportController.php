<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\Report;
use App\Models\User;
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
            'pendingAccounts' => User::pending()->count(),
            'queue' => Report::with(['user', 'photos'])
                ->where('status', ReportStatus::Submitted->value)->oldest()->limit(5)->get(),
            'working' => Report::with(['user', 'photos'])
                ->whereIn('status', [ReportStatus::Pending->value, ReportStatus::InRepair->value])
                ->latest('updated_at')->limit(5)->get(),
        ]);
    }

    public function index(Request $request): View
    {
        $status = $request->query('status', ReportStatus::Submitted->value);
        $category = $request->query('category');

        $query = Report::with(['user', 'photos'])->search($request->query('q'));

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

        // Antrean baru: yang paling lama menunggu di atas. Lainnya: yang terbaru diubah di atas.
        $status === ReportStatus::Submitted->value ? $query->oldest() : $query->latest('updated_at');

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
        $report->load(['user', 'photos', 'logs.user']);

        return view('admin.reports.show', ['report' => $report]);
    }

    public function transition(Request $request, Report $report, ReportService $service): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ReportStatus::class)],
            'note' => [
                Rule::requiredIf($request->input('status') === ReportStatus::Declined->value),
                'nullable', 'string', 'max:500',
            ],
        ], [
            'note.required' => 'Tulis alasan penolakan supaya siswa tahu.',
        ]);

        $to = ReportStatus::from($data['status']);
        $service->transition($report, $to, $request->user(), $data['note'] ?? null);

        return redirect()->route('admin.reports.show', $report)->with('toast', $to->adminToast());
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
