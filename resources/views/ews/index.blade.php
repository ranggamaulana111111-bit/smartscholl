@extends('layouts.app')

@section('title', 'Early Warning System')
@section('content')
<x-page-head title="Early Warning System"
    description="Peringatan dini terhadap siswa yang berisiko."
    eyebrow="Perhatian">
    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']))
        <x-slot name="actions">
            <form method="POST" action="{{ route('ews.runCheck') }}" class="inline">
                @csrf
                <button type="submit" class="btn btn-accent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Jalankan Pemeriksaan
                </button>
            </form>
        </x-slot>
    @endif
</x-page-head>

<div class="bento mb-6">
    @foreach([
        ['label' => 'Total', 'value' => $stats['total']],
        ['label' => 'Belum Diselesaikan', 'value' => $stats['unresolved'], 'class' => 'text-danger'],
        ['label' => 'Absen Berturut', 'value' => $stats['absence']],
        ['label' => 'Presensi Rendah', 'value' => $stats['attendance']],
        ['label' => 'Nilai Rendah', 'value' => $stats['low_score']],
    ] as $i => $stat)
        <div class="bento-card lg:col-span-{{ $i === 0 ? '4' : '2' }}">
            <div class="bento-card__body">
                <p class="metric__label">{{ $stat['label'] }}</p>
                <p class="metric__value mt-1 {{ $stat['class'] ?? '' }}">{{ $stat['value'] }}</p>
            </div>
        </div>
    @endforeach
</div>

@if($logs->isEmpty())
    <div class="panel p-12 text-center">
        <span class="empty-state__icon" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </span>
        <p class="empty-state__title">Tidak ada peringatan aktif</p>
        <p class="empty-state__hint">Siswa terpantau dalam kondisi baik.</p>
    </div>
@else
    <div class="panel overflow-hidden">
        <div class="table-wrap">
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
                            <td class="font-medium text-text">{{ $log->student->name ?? '-' }}</td>
                            <td>
                                <span class="badge {{ match($log->type) {
                                    'absence_streak' => 'badge-danger',
                                    'attendance_rate' => 'badge-warning',
                                    'low_score' => 'badge-warning',
                                    default => '',
                                } }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
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
                                        <button type="submit" class="link">Tandai Selesai</button>
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