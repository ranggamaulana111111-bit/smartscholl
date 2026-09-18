@extends('layouts.app')

@section('title', 'Detail Jurnal KBM')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">Detail Jurnal KBM</h1>
</div>
<div class="card max-w-2xl p-6 space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-xs text-text-muted uppercase tracking-wide">Tanggal</p>
            <p class="mt-1 text-sm font-medium text-text">{{ $journal->date?->translatedFormat('d M Y') }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase tracking-wide">Status</p>
            <p class="mt-1">
                <span class="badge {{ $journal->status === 'closed' ? 'badge-success' : 'badge-warning' }}">
                    {{ $journal->status === 'closed' ? 'Selesai' : 'Draft' }}
                </span>
            </p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase tracking-wide">Guru</p>
            <p class="mt-1 text-sm font-medium text-text">{{ $journal->teacher->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase tracking-wide">Rombel</p>
            <p class="mt-1 text-sm font-medium text-text">{{ $journal->rombel->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase tracking-wide">Mata Pelajaran</p>
            <p class="mt-1 text-sm font-medium text-text">{{ $journal->subject->name ?? '-' }}</p>
        </div>
    </div>
    <div>
        <p class="text-xs text-text-muted uppercase tracking-wide">Topik / Materi</p>
        <p class="mt-1 text-sm font-medium text-text">{{ $journal->topic }}</p>
    </div>
    @if($journal->notes)
        <div>
            <p class="text-xs text-text-muted uppercase tracking-wide">Catatan</p>
            <p class="mt-1 text-sm text-text whitespace-pre-wrap">{{ $journal->notes }}</p>
        </div>
    @endif
    <div class="flex items-center gap-3 pt-4 border-t border-border">
        <a href="{{ route('journals.index') }}" class="btn btn-ghost">Kembali</a>
        @if(auth()->user()->hasRole('guru') && auth()->id() === $journal->user_id && $journal->status === 'draft')
            <a href="{{ route('journals.edit', $journal) }}" class="btn btn-primary">Edit</a>
        @endif
    </div>
</div>
@endsection
