@extends('layouts.app')

@section('title', 'Detail '.$rombel->name)
@section('content')
<x-page-head
    eyebrow="Data Rombel"
    :title="$rombel->name"
    :description="$rombel->grade_level . ' · ' . ($rombel->academicYear?->name ?? '-')"
>
    <x-slot:actions>
        <a href="{{ route('rombels.edit', $rombel) }}" class="btn btn-primary btn-sm">Edit</a>
        <a href="{{ route('rombels.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </x-slot:actions>
</x-page-head>

<x-panel class="p-6 sm:p-8 mb-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <div>
            <p class="text-xs text-text-muted uppercase">Nama</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $rombel->name }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Tingkat</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $rombel->grade_level }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Tahun Ajaran</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $rombel->academicYear?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Wali Kelas</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $rombel->homeroomTeacher?->name ?? '-' }}</p>
        </div>
    </div>
</x-panel>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <x-panel class="overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border flex items-center justify-between">
            <h2 class="text-sm font-medium text-text">Daftar Siswa ({{ $rombel->students->count() }})</h2>
            <span class="text-xs text-text-muted">NISN &middot; Nama</span>
        </div>
        @if($rombel->students->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-text-muted">Belum ada siswa dalam rombel ini.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-12">No</th>
                            <th>NISN</th>
                            <th>Nama</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rombel->students as $i => $student)
                            <tr>
                                <td class="text-text-muted">{{ $i + 1 }}</td>
                                <td class="text-text-muted">{{ $student->nisn }}</td>
                                <td class="font-medium text-text">{{ $student->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>

    <x-panel class="overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Jadwal Pelajaran</h2>
        </div>
        @if($schedules->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-text-muted">Belum ada jadwal untuk rombel ini.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="w-12">No</th>
                            <th>Mapel</th>
                            <th>Guru</th>
                            <th>Hari &amp; Jam</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $i => $schedule)
                            <tr>
                                <td class="text-text-muted">{{ $i + 1 }}</td>
                                <td class="font-medium text-text">{{ $schedule->subject->name ?? '-' }}</td>
                                <td class="text-text-muted">{{ $schedule->teacher->name ?? '-' }}</td>
                                <td class="text-text-muted whitespace-nowrap">
                                    {{ \Carbon\Carbon::day($schedule->day_of_week)->translatedFormat('l') }}
                                    {{ $schedule->start_time?->format('H:i') }} - {{ $schedule->end_time?->format('H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-panel>
</div>
@endsection