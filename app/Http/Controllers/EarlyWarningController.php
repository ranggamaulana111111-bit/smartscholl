<?php

namespace App\Http\Controllers;

use App\Models\EarlyWarningLog;
use App\Services\EarlyWarningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EarlyWarningController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        $scope = fn ($query) => $query->when(
            $user->hasRole('guru'),
            fn ($q) => $q->whereHas('student.rombel', fn ($rq) => $rq->where('homeroom_teacher_id', $user->id))
        );

        $logs = $scope(EarlyWarningLog::with(['student.rombel', 'resolver'])->latest('trigger_date'));

        if ($request->has('type') && $request->input('type')) {
            $logs->where('type', $request->input('type'));
        }

        if ($request->has('resolved') && $request->input('resolved') !== '') {
            $logs->where('is_resolved', $request->input('resolved') === '1');
        }

        $logs = $logs->paginate(20)->withQueryString();

        $stats = [
            'total' => $scope(EarlyWarningLog::query())->count(),
            'unresolved' => $scope(EarlyWarningLog::where('is_resolved', false))->count(),
            'absence' => $scope(EarlyWarningLog::where('type', 'absence_streak')->where('is_resolved', false))->count(),
            'attendance' => $scope(EarlyWarningLog::where('type', 'attendance_rate')->where('is_resolved', false))->count(),
            'low_score' => $scope(EarlyWarningLog::where('type', 'low_score')->where('is_resolved', false))->count(),
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
