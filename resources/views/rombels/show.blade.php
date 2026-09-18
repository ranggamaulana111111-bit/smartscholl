@extends('layouts.app')

@section('title', 'Detail '.$rombel->name)
@section('content')
<div class="mb-6">
    <a href="{{ route('rombels.index') }}" class="text-sm text-forest-700 hover:text-gold-600 transition-colors">&larr; Kembali</a>
    <h1 class="font-display text-2xl font-semibold text-forest-950 mt-2">Detail Rombel</h1>
</div>

<div class="bg-paper-50 border border-forest-100 rounded-md p-6 mb-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <div>
            <p class="text-xs text-forest-700/60 uppercase">Nama</p>
            <p class="mt-1 font-display text-lg font-semibold text-forest-950">{{ $rombel->name }}</p>
        </div>
        <div>
            <p class="text-xs text-forest-700/60 uppercase">Tingkat</p>
            <p class="mt-1 font-display text-lg font-semibold text-forest-950">{{ $rombel->grade_level }}</p>
        </div>
        <div>
            <p class="text-xs text-forest-700/60 uppercase">Tahun Ajaran</p>
            <p class="mt-1 font-display text-lg font-semibold text-forest-950">{{ $rombel->academicYear?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-forest-700/60 uppercase">Wali Kelas</p>
            <p class="mt-1 font-display text-lg font-semibold text-forest-950">{{ $rombel->homeroomTeacher?->name ?? '-' }}</p>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-paper-50 border border-forest-100 rounded-md overflow-hidden">
        <div class="px-4 py-3 bg-forest-50 border-b border-forest-100 flex items-center justify-between">
            <h2 class="text-sm font-medium text-forest-800">Daftar Siswa ({{ $rombel->students->count() }})</h2>
            <span class="text-xs text-forest-700/70">NISN &middot; Nama</span>
        </div>
        @if($rombel->students->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-forest-700/70">Belum ada siswa dalam rombel ini.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-forest-50 text-left text-forest-800 border-b border-forest-100">
                            <th class="px-4 py-3 font-medium w-12">No</th>
                            <th class="px-4 py-3 font-medium">NISN</th>
                            <th class="px-4 py-3 font-medium">Nama</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-forest-50">
                        @foreach($rombel->students as $i => $student)
                            <tr class="hover:bg-forest-50/50 transition-colors">
                                <td class="px-4 py-3 text-forest-700">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 text-forest-700">{{ $student->nisn }}</td>
                                <td class="px-4 py-3 font-medium text-forest-950">{{ $student->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="bg-paper-50 border border-forest-100 rounded-md overflow-hidden">
        <div class="px-4 py-3 bg-forest-50 border-b border-forest-100">
            <h2 class="text-sm font-medium text-forest-800">Jadwal Pelajaran</h2>
        </div>
        @if($schedules->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-forest-700/70">Belum ada jadwal untuk rombel ini.</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-forest-50 text-left text-forest-800 border-b border-forest-100">
                            <th class="px-4 py-3 font-medium w-12">No</th>
                            <th class="px-4 py-3 font-medium">Mapel</th>
                            <th class="px-4 py-3 font-medium">Guru</th>
                            <th class="px-4 py-3 font-medium">Hari &amp; Jam</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-forest-50">
                        @foreach($schedules as $i => $schedule)
                            <tr class="hover:bg-forest-50/50 transition-colors">
                                <td class="px-4 py-3 text-forest-700">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-forest-950">{{ $schedule->subject->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-forest-700">{{ $schedule->teacher->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-forest-700 whitespace-nowrap">
                                    {{ \Carbon\Carbon::day($schedule->day_of_week)->translatedFormat('l') }}
                                    {{ $schedule->start_time?->format('H:i') }} - {{ $schedule->end_time?->format('H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="flex items-center gap-3">
    <a href="{{ route('rombels.edit', $rombel) }}" class="inline-flex items-center px-4 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">Edit</a>
    <a href="{{ route('rombels.index') }}" class="text-sm text-forest-700 hover:text-forest-900 transition-colors">Kembali</a>
</div>
@endsection