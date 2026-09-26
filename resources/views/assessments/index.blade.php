@extends('layouts.app')

@section('title', 'Daftar Penilaian')
@section('content')
<x-page-head title="Penilaian"
    description="Daftar seluruh penilaian siswa."
    eyebrow="Akademik">
    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
        <x-slot name="actions">
            <a href="{{ route('assessments.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Penilaian
            </a>
        </x-slot>
    @endif
</x-page-head>

@if($assessments->isEmpty())
    <div class="panel p-12 text-center">
        <span class="empty-state__icon" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
        </span>
        <p class="empty-state__title">Belum ada penilaian tercatat</p>
        <p class="empty-state__hint">Buat penilaian pertama untuk memulai penilaian hasil belajar.</p>
    </div>
@else
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Mapel</th>
                        <th>Kategori</th>
                        <th>Bobot</th>
                        <th>Tgl</th>
                        <th>Rombel</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assessments as $a)
                        <tr>
                            <td class="font-medium text-text">{{ $a->title }}</td>
                            <td class="text-text-muted">{{ $a->subject->name ?? '-' }}</td>
                            <td><span class="badge">{{ $a->category_label }}</span></td>
                            <td class="text-text-muted">{{ $a->weight_percentage ? $a->weight_percentage.'%' : '-' }}</td>
                            <td class="text-text-muted">{{ $a->date?->translatedFormat('d M') }}</td>
                            <td><span class="badge">{{ $a->rombel->name ?? 'Semua' }}</span></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('assessments.show', $a) }}" class="link mr-3">Lihat</a>
                                @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
                                    <a href="{{ route('assessments.export', $a) }}" class="link mr-3">Export</a>
                                    <a href="{{ route('assessments.edit', $a) }}" class="link mr-3">Edit</a>
                                    <form method="POST" action="{{ route('assessments.destroy', $a) }}" class="inline" data-confirm="Hapus penilaian ini?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="link-danger">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-border">{{ $assessments->links() }}</div>
    </div>
@endif
@endsection