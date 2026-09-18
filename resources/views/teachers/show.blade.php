@extends('layouts.app')

@section('title', 'Detail '.$teacher->name)
@section('content')
<div class="mb-6">
    <a href="{{ route('teachers.index') }}" class="text-sm text-forest-700 hover:text-gold-600 transition-colors">&larr; Kembali</a>
    <h1 class="font-display text-2xl font-semibold text-forest-950 mt-2">Detail Guru</h1>
</div>

<div class="bg-paper-50 border border-forest-100 rounded-md p-6 mb-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <div>
            <p class="text-xs text-forest-700/60 uppercase">NUPTK</p>
            <p class="mt-1 font-display text-lg font-semibold text-forest-950">{{ $teacher->nuptk ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-forest-700/60 uppercase">NIP</p>
            <p class="mt-1 font-display text-lg font-semibold text-forest-950">{{ $teacher->nip ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-forest-700/60 uppercase">Nama</p>
            <p class="mt-1 font-display text-lg font-semibold text-forest-950">{{ $teacher->name }}</p>
        </div>
        <div>
            <p class="text-xs text-forest-700/60 uppercase">Mata Pelajaran</p>
            <p class="mt-1 font-display text-lg font-semibold text-forest-950">{{ $teacher->subject ?? '-' }}</p>
        </div>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mt-5">
        <div>
            <p class="text-xs text-forest-700/60 uppercase">Status Kepegawaian</p>
            <p class="mt-1">
                <span class="inline-flex items-center px-2 py-1 rounded bg-forest-100 text-forest-800 text-xs font-medium">
                    {{ match($teacher->employment_status) { 'asn' => 'ASN', 'gty' => 'GTY', 'ptt' => 'PTT' } }}
                </span>
            </p>
        </div>
        <div>
            <p class="text-xs text-forest-700/60 uppercase">Telepon</p>
            <p class="mt-1 text-sm text-forest-950">{{ $teacher->phone ?? '-' }}</p>
        </div>
        <div class="col-span-2">
            <p class="text-xs text-forest-700/60 uppercase">Alamat</p>
            <p class="mt-1 text-sm text-forest-950">{{ $teacher->address ?? '-' }}</p>
        </div>
    </div>
</div>

<div class="bg-paper-50 border border-forest-100 rounded-md overflow-hidden">
    <div class="px-4 py-3 bg-forest-50 border-b border-forest-100">
        <h2 class="text-sm font-medium text-forest-800">Jadwal Mengajar</h2>
    </div>
    @if($schedules->isEmpty())
        <div class="px-4 py-10 text-center text-sm text-forest-700/70">
            Belum ada jadwal mengajar untuk guru ini.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-forest-50 text-left text-forest-800 border-b border-forest-100">
                        <th class="px-4 py-3 font-medium w-12">No</th>
                        <th class="px-4 py-3 font-medium">Hari</th>
                        <th class="px-4 py-3 font-medium">Mata Pelajaran</th>
                        <th class="px-4 py-3 font-medium">Rombel</th>
                        <th class="px-4 py-3 font-medium">Jam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-forest-50">
                    @foreach($schedules as $i => $schedule)
                        <tr class="hover:bg-forest-50/50 transition-colors">
                            <td class="px-4 py-3 text-forest-700">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-forest-950">{{ \Carbon\Carbon::day($schedule->day_of_week)->translatedFormat('l') }}</td>
                            <td class="px-4 py-3 text-forest-700">{{ $schedule->subject->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded bg-forest-100 text-forest-800 text-xs font-medium">{{ $schedule->rombel->name ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3 text-forest-700 whitespace-nowrap">{{ $schedule->start_time?->format('H:i') }} - {{ $schedule->end_time?->format('H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="flex items-center gap-3 mt-6">
    <a href="{{ route('teachers.edit', $teacher) }}" class="inline-flex items-center px-4 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">Edit</a>
    <a href="{{ route('teachers.index') }}" class="text-sm text-forest-700 hover:text-forest-900 transition-colors">Kembali</a>
</div>
@endsection