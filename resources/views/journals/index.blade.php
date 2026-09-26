@extends('layouts.app')

@section('title', 'Jurnal KBM')
@section('content')
<x-page-head title="Jurnal KBM"
    description="Catatan kegiatan belajar mengajar."
    eyebrow="Dokumentasi">
    @if(auth()->user()->hasRole('guru'))
        <x-slot name="actions">
            <a href="{{ route('journals.create') }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Isi Jurnal
            </a>
        </x-slot>
    @endif
</x-page-head>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bento-card bento-card--surface p-5">
        <p class="text-sm text-text-muted">Total Jurnal</p>
        <p class="mt-1 font-display text-2xl font-semibold text-text">{{ $stats['total'] }}</p>
    </div>
    <div class="bento-card bento-card--surface p-5">
        <p class="text-sm text-text-muted">Draft</p>
        <p class="mt-1 font-display text-2xl font-semibold text-warning">{{ $stats['draft'] }}</p>
    </div>
    <div class="bento-card bento-card--surface p-5">
        <p class="text-sm text-text-muted">Selesai</p>
        <p class="mt-1 font-display text-2xl font-semibold text-success">{{ $stats['closed'] }}</p>
    </div>
</div>

@if($journals->isEmpty())
    <div class="panel p-12 text-center">
        <span class="empty-state__icon" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>
        </span>
        <p class="empty-state__title">Belum ada jurnal KBM</p>
        <p class="empty-state__hint">Catat kegiatan belajar mengajar pertamamu.</p>
    </div>
@else
    <div class="panel overflow-hidden">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Guru</th>
                        <th>Mapel</th>
                        <th>Rombel</th>
                        <th>Topik</th>
                        <th>Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journals as $j)
                        <tr>
                            <td class="text-text-muted">{{ $j->date?->translatedFormat('d M Y') }}</td>
                            <td class="text-text-muted">{{ $j->teacher->name ?? '-' }}</td>
                            <td class="font-medium text-text">{{ $j->subject->name ?? '-' }}</td>
                            <td><span class="badge">{{ $j->rombel->name ?? '-' }}</span></td>
                            <td class="text-text-muted max-w-xs truncate">{{ $j->topic }}</td>
                            <td>
                                <span class="badge {{ $j->status === 'closed' ? 'badge-success' : 'badge-warning' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $j->status === 'closed' ? 'Selesai' : 'Draft' }}
                                </span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('journals.show', $j) }}" class="link">Lihat</a>
                                @if(auth()->user()->hasRole('guru') && auth()->id() === $j->user_id)
                                    <a href="{{ route('journals.edit', $j) }}" class="link ml-3">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-border">{{ $journals->links() }}</div>
    </div>
@endif
@endsection