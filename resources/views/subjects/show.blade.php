@extends('layouts.app')

@section('title', 'Detail '.$subject->name)
@section('content')
<div class="mb-6">
    <a href="{{ route('subjects.index') }}" class="text-sm text-text-muted hover:text-accent transition-colors">&larr; Kembali</a>
    <h1 class="font-display text-2xl font-semibold text-text mt-2">Detail Mata Pelajaran</h1>
</div>

<div class="card p-6 mb-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <div>
            <p class="text-xs text-text-muted uppercase">Kode Mapel</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $subject->code ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Nama Mata Pelajaran</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $subject->name }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Guru Pengajar</p>
            <div class="mt-1">
                @forelse($teacherNames as $teacher)
                    <span class="badge mb-1">{{ $teacher }}</span>
                @empty
                    <span class="text-text-muted">-</span>
                @endforelse
            </div>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Kelas / Rombel</p>
            <div class="mt-1">
                @forelse($rombelNames as $rombel)
                    <span class="badge mb-1">{{ $rombel }}</span>
                @empty
                    <span class="text-text-muted">-</span>
                @endforelse
            </div>
        </div>
    </div>
    <div class="mt-5 pt-5 border-t border-border">
        <span class="{{ $subject->is_active ? 'badge-success' : 'badge-danger' }}">
            {{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}
        </span>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="px-4 py-3 bg-surface border-b border-border">
        <h2 class="text-sm font-medium text-text">Jadwal &amp; Pengajar</h2>
    </div>
    @if($schedules->isEmpty())
        <div class="px-4 py-10 text-center text-sm text-text-muted">
            Belum ada jadwal untuk mata pelajaran ini.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>Kode Mapel</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Guru Pengajar</th>
                        <th>Kelas</th>
                        <th>Hari &amp; Jam</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $i => $schedule)
                        <tr>
                            <td class="text-text-muted">{{ $i + 1 }}</td>
                            <td class="text-text-muted">{{ $subject->code ?? '-' }}</td>
                            <td class="font-semibold text-text">{{ $subject->name }}</td>
                            <td>{{ $schedule->teacher->name ?? '-' }}</td>
                            <td><span class="badge">{{ $schedule->rombel->name ?? '-' }}</span></td>
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
</div>

<div class="flex items-center gap-3 mt-6">
    <a href="{{ route('subjects.edit', $subject) }}" class="btn btn-primary">Edit</a>
    <a href="{{ route('subjects.index') }}" class="btn btn-ghost">Kembali</a>
</div>
@endsection