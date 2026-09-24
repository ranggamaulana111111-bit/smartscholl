@extends('layouts.app')

@section('title', 'Manajemen Guru')
@section('content')
<x-page-head
    eyebrow="Manajemen Data"
    title="Manajemen Guru"
    description="Data guru dan tenaga pendidik beserta mata pelajaran yang diampu."
>
    <x-slot:actions>
        <a href="{{ route('teachers.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah Guru
        </a>
    </x-slot:actions>
</x-page-head>

@if($teachers->isEmpty())
    <x-panel class="p-12 text-center">
        <p class="text-text-muted">Belum ada data guru.</p>
    </x-panel>
@else
    <x-panel class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>NUPTK</th>
                        <th>Nama</th>
                        <th>Mata Pelajaran</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($teachers as $i => $teacher)
                        <tr>
                            <td class="text-text-muted">{{ $teachers->firstItem() + $i }}</td>
                            <td class="text-text-muted">{{ $teacher->nuptk ?? '-' }}</td>
                            <td class="font-medium">{{ $teacher->name }}</td>
                            <td class="text-text-muted">{{ $teacher->subject?->name ?? ($teacher->subject_text ?? '-') }}</td>
                            <td>
                                <span class="badge">{{ match($teacher->employment_status) { 'asn' => 'ASN', 'gty' => 'GTY', 'ptt' => 'PTT' } }}</span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-4">
                                    <a href="{{ route('teachers.show', $teacher) }}" class="link">Detail</a>
                                    <a href="{{ route('teachers.edit', $teacher) }}" class="link">Edit</a>
                                    <form method="POST" action="{{ route('teachers.destroy', $teacher) }}" class="inline" onsubmit="return confirm('Hapus guru ini?');">
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
            {{ $teachers->links() }}
        </div>
    </x-panel>
@endif
@endsection