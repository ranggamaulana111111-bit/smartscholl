@extends('layouts.app')

@section('title', 'Absensi')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-forest-950">Riwayat Absensi</h1>
        <p class="text-sm text-forest-700/70 mt-1">Rekap kehadiran siswa per hari.</p>
    </div>
    <a href="{{ route('attendance.scan') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
        </svg>
        Buka Mode Scan
    </a>
</div>

<form method="GET" action="{{ route('attendance.index') }}" class="mb-6 max-w-xs bg-paper-50 border border-forest-100 rounded-md p-4 flex items-center gap-3">
    <label for="date" class="text-sm font-medium text-forest-800 shrink-0">Tanggal</label>
    <input type="date" id="date" name="date" value="{{ $date }}" class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
    <button type="submit" class="px-4 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors shrink-0">Tampilkan</button>
</form>

@php
    $statusLabels = ['hadir' => 'Hadir', 'sakit' => 'Sakit', 'izin' => 'Izin', 'alpha' => 'Alpha'];
    $typeLabels = ['gate_in' => 'Masuk', 'gate_out' => 'Pulang', 'lesson' => 'Pelajaran'];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
    @foreach(['gate_in' => 'Masuk Gerbang', 'gate_out' => 'Pulang Gerbang', 'lesson' => 'Hadir Pelajaran'] as $type => $title)
        <div class="bg-paper-50 border border-forest-100 rounded-md p-5">
            <p class="text-sm font-medium text-forest-800">{{ $title }}</p>
            @if(isset($byStatus[$type]))
                <div class="mt-3 space-y-1 text-sm text-forest-700">
                    <div class="flex justify-between"><span>Hadir</span><span class="font-medium">{{ $byStatus[$type]['hadir'] }}</span></div>
                    <div class="flex justify-between"><span>Sakit</span><span class="font-medium">{{ $byStatus[$type]['sakit'] }}</span></div>
                    <div class="flex justify-between"><span>Izin</span><span class="font-medium">{{ $byStatus[$type]['izin'] }}</span></div>
                    <div class="flex justify-between"><span>Alpha</span><span class="font-medium">{{ $byStatus[$type]['alpha'] }}</span></div>
                </div>
            @else
                <p class="mt-3 text-sm text-forest-700/60">Belum ada data.</p>
            @endif
        </div>
    @endforeach
</div>

@if($attendances->isEmpty())
    <div class="p-12 bg-paper-50 border border-forest-100 rounded-md text-center">
        <p class="text-forest-700/70">Belum ada riwayat absensi untuk tanggal ini.</p>
    </div>
@else
    <div class="bg-paper-50 border border-forest-100 rounded-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-forest-50 text-left text-forest-800 border-b border-forest-100">
                        <th class="px-4 py-3 font-medium">Waktu</th>
                        <th class="px-4 py-3 font-medium">Siswa</th>
                        <th class="px-4 py-3 font-medium">Rombel</th>
                        <th class="px-4 py-3 font-medium">Jenis</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Sumber</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-forest-50">
                    @foreach($attendances as $attendance)
                        <tr class="hover:bg-forest-50/50 transition-colors">
                            <td class="px-4 py-3 text-forest-700">{{ $attendance->time }}</td>
                            <td class="px-4 py-3 font-medium">
                                {{ $attendance->student?->name ?? '-' }}
                                <span class="block text-xs font-normal text-forest-700/70 mt-0.5">{{ $attendance->student?->nisn ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3 text-forest-700">{{ $attendance->student?->rombel?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-forest-700">{{ $typeLabels[$attendance->type] ?? $attendance->type }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded bg-forest-100 text-forest-800 text-xs font-medium">
                                    {{ $statusLabels[$attendance->status] ?? $attendance->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-forest-700">{{ $attendance->source === 'scan' ? 'Scan' : 'Manual' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-forest-100">
            {{ $attendances->links() }}
        </div>
    </div>
@endif
@endsection