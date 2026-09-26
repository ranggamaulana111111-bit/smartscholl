@extends('layouts.app')

@section('title', 'Detail '.$teacher->name)
@section('content')
<x-page-head
    eyebrow="Data Guru"
    :title="$teacher->name"
    :description="$teacher->subject?->name ?? ($teacher->subject_text ?? 'Guru mata pelajaran')"
>
    <x-slot:actions>
        <a href="{{ route('teachers.edit', $teacher) }}" class="btn btn-primary btn-sm">Edit</a>
        <a href="{{ route('teachers.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </x-slot:actions>
</x-page-head>

<x-panel class="p-6 sm:p-8 mb-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <div>
            <p class="text-xs text-text-muted uppercase">NUPTK</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $teacher->nuptk ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">NIP</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $teacher->nip ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Status Kepegawaian</p>
            <p class="mt-1"><span class="badge">{{ match($teacher->employment_status) { 'asn' => 'ASN', 'gty' => 'GTY', 'ptt' => 'PTT' } }}</span></p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Telepon</p>
            <p class="mt-1 text-sm text-text">{{ $teacher->phone ?? '-' }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 mt-5 pt-5 border-t border-border">
        <div>
            <p class="text-xs text-text-muted uppercase">Alamat</p>
            <p class="mt-1 text-sm text-text">{{ $teacher->address ?? '-' }}</p>
        </div>
    </div>
</x-panel>

<x-panel class="overflow-hidden">
    <div class="px-4 py-3 bg-surface border-b border-border">
        <h2 class="text-sm font-medium text-text">Jadwal Mengajar</h2>
    </div>
    @if($schedules->isEmpty())
        <div class="px-4 py-10 text-center text-sm text-text-muted">
            Belum ada jadwal mengajar untuk guru ini.
        </div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>Hari</th>
                        <th>Mata Pelajaran</th>
                        <th>Rombel</th>
                        <th>Jam</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $i => $schedule)
                        <tr>
                            <td class="text-text-muted">{{ $i + 1 }}</td>
                            <td class="font-medium text-text">{{ \Carbon\Carbon::day($schedule->day_of_week)->translatedFormat('l') }}</td>
                            <td class="text-text-muted">{{ $schedule->subject->name ?? '-' }}</td>
                            <td>
                                <span class="badge">{{ $schedule->rombel->name ?? '-' }}</span>
                            </td>
                            <td class="text-text-muted whitespace-nowrap">{{ $schedule->start_time?->format('H:i') }} - {{ $schedule->end_time?->format('H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-panel>
@endsection