@extends('layouts.app')

@section('title', 'Mata Pelajaran')
@section('content')
<x-page-head
    eyebrow="Manajemen Data"
    title="Mata Pelajaran"
    description="Kelola daftar mata pelajaran yang diajarkan."
>
    <x-slot:actions>
        <a href="{{ route('subjects.create') }}" class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Tambah
        </a>
    </x-slot:actions>
</x-page-head>

@if($subjects->isEmpty())
    <x-panel class="p-12 text-center">
        <p class="text-text-muted">Belum ada mata pelajaran.</p>
    </x-panel>
@else
    <x-panel class="overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th class="w-12">No</th>
                        <th>Kode Mapel</th>
                        <th>Nama Mata Pelajaran</th>
                        <th>Guru Pengajar</th>
                        <th>Kelas</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjects as $i => $subject)
                        <tr>
                            <td class="text-text-muted">{{ $subjects->firstItem() + $i }}</td>
                            <td class="text-text-muted">{{ $subject->code ?? '-' }}</td>
                            <td class="font-semibold text-text">{{ $subject->name }}</td>
                            <td class="max-w-xs">
                                @forelse($subject->teacher_names ?? [] as $teacher)
                                    <span class="badge mb-1">{{ $teacher }}</span>
                                @empty
                                    <span class="text-text-muted">-</span>
                                @endforelse
                            </td>
                            <td class="max-w-xs">
                                @forelse($subject->rombel_names ?? [] as $rombel)
                                    <span class="badge mb-1">{{ $rombel }}</span>
                                @empty
                                    <span class="text-text-muted">-</span>
                                @endforelse
                            </td>
                            <td>
                                <span class="{{ $subject->is_active ? 'badge badge-success' : 'badge badge-danger' }}">
                                    {{ $subject->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-4">
                                    <a href="{{ route('subjects.show', $subject) }}" class="link">Detail</a>
                                    <a href="{{ route('subjects.edit', $subject) }}" class="link">Edit</a>
                                    <form method="POST" action="{{ route('subjects.destroy', $subject) }}" class="inline" data-confirm="Hapus mata pelajaran ini?">
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
        <div class="px-4 py-3 border-t border-border">{{ $subjects->links() }}</div>
    </x-panel>
@endif
@endsection