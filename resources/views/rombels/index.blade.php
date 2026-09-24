@extends('layouts.app')

@section('title', 'Manajemen Rombel')
@section('content')
<x-page-head
    eyebrow="Manajemen Data"
    title="Manajemen Rombel"
    description="Pembagian rombongan belajar siswa per tingkat dan tahun ajaran."
>
    <x-slot:actions>
        <a href="{{ route('rombels.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Rombel
        </a>
    </x-slot:actions>
</x-page-head>

@if($rombels->isEmpty())
    <x-panel class="p-12 text-center">
        <p class="text-text-muted">Belum ada data rombel.</p>
    </x-panel>
@else
    <x-panel class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>Nama</th>
                        <th>Tingkat</th>
                        <th>Tahun Ajaran</th>
                        <th>Wali Kelas</th>
                        <th>Siswa</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rombels as $i => $rombel)
                        <tr>
                            <td class="text-text-muted">{{ $rombels->firstItem() + $i }}</td>
                            <td class="font-medium">{{ $rombel->name }}</td>
                            <td>{{ $rombel->grade_level }}</td>
                            <td class="text-text-muted">{{ $rombel->academicYear?->name }}</td>
                            <td class="text-text-muted">{{ $rombel->homeroomTeacher?->name ?? '-' }}</td>
                            <td>
                                <span class="badge">{{ $rombel->students_count }}</span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-4">
                                    <a href="{{ route('rombels.show', $rombel) }}" class="link">Detail</a>
                                    <a href="{{ route('rombels.edit', $rombel) }}" class="link">Edit</a>
                                    <form method="POST" action="{{ route('rombels.destroy', $rombel) }}" class="inline" onsubmit="return confirm('Hapus rombel ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link link-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-border">
            {{ $rombels->links() }}
        </div>
    </x-panel>
@endif
@endsection