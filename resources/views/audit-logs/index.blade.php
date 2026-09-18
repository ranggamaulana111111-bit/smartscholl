@extends('layouts.app')

@section('title', 'Audit Trail')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">Audit Trail</h1>
    <p class="text-sm text-text-muted mt-1">Log aktivitas perubahan data nilai, absensi, dan master data.</p>
</div>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Total Aksi</p>
        <p class="font-display text-2xl font-semibold text-text mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Ubah Nilai</p>
        <p class="font-display text-2xl font-semibold text-accent mt-1">{{ $stats['grade_changes'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Import/Export</p>
        <p class="font-display text-2xl font-semibold text-text mt-1">{{ $stats['imports'] }}</p>
    </div>
</div>

@if($logs->isEmpty())
    <div class="card p-12 text-center">
        <p class="text-text-muted">Belum ada aktivitas tercatat.</p>
    </div>
@else
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
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
                            <td class="font-medium">{{ $log->user->name ?? 'Sistem' }}</td>
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