@extends('layouts.app')

@section('title', 'Jejak Pemindaian')
@section('content')
<x-page-head title="Jejak Pemindaian"
    :description="'Setiap kode yang dipindai pada '.$dateLabel.', termasuk kode yang ditolak. Kode disimpan sebagai hash, bukan nilai aslinya.'"
    eyebrow="Audit Kehadiran">
    <x-slot name="actions">
        <a href="{{ route('attendance.index') }}" class="btn btn-ghost">Riwayat Absensi</a>
    </x-slot>
</x-page-head>

<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($outcomeMeta as $key => $meta)
        <a href="{{ route('attendance.scan-events', ['date' => $date, 'outcome' => $key]) }}"
            class="panel p-4 block transition hover:border-primary {{ $outcome === $key ? 'border-primary' : '' }}"
            @if($outcome === $key) aria-current="true" @endif>
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs uppercase tracking-wide text-text-muted">{{ $meta['label'] }}</p>
                @if($outcome === $key)
                    <span class="badge badge-pending">Filter aktif</span>
                @endif
            </div>
            <p class="mt-1 text-2xl font-semibold tabular-nums text-text">{{ $counts[$key] ?? 0 }}</p>
        </a>
    @endforeach
</div>

<form method="GET" action="{{ route('attendance.scan-events') }}" class="mb-6 max-w-3xl bg-white border border-border rounded-md p-4 grid gap-3 sm:grid-cols-[minmax(0,10rem)_minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end">
    <div>
        <label for="date" class="label">Tanggal</label>
        <input type="date" id="date" name="date" value="{{ $date }}" class="input">
    </div>
    <div>
        <label for="outcome" class="label">Hasil</label>
        <select id="outcome" name="outcome" class="input">
            <option value="">Semua hasil</option>
            @foreach($outcomeMeta as $key => $meta)
                <option value="{{ $key }}" @selected($outcome === $key)>{{ $meta['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="type" class="label">Jenis</label>
        <select id="type" name="type" class="input">
            <option value="">Semua jenis</option>
            @foreach($typeLabels as $key => $label)
                <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Tampilkan</button>
</form>

@if($events->isEmpty())
    <x-empty title="Tidak ada jejak pemindaian"
        :hint="'Tidak ada kode yang dipindai pada '.$dateLabel.' dengan filter ini.'" />
@else
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <caption class="sr-only">Jejak pemindaian kode absensi pada {{ $dateLabel }}</caption>
                <thead>
                    <tr>
                        <th scope="col">Waktu</th>
                        <th scope="col">Siswa</th>
                        <th scope="col">Kelas</th>
                        <th scope="col">Jenis</th>
                        <th scope="col">Hasil</th>
                        <th scope="col">Jadwal</th>
                        <th scope="col">Operator</th>
                        <th scope="col">Kode</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($events as $event)
                        <tr>
                            <td class="text-text-muted whitespace-nowrap tabular-nums">{{ $event->created_at?->format('H:i:s') }}</td>
                            <td>
                                <span class="font-medium text-text">{{ $event->student?->name ?? 'Tidak dikenal' }}</span>
                                @if($event->student)
                                    <span class="block text-xs font-normal text-text-muted">NISN {{ $event->student->nisn }}</span>
                                @endif
                            </td>
                            <td class="text-text-muted">{{ $event->student?->rombel?->name ?? '-' }}</td>
                            <td class="text-text-muted">{{ $typeLabels[$event->type] ?? $event->type }}</td>
                            <td>
                                <span class="badge {{ $outcomeMeta[$event->outcome]['tone'] ?? '' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $outcomeMeta[$event->outcome]['label'] ?? $event->outcome }}
                                </span>
                            </td>
                            <td class="text-text-muted">{{ $event->schedule?->subject?->name ?? '-' }}</td>
                            <td class="text-text-muted">{{ $event->recordedBy?->name ?? '-' }}</td>
                            <td class="text-text-muted font-mono text-xs">
                                @if($event->code_hash)
                                    {{ substr($event->code_hash, 0, 12) }}
                                    @if($event->attendance_id)
                                        <span class="block text-[10px] text-primary">terhubung</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-border">
            {{ $events->links() }}
        </div>
    </div>
@endif
@endsection