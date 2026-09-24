@extends('layouts.app')

@section('title', 'Detail '.$academicYear->name)
@section('content')
<x-page-head
    eyebrow="Data Tahun Ajaran"
    :title="$academicYear->name"
    :description="'Semester ' . $academicYear->semester"
>
    <x-slot:actions>
        <a href="{{ route('academic-years.edit', $academicYear) }}" class="btn btn-primary btn-sm">Edit</a>
        <a href="{{ route('academic-years.index') }}" class="btn btn-ghost btn-sm">Kembali</a>
    </x-slot:actions>
</x-page-head>

<x-panel class="p-6 sm:p-8 mb-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <div>
            <p class="text-xs text-text-muted uppercase">Nama</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $academicYear->name }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Semester</p>
            <p class="mt-1 font-display text-lg font-semibold text-text capitalize">{{ $academicYear->semester }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Periode</p>
            <p class="mt-1 font-medium text-text">{{ $academicYear->start_date->translatedFormat('d M Y') }} s.d. {{ $academicYear->end_date->translatedFormat('d M Y') }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Status</p>
            <p class="mt-1">
                @if($academicYear->is_active)
                    <span class="badge badge-success">Aktif</span>
                @else
                    <span class="badge">Nonaktif</span>
                @endif
            </p>
        </div>
    </div>
</x-panel>

<div class="grid grid-cols-2 gap-4 mb-6">
    <x-panel class="p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Rombel</p>
        <p class="mt-1 font-display text-2xl font-semibold text-text">{{ $rombels->count() }}</p>
    </x-panel>
    <x-panel class="p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Total Siswa</p>
        <p class="mt-1 font-display text-2xl font-semibold text-text">{{ $totalStudents }}</p>
    </x-panel>
</div>

<x-panel class="overflow-hidden">
    <div class="px-4 py-3 bg-surface border-b border-border">
        <h2 class="text-sm font-medium text-text">Daftar Rombel</h2>
    </div>
    @if($rombels->isEmpty())
        <div class="px-4 py-10 text-center text-sm text-text-muted">Belum ada rombel pada tahun ajaran ini.</div>
    @else
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>Nama</th>
                        <th>Tingkat</th>
                        <th>Wali Kelas</th>
                        <th>Siswa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rombels as $i => $rombel)
                        <tr>
                            <td class="text-text-muted">{{ $i + 1 }}</td>
                            <td class="font-medium text-text">{{ $rombel->name }}</td>
                            <td class="text-text-muted">{{ $rombel->grade_level }}</td>
                            <td class="text-text-muted">{{ $rombel->homeroomTeacher?->name ?? '-' }}</td>
                            <td>
                                <span class="badge">{{ $rombel->students_count }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-panel>
@endsection