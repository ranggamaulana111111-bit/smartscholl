@extends('layouts.app')

@section('title', 'Manajemen Siswa')
@section('content')
<x-page-head
    eyebrow="Manajemen Data"
    title="Manajemen Siswa"
    description="Data pokok siswa per rombel, termasuk kontak dan kredensial login."
>
    <x-slot:actions>
        <a href="{{ route('students.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Siswa
        </a>
    </x-slot:actions>
</x-page-head>

@if($students->isEmpty())
    <x-panel class="p-12 text-center">
        <p class="text-text-muted">Belum ada data siswa.</p>
    </x-panel>
@else
    <x-panel class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>NISN</th>
                        <th>Nama</th>
                        <th>L/P</th>
                        <th>Rombel</th>
                        <th>Tanggal Lahir</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $i => $student)
                        <tr>
                            <td class="text-text-muted">{{ $students->firstItem() + $i }}</td>
                            <td class="text-text-muted">{{ $student->nisn }}</td>
                            <td class="font-medium">{{ $student->name }}</td>
                            <td>{{ $student->gender }}</td>
                            <td>
                                @if($student->rombel)
                                    <span class="badge">{{ $student->rombel->name }}</span>
                                @else
                                    <span class="text-text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-text-muted">{{ $student->birth_date?->translatedFormat('d M Y') }}</td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-4">
                                    <a href="{{ route('students.show', $student) }}" class="link">Detail</a>
                                    <a href="{{ route('students.progress', $student) }}" class="link">Monitor</a>
                                    <a href="{{ route('students.rapor', $student) }}" class="link" target="_blank">Rapor</a>
                                    <a href="{{ route('students.edit', $student) }}" class="link">Edit</a>
                                    <form method="POST" action="{{ route('students.destroy', $student) }}" class="inline" onsubmit="return confirm('Hapus siswa ini?');">
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
        <div class="px-4 py-3 border-t border-border">{{ $students->links() }}</div>
    </x-panel>
@endif
@endsection