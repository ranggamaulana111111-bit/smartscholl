@extends('layouts.app')

@section('title', 'Detail '.$academicYear->name)
@section('content')
<div class="mb-6">
    <a href="{{ route('academic-years.index') }}" class="text-sm text-forest-700 hover:text-gold-600 transition-colors">&larr; Kembali</a>
    <h1 class="font-display text-2xl font-semibold text-forest-950 mt-2">Detail Tahun Ajaran</h1>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-paper-50 border border-forest-100 rounded-md p-5">
        <p class="text-xs text-forest-700/60 uppercase">Nama</p>
        <p class="mt-1 font-display text-lg font-semibold text-forest-950">{{ $academicYear->name }}</p>
    </div>
    <div class="bg-paper-50 border border-forest-100 rounded-md p-5">
        <p class="text-xs text-forest-700/60 uppercase">Semester</p>
        <p class="mt-1 font-display text-lg font-semibold text-forest-950 capitalize">{{ $academicYear->semester }}</p>
    </div>
    <div class="bg-paper-50 border border-forest-100 rounded-md p-5">
        <p class="text-xs text-forest-700/60 uppercase">Periode</p>
        <p class="mt-1 font-medium text-forest-950">{{ $academicYear->start_date->translatedFormat('d M Y') }} s.d. {{ $academicYear->end_date->translatedFormat('d M Y') }}</p>
    </div>
    <div class="bg-paper-50 border border-forest-100 rounded-md p-5">
        <p class="text-xs text-forest-700/60 uppercase">Status</p>
        <p class="mt-1">
            @if($academicYear->is_active)
                <span class="inline-flex items-center px-2 py-1 rounded bg-forest-600 text-paper-50 text-xs font-medium">Aktif</span>
            @else
                <span class="inline-flex items-center px-2 py-1 rounded bg-paper-200 text-forest-700 text-xs font-medium">Nonaktif</span>
            @endif
        </p>
    </div>
</div>

<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-paper-50 border border-forest-100 rounded-md p-5 text-center">
        <p class="text-xs text-forest-700/60 uppercase">Rombel</p>
        <p class="mt-1 font-display text-2xl font-semibold text-forest-950">{{ $rombels->count() }}</p>
    </div>
    <div class="bg-paper-50 border border-forest-100 rounded-md p-5 text-center">
        <p class="text-xs text-forest-700/60 uppercase">Total Siswa</p>
        <p class="mt-1 font-display text-2xl font-semibold text-forest-950">{{ $totalStudents }}</p>
    </div>
</div>

<div class="bg-paper-50 border border-forest-100 rounded-md overflow-hidden">
    <div class="px-4 py-3 bg-forest-50 border-b border-forest-100">
        <h2 class="text-sm font-medium text-forest-800">Daftar Rombel</h2>
    </div>
    @if($rombels->isEmpty())
        <div class="px-4 py-10 text-center text-sm text-forest-700/70">Belum ada rombel pada tahun ajaran ini.</div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-forest-50 text-left text-forest-800 border-b border-forest-100">
                        <th class="px-4 py-3 font-medium w-12">No</th>
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium">Tingkat</th>
                        <th class="px-4 py-3 font-medium">Wali Kelas</th>
                        <th class="px-4 py-3 font-medium">Siswa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-forest-50">
                    @foreach($rombels as $i => $rombel)
                        <tr class="hover:bg-forest-50/50 transition-colors">
                            <td class="px-4 py-3 text-forest-700">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-forest-950">{{ $rombel->name }}</td>
                            <td class="px-4 py-3 text-forest-700">{{ $rombel->grade_level }}</td>
                            <td class="px-4 py-3 text-forest-700">{{ $rombel->homeroomTeacher?->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded bg-forest-100 text-forest-800 text-xs font-medium">{{ $rombel->students_count }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="flex items-center gap-3 mt-6">
    <a href="{{ route('academic-years.edit', $academicYear) }}" class="inline-flex items-center px-4 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">Edit</a>
    <a href="{{ route('academic-years.index') }}" class="text-sm text-forest-700 hover:text-forest-900 transition-colors">Kembali</a>
</div>
@endsection