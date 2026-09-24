@extends('layouts.app')

@section('title', 'Absen Manual: '.$schedule->subject->name)
@section('content')
<div class="max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('attendance.manualMeetings', $schedule->id) }}" class="text-xs text-text-muted hover:text-text transition-colors">&larr; Kembali ke pilihan pertemuan</a>
        <h1 class="font-display text-2xl font-semibold text-text mt-2">Absen Manual Kelas {{ $schedule->rombel->name }}</h1>
        <p class="text-sm text-text-muted/70 mt-1">
            @if($pertemuan)
                Pertemuan {{ $pertemuan }} &middot;
            @endif
            {{ $schedule->subject->name }} &middot;
            Hari {{ \Carbon\Carbon::day($schedule->day_of_week)->translatedFormat('l') }} &middot;
            {{ $schedule->start_time->format('H:i') }}-{{ $schedule->end_time->format('H:i') }} &middot;
            Guru {{ $schedule->teacher->name }}
        </p>
    </div>

    @if(session('success'))
        <div class="mb-5 p-4 bg-surface border border-border rounded-md text-sm text-text">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <form method="GET" action="{{ route('attendance.manualSchedule', $schedule->id) }}" class="bg-white border border-border rounded-md p-4 mb-6 flex items-end gap-3">
        <div class="flex-1">
            <label for="date" class="block text-sm font-medium text-text mb-1">Tanggal <span class="text-danger">*</span></label>
            <input type="date" id="date" name="date" value="{{ $date }}" required
                class="w-full px-3 py-2 rounded-md border border-border bg-white text-text text-sm focus:border-accent outline-none">
        </div>
        <button type="submit" class="px-5 py-2 bg-primary text-on-primary text-sm font-medium rounded-md hover:bg-primary-hover transition-colors">Tampilkan</button>
    </form>

    @if($students->isEmpty())
        <div class="mb-5 p-8 text-center bg-surface border border-border rounded-md">
            <p class="text-sm text-text font-medium">Belum ada siswa di rombel ini.</p>
        </div>
    @else
        <div class="bg-white border border-border rounded-md overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-primary text-on-primary">
                        <th class="px-4 py-3 text-left font-medium">Nama Siswa</th>
                        <th class="px-4 py-3 text-left font-medium hidden sm:table-cell">NISN</th>
                        <th class="px-4 py-3 text-left font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        <tr class="border-t border-border">
                            <td class="px-4 py-3">
                                <p class="text-text font-medium">{{ $student->name }}</p>
                                <p class="text-xs text-text-muted/60 sm:hidden mt-0.5">{{ $student->nisn }}</p>
                            </td>
                            <td class="px-4 py-3 text-text-muted/70 hidden sm:table-cell">{{ $student->nisn }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach(['hadir' => 'Masuk', 'sakit' => 'Sakit', 'izin' => 'Izin', 'alpha' => 'Alpa'] as $status => $label)
                                        <form method="POST" action="{{ route('attendance.storeManual') }}">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
                                            <input type="hidden" name="type" value="lesson">
                                            <input type="hidden" name="status" value="{{ $status }}">
                                            <input type="hidden" name="date" value="{{ $date }}">
                                            <button type="submit"
                                                class="px-3 py-1.5 text-xs rounded-md border transition-colors
                                                    {{ $status === 'hadir'
                                                        ? 'border-primary bg-primary text-on-primary'
                                                        : ($status === 'sakit'
                                                            ? 'border-orange-300 bg-orange-100 text-orange-900 hover:bg-orange-200'
                                                            : ($status === 'izin'
                                                                ? 'border-sky-300 bg-sky-100 text-sky-900 hover:bg-sky-200'
                                                                : 'border-red-300 bg-red-100 text-red-900 hover:bg-red-200')) }}">
                                                {{ $label }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-8 bg-white border border-border rounded-md p-5">
        <h2 class="font-display text-lg font-semibold text-text mb-1">Rekap Bulanan &amp; Semesteran</h2>
        <p class="text-xs text-text-muted/60 mb-4">Jumlah status per siswa untuk jadwal ini.</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-text-muted border-b border-border">
                        <th class="px-3 py-2 text-left font-medium">Siswa</th>
                        <th colspan="4" class="px-3 py-2 text-center font-medium bg-surface text-text">Bulan Ini</th>
                        <th colspan="4" class="px-3 py-2 text-center font-medium bg-accent/10 text-text">Semester Ini</th>
                    </tr>
                    <tr class="text-text-muted border-b border-border">
                        <th></th>
                        <th class="px-3 py-2 text-center text-text">Masuk</th>
                        <th class="px-3 py-2 text-center text-orange-700">Sakit</th>
                        <th class="px-3 py-2 text-center text-sky-700">Izin</th>
                        <th class="px-3 py-2 text-center text-red-700">Alpa</th>
                        <th class="px-3 py-2 text-center text-text">Masuk</th>
                        <th class="px-3 py-2 text-center text-orange-700">Sakit</th>
                        <th class="px-3 py-2 text-center text-sky-700">Izin</th>
                        <th class="px-3 py-2 text-center text-red-700">Alpa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $m = $monthly[$student->id];
                            $s = $semester[$student->id];
                        @endphp
                        <tr class="border-t border-border">
                            <td class="px-3 py-2 text-text">{{ $student->name }}</td>
                            <td class="px-3 py-2 text-center text-text">{{ $m['hadir'] }}</td>
                            <td class="px-3 py-2 text-center text-orange-700">{{ $m['sakit'] }}</td>
                            <td class="px-3 py-2 text-center text-sky-700">{{ $m['izin'] }}</td>
                            <td class="px-3 py-2 text-center text-red-700">{{ $m['alpha'] }}</td>
                            <td class="px-3 py-2 text-center text-text">{{ $s['hadir'] }}</td>
                            <td class="px-3 py-2 text-center text-orange-700">{{ $s['sakit'] }}</td>
                            <td class="px-3 py-2 text-center text-sky-700">{{ $s['izin'] }}</td>
                            <td class="px-3 py-2 text-center text-red-700">{{ $s['alpha'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-4 text-center text-sm text-text-muted/60">Belum ada siswa di rombel ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection