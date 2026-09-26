@extends('layouts.app')

@section('title', 'Tugas & PR')
@section('content')
<x-page-head title="Tugas & PR"
    description="Tugas harian, batas waktu, dan status pengumpulan."
    eyebrow="Akademik">
    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
        <x-slot name="actions">
            <a href="{{ route('assignments.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Tugas
            </a>
        </x-slot>
    @endif
</x-page-head>

<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bento-card bento-card--surface p-5 text-center">
        <p class="text-sm text-text-muted">Tugas Aktif</p>
        <p class="mt-1 font-display text-2xl font-semibold text-text">{{ $stats['open'] }}</p>
    </div>
    <div class="bento-card bento-card--surface p-5 text-center">
        <p class="text-sm text-text-muted">Terlewat</p>
        <p class="mt-1 font-display text-2xl font-semibold text-danger">{{ $stats['overdue'] }}</p>
    </div>
</div>

@if($assignments->isEmpty())
    <div class="panel p-12 text-center">
        <span class="empty-state__icon" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/></svg>
        </span>
        <p class="empty-state__title">Belum ada tugas tercatat</p>
        <p class="empty-state__hint">Buat tugas pertama untuk rombel.</p>
    </div>
@else
    <div class="panel overflow-hidden">
        <div class="table-wrap">
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
                            <td class="font-medium text-text">{{ $a->title }}</td>
                            <td class="text-text-muted">{{ $a->subject->name ?? '-' }}</td>
                            <td><span class="badge">{{ $a->rombel->name ?? '-' }}</span></td>
                            <td class="text-text-muted">{{ $a->teacher->name ?? '-' }}</td>
                            <td class="text-text-muted">{{ $a->deadline_label }}</td>
                            <td>
                                <span class="badge {{ $a->is_overdue ? 'badge-danger' : 'badge-success' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $a->is_overdue ? 'Terlewat' : 'Aktif' }}
                                </span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('assignments.show', $a) }}" class="link">Lihat</a>
                                @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
                                    <form method="POST" action="{{ route('assignments.destroy', $a) }}" class="inline ml-3" data-confirm="Hapus tugas ini?">
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
        <div class="px-4 py-3 border-t border-border">{{ $assignments->links() }}</div>
    </div>
@endif
@endsection