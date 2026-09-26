@extends('layouts.app')

@section('title', 'Pilih Pertemuan: '.$schedule->subject->name)
@section('content')
<div class="max-w-4xl">
    <x-page-head title="Absen Manual Kelas {{ $schedule->rombel->name }}"
        description="{{ $schedule->subject->name }} &middot; Hari {{ \Carbon\Carbon::day($schedule->day_of_week)->translatedFormat('l') }} &middot; {{ $schedule->start_time->format('H:i') }}-{{ $schedule->end_time->format('H:i') }} &middot; Guru {{ $schedule->teacher->name }}">
        <x-slot name="actions">
            <a href="{{ route('attendance.manual') }}" class="btn btn-ghost btn-sm">&larr; Jadwal Mengajar</a>
        </x-slot>
    </x-page-head>

    @if(count($meetings) === 0)
        <div class="panel p-12 text-center">
            <span class="empty-state__icon" aria-hidden="true">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z"/></svg>
            </span>
            <p class="empty-state__title">Belum ada pertemuan pada semester ini</p>
            <p class="empty-state__hint">Pastikan tahun ajaran jadwal sudah diatur rentang tanggalnya.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($meetings as $meeting)
                <a href="{{ route('attendance.manualSchedule', [$schedule->id, 'date' => $meeting['date']->toDateString()]) }}"
                    class="bento-card p-5 group {{ $meeting['is_today'] ? 'ring-1 ring-accent' : '' }}">
                    <div class="flex items-center justify-between mb-3">
                        <span class="badge">Pertemuan {{ $meeting['number'] }}</span>
                        @if($meeting['filled'])
                            <span class="badge badge-success">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Diisi {{ $meeting['total'] }} siswa
                            </span>
                        @elseif($meeting['is_today'])
                            <span class="badge">
                                <span class="w-2 h-2 rounded-full bg-accent" aria-hidden="true"></span>
                                Hari ini
                            </span>
                        @else
                            <span class="badge">Belum diisi</span>
                        @endif
                    </div>
                    <p class="text-sm text-text-muted">{{ $meeting['date']->translatedFormat('l, d M Y') }}</p>
                    <p class="text-xs text-text-muted/70 mt-2">{{ $schedule->start_time->format('H:i') }} - {{ $schedule->end_time->format('H:i') }}</p>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection