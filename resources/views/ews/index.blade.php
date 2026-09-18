@extends('layouts.app')

@section('title', 'Early Warning System')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-text">Early Warning System</h1>
        <p class="text-sm text-text-muted mt-1">Peringatan dini terhadap siswa yang berisiko.</p>
    </div>
    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']))
        <form method="POST" action="{{ route('ews.runCheck') }}" class="inline">
            @csrf
            <button type="submit" class="btn btn-accent">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Jalankan Pemeriksaan
            </button>
        </form>
    @endif
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted">Total</p>
        <p class="font-display text-2xl font-semibold text-text mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted">Belum Diselesaikan</p>
        <p class="font-display text-2xl font-semibold text-danger mt-1">{{ $stats['unresolved'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted">Absen Berturut</p>
        <p class="font-display text-2xl font-semibold text-text mt-1">{{ $stats['absence'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted">Presensi Rendah</p>
        <p class="font-display text-2xl font-semibold text-text mt-1">{{ $stats['attendance'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted">Nilai Rendah</p>
        <p class="font-display text-2xl font-semibold text-text mt-1">{{ $stats['low_score'] }}</p>
    </div>
</div>

@if($logs->isEmpty())
    <div class="card p-12 text-center">
        <p class="text-text-muted">Tidak ada peringatan aktif saat ini.</p>
    </div>
@else
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Siswa</th>
                        <th>Tipe</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr class="{{ $log->is_resolved ? 'opacity-60' : '' }}">
                            <td class="text-text-muted">{{ $log->trigger_date?->translatedFormat('d M Y') }}</td>
                            <td class="font-medium">{{ $log->student->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ match($log->type) {
                                    'absence_streak' => 'badge-danger',
                                    'attendance_rate' => 'badge-warning',
                                    'low_score' => 'badge-warning',
                                    default => '',
                                } }}">
                                    {{ match($log->type) {
                                        'absence_streak' => 'Absen Berturut',
                                        'attendance_rate' => 'Presensi Rendah',
                                        'low_score' => 'Nilai Rendah',
                                        default => ucfirst($log->type),
                                    } }}
                                </span>
                            </td>
                            <td class="text-text-muted max-w-sm truncate">{{ $log->description }}</td>
                            <td>
                                @if($log->is_resolved)
                                    <span class="badge badge-success">Selesai</span>
                                @else
                                    <span class="badge badge-danger">Aktif</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if(!$log->is_resolved && auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']))
                                    <form method="POST" action="{{ route('ews.resolve', $log) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="note" value="">
                                        <button type="submit" class="text-text-muted hover:text-accent text-sm transition-colors">Tandai Selesai</button>
                                    </form>
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