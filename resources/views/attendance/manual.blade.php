@extends('layouts.app')

@section('title', 'Absen Manual')
@section('content')
<div class="max-w-4xl">
    <x-page-head title="Absen Manual"
        description="Pilih jadwal untuk mengisi kehadiran seluruh siswa di kelas."
        eyebrow="{{ auth()->user()->hasRole('guru') ? 'Jadwal mengajar Anda' : 'Akademik' }}">
    </x-page-head>

    @if(! $schedules->isEmpty() && auth()->user()->hasRole('guru'))
        <p class="mb-6 text-sm text-text-muted">Hanya jadwal mengajar Anda yang ditampilkan.</p>
    @endif

    @if($schedules->isEmpty())
        <div class="panel p-12 text-center">
            <span class="empty-state__icon" aria-hidden="true">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
            </span>
            <p class="empty-state__title">Belum ada jadwal</p>
            <p class="empty-state__hint">
                @if(! auth()->user()->hasRole('guru'))
                    Buat jadwal terlebih dahulu lewat menu Akademik, lalu kembali ke halaman ini.
                @else
                    Anda belum dijadwalkan mengajar. Hubungi admin sekolah untuk mendapat jadwal.
                @endif
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($schedules as $sched)
                <a href="{{ route('attendance.manualMeetings', $sched->id) }}"
                    class="bento-card p-5 group" aria-label="Isi kehadiran {{ $sched->subject->name }} kelas {{ $sched->rombel->name }}">
                    <div class="flex items-center justify-between mb-3">
                        <span class="badge">{{ \Carbon\Carbon::day($sched->day_of_week)->translatedFormat('l') }}</span>
                        <span class="text-xs text-text-muted">{{ $sched->start_time->format('H:i') }} - {{ $sched->end_time->format('H:i') }}</span>
                    </div>
                    <h2 class="font-display text-lg font-semibold text-text">{{ $sched->subject->name }}</h2>
                    <p class="text-sm text-text-muted mt-0.5">Kelas {{ $sched->rombel->name }}</p>
                    <p class="text-xs text-text-muted/70 mt-3 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
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