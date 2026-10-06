<?php

namespace App\Http\Controllers\Student;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function home(Request $request): View
    {
        $reports = $request->user()->reports()->with('photos');

        return view('student.home', [
            'active' => (clone $reports)->whereIn('status', ReportStatus::openValues())->latest()->get(),
            'finished' => (clone $reports)->where('status', ReportStatus::Done->value)->latest('resolved_at')->limit(3)->get(),
        ]);
    }

    public function index(Request $request): View
    {
        $filter = $request->query('filter');
        $query = $request->user()->reports()->with('photos')->latest();

        if ($filter === 'open') {
            $query->whereIn('status', ReportStatus::openValues());
        } elseif ($filter === 'done') {
            $query->where('status', ReportStatus::Done->value);
        } elseif ($filter === 'declined') {
            $query->where('status', ReportStatus::Declined->value);
        } else {
            $filter = null;
        }

        return view('student.reports.index', [
            'reports' => $query->paginate(10)->withQueryString(),
            'filter' => $filter,
        ]);
    }

    public function create(): View
    {
        return view('student.reports.create', ['categories' => ReportCategory::cases()]);
    }

    public function store(StoreReportRequest $request, ReportService $service): RedirectResponse
    {
        $report = $service->create(
            $request->user(),
            $request->safe()->except('photos'),
            $request->file('photos', [])
        );

        return redirect()
            ->route('student.reports.show', $report)
            ->with('toast', 'Laporan terkirim. Toolman akan segera meninjaunya.');
    }

    public function show(Report $report): View
    {
        Gate::authorize('view', $report);

        $report->load(['photos', 'logs.user']);

        return view('student.reports.show', ['report' => $report]);
    }

    public function destroy(Report $report, ReportService $service): RedirectResponse
    {
        Gate::authorize('view', $report);

        if (! Gate::allows('delete', $report)) {
            return back()->with('toast', 'Laporan yang sudah ditinjau Toolman tidak bisa dibatalkan.');
        }

        $service->cancel($report);

        return redirect()->route('student.reports.index')->with('toast', 'Laporan dibatalkan.');
    }
}
