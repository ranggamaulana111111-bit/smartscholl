<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        $logs = AuditLog::with('user')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total' => AuditLog::count(),
            'grade_changes' => AuditLog::where('action', 'update_grades')->count(),
            'imports' => AuditLog::whereIn('action', ['import_grades', 'export_grades'])->count(),
        ];

        return view('audit-logs.index', compact('logs', 'stats'));
    }
}
