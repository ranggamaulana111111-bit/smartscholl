@extends('layouts.app')

@section('title', 'Absen Manual: '.$schedule->subject->name)
@section('content')
<div class="max-w-3xl">
    <x-page-head title="Absen Manual Kelas {{ $schedule->rombel->name }}"
        description="{{ $schedule->subject->name }} &middot; Hari {{ \Carbon\Carbon::day($schedule->day_of_week)->translatedFormat('l') }} &middot; {{ $schedule->start_time->format('H:i') }}-{{ $schedule->end_time->format('H:i') }} &middot; Guru {{ $schedule->teacher->name }} {{ $pertemuan ? '&middot; Pertemuan '.$pertemuan : '' }}">
        <x-slot name="actions">
            <a href="{{ route('attendance.manualMeetings', $schedule->id) }}" class="btn btn-ghost btn-sm">&larr; Pilih Pertemuan</a>
        </x-slot>
    </x-page-head>

    <form method="GET" action="{{ route('attendance.manualSchedule', $schedule->id) }}" class="bg-white border border-border rounded-md p-4 mb-6 flex flex-col sm:flex-row items-start sm:items-end gap-3">
        <div class="flex-1 w-full">
            <label for="date" class="label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" id="date" name="date" value="{{ $date }}" required class="input">
        </div>
        <button type="submit" class="btn btn-primary shrink-0">Tampilkan</button>
    </form>

    @if($students->isEmpty())
        <div class="panel p-12 text-center">
            <span class="empty-state__icon" aria-hidden="true">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1m8-7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm6-3v6m3-3h-6"/></svg>
            </span>
            <p class="empty-state__title">Belum ada siswa di rombel ini</p>
            <p class="empty-state__hint">Tambahkan siswa ke rombel terlebih dahulu melalui menu Master Data.</p>
        </div>
    @else
        <div class="mt-2 mb-4 flex flex-wrap items-center gap-4 text-xs text-text-muted">
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-primary" aria-hidden="true"></span> Masuk</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-warning" aria-hidden="true"></span> Sakit</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-surface border border-border" aria-hidden="true"></span> Izin</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-danger" aria-hidden="true"></span> Alpa</span>
        </div>

        <div class="panel overflow-hidden">
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Siswa</th>
                            <th>NISN</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                            <tr>
                                <td>
                                    <span class="font-medium text-text">{{ $student->name }}</span>
                                    <span class="block text-xs font-normal text-text-muted sm:hidden">{{ $student->nisn }}</span>
                                </td>
                                <td class="text-text-muted hidden sm:table-cell">{{ $student->nisn }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-1.5 justify-end">
                                        @foreach(['hadir' => 'Masuk', 'sakit' => 'Sakit', 'izin' => 'Izin', 'alpha' => 'Alpa'] as $status => $label)
                                            <form method="POST" action="{{ route('attendance.storeManual') }}" data-confirm="Catat {{ $label }} untuk {{ $student->name }}?">
                                                @csrf
                                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
                                                <input type="hidden" name="type" value="lesson">
                                                <input type="hidden" name="status" value="{{ $status }}">
                                                <input type="hidden" name="date" value="{{ $date }}">
                                                <button type="submit"
                                                    class="px-3 py-1.5 text-xs rounded-md border font-medium transition-colors
                                                        {{ $status === 'hadir'
                                                            ? 'border-primary bg-primary text-on-primary hover:bg-primary-hover'
                                                            : ($status === 'sakit'
                                                                ? 'border-warning/40 bg-warning/10 text-warning hover:bg-warning/20'
                                                                : ($status === 'izin'
                                                                    ? 'border-border bg-surface text-text hover:bg-white'
                                                                    : 'border-danger/40 bg-danger/10 text-danger hover:bg-danger/20')) }}">
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
        </div>
    @endif

    <div class="mt-8 panel p-5">
        <h2 class="font-display text-lg font-semibold text-text mb-1">Rekap Bulanan &amp; Semesteran</h2>
        <p class="text-xs text-text-muted mb-4">Jumlah status per siswa untuk jadwal ini.</p>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th class="text-center" colspan="4">Bulan Ini</th>
                        <th class="text-center" colspan="4">Semester Ini</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th class="text-center">Masuk</th>
                        <th class="text-center">Sakit</th>
                        <th class="text-center">Izin</th>
                        <th class="text-center">Alpa</th>
                        <th class="text-center">Masuk</th>
                        <th class="text-center">Sakit</th>
                        <th class="text-center">Izin</th>
                        <th class="text-center">Alpa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        @php
                            $m = $monthly[$student->id];
                            $s = $semester[$student->id];
                        @endphp
                        <tr>
                            <td class="text-text">{{ $student->name }}</td>
                            <td class="text-center text-success">{{ $m['hadir'] }}</td>
                            <td class="text-center text-warning">{{ $m['sakit'] }}</td>
                            <td class="text-center text-text-muted">{{ $m['izin'] }}</td>
                            <td class="text-center text-danger">{{ $m['alpha'] }}</td>
                            <td class="text-center text-success">{{ $s['hadir'] }}</td>
                            <td class="text-center text-warning">{{ $s['sakit'] }}</td>
                            <td class="text-center text-text-muted">{{ $s['izin'] }}</td>
                            <td class="text-center text-danger">{{ $s['alpha'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-6 text-center text-text-muted">Belum ada siswa di rombel ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection