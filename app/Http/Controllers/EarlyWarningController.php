<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\EarlyWarningLog;
use App\Services\EarlyWarningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarlyWarningController extends Controller
{
    public function index(Request $request): View
    {
        $year = AcademicYear::where('is_active', true)->first();

        $query = EarlyWarningLog::with(['student.rombel', 'resolver'])
            ->latest('trigger_date');

        if ($request->has('type') && $request->input('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('resolved') && $request->input('resolved') !== '') {
            $query->where('is_resolved', $request->input('resolved') === '1');
        }

        $logs = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => EarlyWarningLog::count(),
            'unresolved' => EarlyWarningLog::where('is_resolved', false)->count(),
            'absence' => EarlyWarningLog::where('type', 'absence_streak')->where('is_resolved', false)->count(),
            'attendance' => EarlyWarningLog::where('type', 'attendance_rate')->where('is_resolved', false)->count(),
            'low_score' => EarlyWarningLog::where('type', 'low_score')->where('is_resolved', false)->count(),
        ];

        return view('ews.index', compact('logs', 'stats'));
    }

    public function resolve(EarlyWarningLog $earlyWarningLog): RedirectResponse
    {
        $earlyWarningLog->update([
            'is_resolved' => true,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
            'note' => request()->input('note', $earlyWarningLog->note),
        ]);

        return back()->with('success', 'Peringatan berhasil diselesaikan.');
    }

    public function runCheck(): RedirectResponse
    {
        $service = new EarlyWarningService;

        $service->runCheck(currentTenantId());

        return back()->with('success', 'Pemeriksaan EWS selesai. Silakan periksa log baru.');
    }
}
