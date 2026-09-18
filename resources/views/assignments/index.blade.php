@extends('layouts.app')

@section('title', 'Tugas & PR')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-text">Tugas & PR</h1>
        <p class="text-sm text-text-muted mt-1">Tugas harian, batas waktu, dan status pengumpulan.</p>
    </div>
    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
        <a href="{{ route('assignments.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Buat Tugas
        </a>
    @endif
</div>

<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="card p-5 text-center">
        <p class="text-sm text-text-muted">Tugas Aktif</p>
        <p class="mt-1 font-display text-2xl font-semibold text-text">{{ $stats['open'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-sm text-text-muted">Terlewat</p>
        <p class="mt-1 font-display text-2xl font-semibold text-danger">{{ $stats['overdue'] }}</p>
    </div>
</div>

@if($assignments->isEmpty())
    <div class="card p-12 text-center">
        <p class="text-text-muted">Belum ada tugas tercatat.</p>
    </div>
@else
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Mapel</th>
                        <th>Rombel</th>
                        <th>Guru</th>
                        <th>Batas Waktu</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $a)
                        <tr>
                            <td class="font-medium">{{ $a->title }}</td>
                            <td class="text-text-muted">{{ $a->subject->name ?? '-' }}</td>
                            <td><span class="badge">{{ $a->rombel->name ?? '-' }}</span></td>
                            <td class="text-text-muted">{{ $a->teacher->name ?? '-' }}</td>
                            <td class="text-text-muted">{{ $a->deadline_label }}</td>
                            <td>
                                <span class="badge {{ $a->is_overdue ? 'badge-danger' : 'badge-success' }}">{{ $a->is_overdue ? 'Terlewat' : 'Aktif' }}</span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('assignments.show', $a) }}" class="text-text-muted hover:text-accent transition-colors">Lihat</a>
                                @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
                                    <form method="POST" action="{{ route('assignments.destroy', $a) }}" class="inline ml-3" onsubmit="return confirm('Hapus tugas ini?');">
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
        <div class="px-4 py-3 border-t border-border">{{ $assignments->links() }}</div>
    </div>
@endif
@endsection