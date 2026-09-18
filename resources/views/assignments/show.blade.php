@extends('layouts.app')

@section('title', 'Detail Tugas')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">{{ $assignment->title }}</h1>
    <p class="text-sm text-text-muted mt-1">
        {{ $assignment->subject->name ?? '-' }} &middot; Rombel {{ $assignment->rombel->name ?? '-' }} &middot; {{ $assignment->teacher->name ?? '-' }}
    </p>
</div>

<div class="card p-6 mb-6 space-y-3">
    <div class="flex items-center gap-3">
        <span class="badge {{ $assignment->is_overdue ? 'badge-danger' : 'badge-success' }}">{{ $assignment->is_overdue ? 'Terlewat' : 'Aktif' }}</span>
        <span class="text-sm text-text-muted">Batas waktu: <strong class="text-text">{{ $assignment->deadline_label }}</strong></span>
    </div>
    @if($assignment->description)
        <p class="text-sm text-text whitespace-pre-wrap">{{ $assignment->description }}</p>
    @endif
    @if($assignment->attachment_path)
        <a href="{{ route('assignments.download', $assignment) }}" class="btn btn-accent btn-sm">Download Berkas</a>
    @endif
</div>

@if(auth()->user()->hasRole('siswa'))
    <div class="card p-6 mb-6">
        <h2 class="font-display text-sm font-semibold text-text mb-3">Pengumpulan Tugas</h2>
        @if($mySubmission)
            <p class="text-sm text-text-muted mb-3">
                Telah dikumpulkan pada <strong class="text-text">{{ $mySubmission->submitted_at?->translatedFormat('d M Y H:i') }}</strong>
                @if($mySubmission->note)
                    &middot; {{ $mySubmission->note }}
                @endif
            </p>
            <div class="flex items-center gap-3">
                @if($mySubmission->attachment_path)
                    <a href="{{ Storage::disk('public')->url($mySubmission->attachment_path) }}" target="_blank" class="btn btn-ghost btn-sm">Buka Berkas</a>
                @endif
                @if(! $assignment->is_overdue)
                    <details class="inline-block">
                        <summary class="btn btn-primary btn-sm cursor-pointer">Perbarui Pengumpulan</summary>
                        <form method="POST" action="{{ route('assignments.submit', $assignment) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <label class="label">Catatan</label>
                                <textarea name="note" rows="2" maxlength="1000" class="input">{{ old('note') }}</textarea>
                            </div>
                            <div>
                                <label class="label">Berkas (opsional)</label>
                                <input type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt,.jpg,.jpeg,.png" class="input">
                            </div>
                            <button type="submit" class="btn btn-primary">Simpan</button>
                        </form>
                    </details>
                @endif
            </div>
        @else
            @if($assignment->is_overdue)
                <div class="p-4 rounded-lg border border-danger bg-red-50 text-sm text-red-800" role="alert">Batas waktu pengumpulan telah lewat.</div>
            @else
                <form method="POST" action="{{ route('assignments.submit', $assignment) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="label">Catatan</label>
                        <textarea name="note" rows="2" maxlength="1000" placeholder="jawaban / ringkasan dikerjakan" class="input">{{ old('note') }}</textarea>
                    </div>
                    <div>
                        <label class="label">Berkas (opsional)</label>
                        <input type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt,.jpg,.jpeg,.png" class="input">
                        @error('attachment')<p class="error-text">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn btn-primary">Kumpulkan</button>
                        <a href="{{ route('assignments.index') }}" class="btn btn-ghost">Kembali</a>
                    </div>
                </form>
            @endif
        @endif
    </div>
@endif

@if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
    <div class="card overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Status Pengumpulan ({{ $students->count() }} siswa)</h2>
        </div>
        @if($students->isEmpty())
            <div class="px-4 py-6 text-center text-sm text-text-muted">Tidak ada siswa pada rombel ini.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Nama</th>
                            <th>Status</th>
                            <th>Dikumpul</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $s)
                            @php $sub = $submissions->firstWhere('student_id', $s->id); @endphp
                            <tr>
                                <td class="text-text-muted">{{ $s->nisn }}</td>
                                <td class="font-medium">{{ $s->name }}</td>
                                <td>
                                    <span class="badge {{ $sub ? 'badge-success' : 'badge-warning' }}">{{ $sub ? 'Dikumpulkan' : 'Belum' }}</span>
                                </td>
                                <td class="text-text-muted">{{ $sub?->submitted_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td class="text-text-muted max-w-xs truncate">{{ $sub?->note ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif

<div class="mt-6">
    <a href="{{ route('assignments.index') }}" class="btn btn-ghost">Kembali</a>
</div>
@endsection