@extends('layouts.app')

@section('title', 'Riwayat Absensi')
@section('content')
<x-page-head title="Riwayat Absensi"
    description="Catatan kehadiran siswa per hari, termasuk scan gerbang."
    eyebrow="Absensi">
    <x-slot name="actions">
        @can('attendance.scan')
            <a href="{{ route('attendance.scan') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Mode Scan
            </a>
        @endcan
        @can('attendance.manual')
            <a href="{{ route('attendance.manual') }}" class="btn btn-ghost">Absensi Manual</a>
        @endcan
    </x-slot>
</x-page-head>

<form method="GET" action="{{ route('attendance.index') }}" class="mb-6 max-w-md bg-white border border-border rounded-md p-4 flex items-end gap-3">
    <div class="flex-1">
        <label for="date" class="label">Tanggal</label>
        <input type="date" id="date" name="date" value="{{ $date }}" class="input">
    </div>
    <button type="submit" class="btn btn-primary shrink-0">Tampilkan</button>
</form>

<div class="bento mb-6">
    <div class="bento-card lg:col-span-4">
        <div class="bento-card__header">
            <p class="bento-card__title">Kelengkapan Pelajaran</p>
        </div>
        <div class="bento-card__body pt-4">
            <p class="text-3xl font-semibold text-text">
                {{ $summary['recorded'] }}<span class="text-base font-normal text-text-muted"> / {{ $summary['students'] }}</span>
            </p>
            <div class="mt-3 h-2 rounded-full bg-surface overflow-hidden" role="img" aria-label="Kelengkapan {{ $summary['rate'] }} persen">
                <div class="h-full rounded-full bg-primary" style="width: {{ min(100, (float) $summary['rate']) }}%"></div>
            </div>
            <p class="mt-2 text-xs text-text-muted">{{ $summary['rate'] }}% siswa sudah punya catatan pelajaran</p>
            <a href="{{ route('attendance.recap', ['date' => $date]) }}" class="btn btn-ghost mt-4 w-full">Buka rekap lengkap</a>
        </div>
    </div>

    @foreach(['hadir' => ['Hadir', 'text-success'], 'sakit' => ['Sakit', 'text-warning'], 'izin' => ['Izin', 'text-text'], 'alpha' => ['Alpa', 'text-danger']] as $key => [$label, $tone])
        <div class="bento-card lg:col-span-2">
            <div class="bento-card__header">
                <p class="bento-card__title">{{ $label }}</p>
            </div>
            <div class="bento-card__body pt-4">
                <p class="text-3xl font-semibold {{ $tone }}">{{ $summary[$key] }}</p>
                <p class="mt-1 text-xs text-text-muted">dari {{ $totalStudents }} siswa</p>
            </div>
        </div>
    @endforeach

    <div class="bento-card lg:col-span-6">
        <div class="bento-card__header">
            <p class="bento-card__title">Gerbang</p>
        </div>
        <div class="bento-card__body pt-4">
            <dl class="grid gap-3 sm:grid-cols-3">
                <div>
                    <dt class="text-sm text-text-muted">Masuk</dt>
                    <dd class="text-2xl font-semibold tabular-nums text-text">{{ $gate['gate_in'] }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-text-muted">Pulang</dt>
                    <dd class="text-2xl font-semibold tabular-nums text-text">{{ $gate['gate_out'] }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-text-muted">Pulang tanpa scan masuk</dt>
                    <dd class="text-2xl font-semibold tabular-nums {{ $gate['gate_out_without_gate_in'] > 0 ? 'text-warning' : 'text-text' }}">{{ $gate['gate_out_without_gate_in'] }}</dd>
                </div>
            </dl>
            <p class="mt-3 text-xs text-text-muted">
                Dihitung dari jumlah siswa berbeda, jadi scan ulang kartu yang sama tidak menambah angka.
            </p>
        </div>
    </div>
</div>

@if($attendances->isEmpty())
    <x-empty title="Belum ada riwayat absensi" hint="Tidak ada pencatatan kehadiran untuk tanggal yang dipilih.">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
            </svg>
        </x-slot:icon>
    </x-empty>
@else
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Riwayat absensi tanggal {{ $date }}</caption>
                <thead>
                    <tr>
                        <th scope="col">Waktu</th>
                        <th scope="col">Siswa</th>
                        <th scope="col">Kelas</th>
                        <th scope="col">Jenis</th>
                        <th scope="col">Status</th>
                        <th scope="col">Sumber</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $attendance)
                        <tr>
                            <td class="text-text-muted tabular-nums">{{ $attendance->time }}</td>
                            <td>
                                <span class="font-medium text-text">{{ $attendance->student?->name ?? '-' }}</span>
                                <span class="block text-xs font-normal text-text-muted">{{ $attendance->student?->nisn ?? '' }}</span>
                            </td>
                            <td class="text-text-muted">{{ $attendance->student?->rombel?->name ?? '-' }}</td>
                            <td class="text-text-muted">{{ $typeLabels[$attendance->type] ?? $attendance->type }}</td>
                            <td>
                                <span class="badge {{ $attendance->status === 'alpha' ? 'badge-danger' : ($attendance->status === 'hadir' ? 'badge-success' : 'badge-pending') }}">
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