@extends('layouts.app')

@section('title', 'Rekap Absensi Harian')
@section('content')
<x-page-head title="Rekap Absensi"
    :description="$rekap['day_label'].', '.$rekap['date_label'].'. Satu siswa dihitung satu kali, meskipun hadir di beberapa pelajaran.'"
    eyebrow="Absensi">
    <x-slot name="actions">
        @can('attendance.view')
            <a href="{{ route('attendance.recap.export', ['date' => $date, 'rombel' => $rombelId]) }}" class="btn btn-ghost">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0 4-4m-4 4-4-4M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>
                </svg>
                Unduh CSV
            </a>
        @endcan
        <a href="{{ route('attendance.manual') }}" class="btn btn-primary">Isi Manual</a>
    </x-slot>
</x-page-head>

<form method="GET" action="{{ route('attendance.recap') }}" class="mb-6 max-w-2xl bg-white border border-border rounded-md p-4 grid gap-3 sm:grid-cols-[minmax(0,12rem)_minmax(0,1fr)_auto] sm:items-end">
    <div>
        <label for="date" class="label">Tanggal</label>
        <input type="date" id="date" name="date" value="{{ $date }}" class="input">
    </div>
    <div>
        <label for="rombel" class="label">Rombel</label>
        <select id="rombel" name="rombel" class="input">
            <option value="">Semua rombel</option>
            @foreach($rombels as $rombel)
                <option value="{{ $rombel->id }}" @selected($rombelId === $rombel->id)>{{ $rombel->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Tampilkan</button>
</form>

@if(! $rekap['is_school_day'])
    <x-empty title="{{ $rekap['day_label'] }} bukan hari sekolah"
        hint="Rekap hanya terisi pada hari sekolah. Tanggal ini {{ $rekap['day_label'] }}, jadi memang tidak ada absensi yang tercatat.">
        <x-slot:icon>
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/>
            </svg>
        </x-slot:icon>
    </x-empty>
@else
    <div class="bento mb-6">
        <div class="bento-card lg:col-span-4">
            <div class="bento-card__header">
                <p class="bento-card__title">Sudah Absen</p>
            </div>
            <div class="bento-card__body pt-4">
                <p class="text-3xl font-semibold text-text">{{ $rekap['totals']['recorded'] }}<span class="text-base font-normal text-text-muted"> / {{ $rekap['totals']['students'] }}</span></p>
                <div class="mt-3 h-2 rounded-full bg-surface overflow-hidden" role="img" aria-label="Persentase siswa yang sudah absen {{ $rekap['totals']['rate'] }} persen">
                    <div class="h-full rounded-full bg-primary" style="width: {{ min(100, (float) $rekap['totals']['rate']) }}%"></div>
                </div>
                <p class="mt-2 text-xs text-text-muted">{{ $rekap['totals']['rate'] }}% dari seluruh siswa</p>
            </div>
        </div>

        @foreach(['hadir' => ['Hadir', 'text-success'], 'sakit' => ['Sakit', 'text-warning'], 'izin' => ['Izin', 'text-text'], 'alpha' => ['Alpa', 'text-danger']] as $key => [$label, $tone])
            <div class="bento-card lg:col-span-2">
                <div class="bento-card__header">
                    <p class="bento-card__title">{{ $label }}</p>
                </div>
                <div class="bento-card__body pt-4">
                    <p class="text-3xl font-semibold {{ $tone }}">{{ $rekap['totals'][$key] }}</p>
                    <p class="mt-1 text-xs text-text-muted">siswa</p>
                </div>
            </div>
        @endforeach
    </div>

    @if($rekap['rombels']->isEmpty())
        <x-empty title="Tidak ada rombel yang bisa ditampilkan"
            hint="Rombel yang Anda pilih di luar wewenang atau memang belum ada siswa." />
    @else
        <div class="panel overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <caption class="sr-only">Rekap kehadiran per rombel pada {{ $rekap['date_label'] }}</caption>
                    <thead>
                        <tr>
                            <th scope="col">Rombel</th>
                            <th scope="col" class="text-right">Siswa</th>
                            <th scope="col" class="text-right">Sudah</th>
                            <th scope="col" class="text-right">Belum</th>
                            <th scope="col" class="text-right">Hadir</th>
                            <th scope="col" class="text-right">Sakit</th>
                            <th scope="col" class="text-right">Izin</th>
                            <th scope="col" class="text-right">Alpa</th>
                            <th scope="col">Kelengkapan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rekap['rombels'] as $row)
                            <tr>
                                <th scope="row" class="font-medium text-text">{{ $row['name'] }}</th>
                                <td class="text-right tabular-nums">{{ $row['students'] }}</td>
                                <td class="text-right tabular-nums font-medium text-text">{{ $row['recorded'] }}</td>
                                <td class="text-right tabular-nums {{ $row['unrecorded'] > 0 ? 'text-danger font-semibold' : 'text-text-muted' }}">{{ $row['unrecorded'] }}</td>
                                <td class="text-right tabular-nums text-success">{{ $row['hadir'] }}</td>
                                <td class="text-right tabular-nums text-warning">{{ $row['sakit'] }}</td>
                                <td class="text-right tabular-nums">{{ $row['izin'] }}</td>
                                <td class="text-right tabular-nums text-danger">{{ $row['alpha'] }}</td>
                                <td class="w-40">
                                    <div class="h-2 rounded-full bg-surface overflow-hidden" role="img" aria-label="Kelengkapan {{ $row['name'] }} {{ $row['rate'] }} persen">
                                        <div class="h-full rounded-full {{ $row['rate'] >= 90 ? 'bg-success' : ($row['rate'] >= 70 ? 'bg-warning' : 'bg-danger') }}" style="width: {{ min(100, (float) $row['rate']) }}%"></div>
                                    </div>
                                    <span class="block mt-1 text-xs tabular-nums text-text-muted">{{ $row['rate'] }}%</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-surface font-semibold">
                            <th scope="row">Total</th>
                            <td class="text-right tabular-nums">{{ $rekap['totals']['students'] }}</td>
                            <td class="text-right tabular-nums">{{ $rekap['totals']['recorded'] }}</td>
                            <td class="text-right tabular-nums">{{ $rekap['totals']['unrecorded'] }}</td>
                            <td class="text-right tabular-nums text-success">{{ $rekap['totals']['hadir'] }}</td>
                            <td class="text-right tabular-nums text-warning">{{ $rekap['totals']['sakit'] }}</td>
                            <td class="text-right tabular-nums">{{ $rekap['totals']['izin'] }}</td>
                            <td class="text-right tabular-nums text-danger">{{ $rekap['totals']['alpha'] }}</td>
                            <td class="tabular-nums">{{ $rekap['totals']['rate'] }}%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="panel mt-6">
            <div class="px-5 py-4 border-b border-border flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-text">Siswa tanpa catatan</h2>
                    <p class="text-sm text-text-muted">Belum punya absensi pelajaran pada {{ $rekap['day_label'] }}. Scan di gerbang tidak dihitung di sini.</p>
                </div>
                <span class="badge {{ $rekap['unrecorded_total'] > 0 ? 'badge-danger' : 'badge-success' }}">{{ $rekap['unrecorded_total'] }} siswa</span>
            </div>

            @if($rekap['unrecorded']->isEmpty())
                <x-empty title="Semua siswa sudah punya catatan"
                    hint="Tidak ada siswa yang tertinggal pada {{ $rekap['day_label'] }}." class="border-0" />
            @else
                <ul class="divide-y divide-border">
                    @foreach($rekap['unrecorded'] as $row)
                        <li class="flex items-center justify-between gap-4 px-5 py-3">
                            <div class="min-w-0">
                                <p class="font-medium text-text truncate">{{ $row['student']->name }}</p>
                                <p class="text-xs text-text-muted">{{ $row['rombel_name'] }} &middot; NISN {{ $row['student']->nisn }}</p>
                            </div>
                            <a href="{{ route('attendance.qrcodes', ['rombel' => $row['student']->rombel_id]) }}" class="btn btn-ghost shrink-0 text-xs">Kartu QR</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
@endif
@endsection