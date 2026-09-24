@extends('layouts.app')

@section('title', 'Absen Manual')
@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold text-text">Absen Manual</h1>
        <p class="text-sm text-text-muted/70 mt-1">Pilih jadwal untuk mengisi kehadiran seluruh siswa di kelas.</p>
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

    @if(auth()->user()->hasRole('guru'))
        <div class="mb-5 p-4 bg-surface border border-border rounded-md text-xs text-text">
            Hanya jadwal mengajar Anda yang ditampilkan.
        </div>
    @endif

    @if($schedules->isEmpty())
        <div class="mb-5 p-8 text-center bg-surface border border-border rounded-md">
            <p class="text-sm text-text font-medium">Belum ada jadwal.</p>
            @if(! auth()->user()->hasRole('guru'))
                <p class="text-xs text-text-muted/70 mt-1">Buat jadwal terlebih dahulu lewat menu Akademik &rarr; Jadwal.</p>
            @else
                <p class="text-xs text-text-muted/70 mt-1">Anda belum dijadwalkan mengajar. Hubungi admin sekolah untuk mendapat jadwal.</p>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($schedules as $sched)
                <a href="{{ route('attendance.manualMeetings', $sched->id) }}"
                    class="group bg-white border border-border rounded-lg p-5 hover:border-accent hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-primary text-on-primary text-xs font-medium">
                            {{ \Carbon\Carbon::day($sched->day_of_week)->translatedFormat('l') }}
                        </span>
                        <span class="text-xs text-text-muted/60">{{ $sched->start_time->format('H:i') }} - {{ $sched->end_time->format('H:i') }}</span>
                    </div>
                    <h2 class="font-display text-lg font-semibold text-text group-hover:text-text-muted transition-colors">
                        {{ $sched->subject->name }}
                    </h2>
                    <p class="text-sm text-text-muted/70 mt-0.5">Kelas {{ $sched->rombel->name }}</p>
                    <p class="text-xs text-text-muted/50 mt-3 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        {{ $sched->teacher->name }}
                    </p>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection