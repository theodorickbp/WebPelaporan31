<?php

namespace App\Http\Controllers\Toolman;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Area Toolman: hanya laporan yang ditugaskan Admin kepadanya. */
class ReportController extends Controller
{
    public function home(Request $request): View
    {
        $mine = $request->user()->assignedReports();

        return view('toolman.home', [
            'counts' => (clone $mine)->selectRaw('status, count(*) as total')->groupBy('status')
                ->pluck('total', 'status')->map(fn ($n) => (int) $n)->all(),
            'todo' => (clone $mine)->with(['user', 'photos'])
                ->where('status', ReportStatus::InRepair->value)->oldest('updated_at')->get(),
            'waiting' => (clone $mine)->with(['user', 'photos'])
                ->where('status', ReportStatus::Review->value)->latest('updated_at')->get(),
        ]);
    }

    public function index(Request $request): View
    {
        $status = $request->query('status', ReportStatus::InRepair->value);
        $allowed = [ReportStatus::InRepair->value, ReportStatus::Review->value, ReportStatus::Done->value];

        $query = $request->user()->assignedReports()->with(['user', 'photos'])->search($request->query('q'));

        if (in_array($status, $allowed, true)) {
            $query->where('status', $status);
        } else {
            $status = 'all';
        }

        $query->latest('updated_at');

        $counts = $request->user()->assignedReports()->selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status')->map(fn ($n) => (int) $n)->all();

        return view('toolman.reports.index', [
            'reports' => $query->paginate(12)->withQueryString(),
            'counts' => $counts,
            'status' => $status,
            'q' => (string) $request->query('q', ''),
        ]);
    }

    public function show(Report $report): View
    {
        Gate::authorize('view', $report);

        $report->load(['user', 'photos', 'toolman', 'logs.user', 'logs.toolman']);

        return view('toolman.reports.show', ['report' => $report]);
    }

    /** Toolman mengirim hasil pengerjaan ke Admin. */
    public function submit(Request $request, Report $report, ReportService $service): RedirectResponse
    {
        Gate::authorize('view', $report);

        abort_unless(Gate::allows('work', $report), 403, 'Laporan ini tidak sedang dalam pengerjaanmu.');

        $data = $request->validate([
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'note.required' => 'Tulis hasil pengerjaan sebelum mengirim ke Admin.',
            'note.min' => 'Hasil pengerjaan minimal 5 karakter.',
        ]);

        $service->submitResult($report, $request->user(), $data['note']);

        return redirect()->route('toolman.reports.show', $report)
            ->with('toast', ReportStatus::Review->adminToast());
    }
}
