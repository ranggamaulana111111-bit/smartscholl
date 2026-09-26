@extends('layouts.app')

@section('title', 'Riwayat Absensi')
@section('content')
<x-page-head title="Riwayat Absensi"
    description="Rekap kehadiran siswa per hari."
    eyebrow="Absensi">
    <x-slot name="actions">
        <a href="{{ route('attendance.scan') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Mode Scan
        </a>
        <a href="{{ route('attendance.manual') }}" class="btn btn-ghost">Absensi Manual</a>
    </x-slot>
</x-page-head>

<form method="GET" action="{{ route('attendance.index') }}" class="mb-6 max-w-md bg-white border border-border rounded-md p-4 flex items-end gap-3">
    <div class="flex-1">
        <label for="date" class="label">Tanggal</label>
        <input type="date" id="date" name="date" value="{{ $date }}" class="input">
    </div>
    <button type="submit" class="btn btn-primary shrink-0">Tampilkan</button>
</form>

@php
    $statusBadge = [
        'hadir' => 'badge-success',
        'sakit' => 'badge-warning',
        'izin' => '',
        'alpha' => 'badge-danger',
    ];
    $typeLabels = ['gate_in' => 'Masuk', 'gate_out' => 'Pulang', 'lesson' => 'Pelajaran'];
@endphp

<div class="bento mb-6">
    @foreach(['gate_in' => 'Masuk Gerbang', 'gate_out' => 'Pulang Gerbang', 'lesson' => 'Hadir Pelajaran'] as $type => $title)
        <div class="bento-card lg:col-span-4">
            <div class="bento-card__header">
                <p class="bento-card__title">{{ $title }}</p>
            </div>
            <div class="bento-card__body pt-4">
                @if(isset($byStatus[$type]))
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between items-center gap-4 border-b border-border pb-2"><dt class="text-text-muted">Hadir</dt><dd class="font-semibold text-success">{{ $byStatus[$type]['hadir'] }}</dd></div>
                        <div class="flex justify-between items-center gap-4 border-b border-border pb-2"><dt class="text-text-muted">Sakit</dt><dd class="font-semibold text-warning">{{ $byStatus[$type]['sakit'] }}</dd></div>
                        <div class="flex justify-between items-center gap-4 border-b border-border pb-2"><dt class="text-text-muted">Izin</dt><dd class="font-semibold text-text">{{ $byStatus[$type]['izin'] }}</dd></div>
                        <div class="flex justify-between items-center gap-4"><dt class="text-text-muted">Alpha</dt><dd class="font-semibold text-danger">{{ $byStatus[$type]['alpha'] }}</dd></div>
                    </dl>
                @else
                    <p class="text-sm text-text-muted">Belum ada data.</p>
                @endif
            </div>
        </div>
    @endforeach
</div>

@if($attendances->isEmpty())
    <div class="panel p-12 text-center">
        <span class="empty-state__icon" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
        </span>
        <p class="empty-state__title">Belum ada riwayat absensi</p>
        <p class="empty-state__hint">Tidak ada pencatatan kehadiran untuk tanggal yang dipilih.</p>
    </div>
@else
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Siswa</th>
                        <th>Rombel</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th>Sumber</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $attendance)
                        <tr>
                            <td class="text-text-muted">{{ $attendance->time }}</td>
                            <td>
                                <span class="font-medium text-text">{{ $attendance->student?->name ?? '-' }}</span>
                                <span class="block text-xs font-normal text-text-muted">{{ $attendance->student?->nisn ?? '' }}</span>
                            </td>
                            <td class="text-text-muted">{{ $attendance->student?->rombel?->name ?? '-' }}</td>
                            <td class="text-text-muted">{{ $typeLabels[$attendance->type] ?? $attendance->type }}</td>
                            <td>
                                <span class="badge {{ $statusBadge[$attendance->status] ?? '' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $statusLabels[$attendance->status] ?? $attendance->status }}
                                </span>
                            </td>
                            <td class="text-text-muted">{{ $attendance->source === 'scan' ? 'Scan' : 'Manual' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-border">
            {{ $attendances->links() }}
        </div>
    </div>
@endif
@endsection