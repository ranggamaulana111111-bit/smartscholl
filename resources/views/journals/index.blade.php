@extends('layouts.app')

@section('title', 'Jurnal KBM')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-text">Jurnal KBM</h1>
        <p class="text-sm text-text-muted mt-1">Catatan kegiatan belajar mengajar.</p>
    </div>
    @if(auth()->user()->hasRole('guru'))
        <a href="{{ route('journals.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Isi Jurnal
        </a>
    @endif
</div>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="card p-5 text-center">
        <p class="text-sm text-text-muted">Total Jurnal</p>
        <p class="mt-1 font-display text-2xl font-semibold text-text">{{ $stats['total'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-sm text-text-muted">Draft</p>
        <p class="mt-1 font-display text-2xl font-semibold text-accent">{{ $stats['draft'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-sm text-text-muted">Selesai</p>
        <p class="mt-1 font-display text-2xl font-semibold text-text">{{ $stats['closed'] }}</p>
    </div>
</div>

@if($journals->isEmpty())
    <div class="card p-12 text-center">
        <p class="text-text-muted">Belum ada jurnal KBM.</p>
    </div>
@else
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
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
                            <td>{{ $j->subject->name ?? '-' }}</td>
                            <td><span class="badge">{{ $j->rombel->name ?? '-' }}</span></td>
                            <td class="text-text-muted max-w-xs truncate">{{ $j->topic }}</td>
                            <td>
                                <span class="badge {{ $j->status === 'closed' ? 'badge-success' : 'badge-warning' }}">
                                    {{ $j->status === 'closed' ? 'Selesai' : 'Draft' }}
                                </span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('journals.show', $j) }}" class="text-text-muted hover:text-accent transition-colors">Lihat</a>
                                @if(auth()->user()->hasRole('guru') && auth()->id() === $j->user_id)
                                    <a href="{{ route('journals.edit', $j) }}" class="text-text-muted hover:text-accent ml-3 transition-colors">Edit</a>
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
