@extends('layouts.app')

@section('title', 'Pilih Pertemuan: '.$schedule->subject->name)
@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('attendance.manual') }}" class="text-xs text-text-muted hover:text-text transition-colors">&larr; Kembali ke pilihan jadwal</a>
        <h1 class="font-display text-2xl font-semibold text-text mt-2">Absen Manual Kelas {{ $schedule->rombel->name }}</h1>
        <p class="text-sm text-text-muted/70 mt-1">
            {{ $schedule->subject->name }} &middot;
            Hari {{ \Carbon\Carbon::day($schedule->day_of_week)->translatedFormat('l') }} &middot;
            {{ $schedule->start_time->format('H:i') }}-{{ $schedule->end_time->format('H:i') }} &middot;
            Guru {{ $schedule->teacher->name }}
        </p>
        <p class="text-xs text-text-muted/60 mt-2">Pilih pertemuan yang akan diisi kehadirannya.</p>
    </div>

    @if(count($meetings) === 0)
        <div class="mb-5 p-8 text-center bg-surface border border-border rounded-md">
            <p class="text-sm text-text font-medium">Belum ada pertemuan pada semester ini.</p>
            <p class="text-xs text-text-muted/70 mt-1">Pastikan tahun ajaran jadwal sudah diatur rentang tanggalnya.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($meetings as $meeting)
                <a href="{{ route('attendance.manualSchedule', [$schedule->id, 'date' => $meeting['date']->toDateString()]) }}"
                    class="group bg-white border rounded-lg p-5 transition-all
                        {{ $meeting['is_today'] ? 'border-accent ring-1 ring-accent' : 'border-border hover:border-accent hover:shadow-md' }}">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-primary text-on-primary text-xs font-medium">
                            Pertemuan {{ $meeting['number'] }}
                        </span>
                        @if($meeting['filled'])
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-surface text-text text-xs font-medium">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Diisi {{ $meeting['total'] }} siswa
                            </span>
                        @elseif($meeting['is_today'])
                            <span class="px-2 py-1 rounded-md bg-accent text-primary text-xs font-semibold">Hari ini</span>
                        @else
                            <span class="px-2 py-1 rounded-md bg-surface text-text-muted/60 text-xs font-medium">Belum diisi</span>
                        @endif
                    </div>
                    <p class="text-sm text-text-muted/70">{{ $meeting['date']->translatedFormat('l, d M Y') }}</p>
                    <p class="text-xs text-text-muted/50 mt-2">
                        {{ $schedule->start_time->format('H:i') }} - {{ $schedule->end_time->format('H:i') }}
                    </p>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection