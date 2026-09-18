@extends('layouts.app')

@section('title', 'Daftar Penilaian')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-text">Penilaian</h1>
        <p class="text-sm text-text-muted mt-1">Daftar seluruh penilaian siswa.</p>
    </div>
    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
        <div class="flex gap-3">
            <a href="{{ route('assessments.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Tambah
            </a>
            <a href="{{ route('assessments.input') }}" class="btn btn-accent">Input Nilai</a>
        </div>
    @endif
</div>

@if($assessments->isEmpty())
    <div class="card p-12 text-center">
        <p class="text-text-muted">Belum ada penilaian tercatat.</p>
    </div>
@else
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
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
                </thead>
                <tbody>
                    @foreach($assessments as $a)
                        <tr>
                            <td class="font-medium">{{ $a->title }}</td>
                            <td class="text-text-muted">{{ $a->subject->name ?? '-' }}</td>
                            <td><span class="badge">{{ $a->category_label }}</span></td>
                            <td class="text-text-muted">{{ $a->weight_percentage ? $a->weight_percentage.'%' : '-' }}</td>
                            <td class="text-text-muted">{{ $a->date?->translatedFormat('d M') }}</td>
                            <td><span class="badge">{{ $a->rombel->name ?? 'Semua' }}</span></td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('assessments.show', $a) }}" class="text-text-muted hover:text-accent mr-3 transition-colors">Lihat</a>
                                @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
                                    <a href="{{ route('assessments.export', $a) }}" class="text-text-muted hover:text-accent mr-3 transition-colors">Export</a>
                                    <a href="{{ route('assessments.edit', $a) }}" class="text-text-muted hover:text-accent mr-3 transition-colors">Edit</a>
                                    <form method="POST" action="{{ route('assessments.destroy', $a) }}" class="inline" onsubmit="return confirm('Hapus penilaian ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger hover:opacity-80 transition-colors">Hapus</button>
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
