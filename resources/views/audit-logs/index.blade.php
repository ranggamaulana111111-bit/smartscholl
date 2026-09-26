@extends('layouts.app')

@section('title', 'Audit Trail')
@section('content')
<x-page-head title="Audit Trail"
    description="Log aktivitas perubahan data nilai, absensi, dan master data."
    eyebrow="Konfigurasi">
</x-page-head>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bento-card bento-card--surface p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Total Aksi</p>
        <p class="font-display text-2xl font-semibold text-text mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bento-card bento-card--surface p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Ubah Nilai</p>
        <p class="font-display text-2xl font-semibold text-accent-deep mt-1">{{ $stats['grade_changes'] }}</p>
    </div>
    <div class="bento-card bento-card--surface p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Import/Export</p>
        <p class="font-display text-2xl font-semibold text-text mt-1">{{ $stats['imports'] }}</p>
    </div>
</div>

@if($logs->isEmpty())
    <div class="panel p-12 text-center">
        <span class="empty-state__icon" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </span>
        <p class="empty-state__title">Belum ada aktivitas tercatat</p>
        <p class="empty-state__hint">Riwayat perubahan akan muncul di sini.</p>
    </div>
@else
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Entitas</th>
                        <th>ID</th>
                        <th>Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="text-text-muted whitespace-nowrap">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td>
                            <td class="font-medium text-text">{{ $log->user->name ?? 'Sistem' }}</td>
                            <td><span class="badge">{{ $log->action }}</span></td>
                            <td class="text-text-muted">{{ $log->entity_type }}</td>
                            <td class="text-text-muted">{{ $log->entity_id }}</td>
                            <td class="text-text-muted max-w-sm truncate">
                                @if($log->new_values && $log->action !== 'update_grades')
                                    {{ Str::limit(json_encode($log->new_values, JSON_UNESCAPED_UNICODE), 60) }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-border">{{ $logs->links() }}</div>
    </div>
@endif
@endsection