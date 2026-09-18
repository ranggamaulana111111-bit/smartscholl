@extends('layouts.app')

@section('title', 'Detail Penilaian')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">{{ $assessment->title }}</h1>
    <p class="text-sm text-text-muted mt-1">{{ $assessment->subject->name ?? '' }} &middot; {{ $assessment->category_label }} &middot; Nilai Maks {{ $assessment->max_score }}</p>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Jumlah Siswa</p>
        <p class="mt-1 font-display text-2xl font-semibold text-text">{{ $stats['count'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Rata-rata</p>
        <p class="mt-1 font-display text-2xl font-semibold text-accent">{{ $stats['avg'] }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Nilai Tertinggi</p>
        <p class="mt-1 font-display text-2xl font-semibold text-text">{{ $stats['max'] ?? '-' }}</p>
    </div>
    <div class="card p-5 text-center">
        <p class="text-xs text-text-muted uppercase">Nilai Terendah</p>
        <p class="mt-1 font-display text-2xl font-semibold text-danger">{{ $stats['min'] ?? '-' }}</p>
    </div>
</div>

@if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
    <div class="card p-5 mb-6">
        <h2 class="font-display text-sm font-semibold text-text mb-3">Import Nilai (CSV)</h2>
        <p class="text-xs text-text-muted mb-3">Format kolom: <code class="font-mono bg-surface rounded px-1.5 py-0.5">nisn, nama, nilai, catatan</code>. Baris dengan NISN kosong, nilai di luar rentang, atau siswa tidak dikenal akan dilewati.</p>
        <form method="POST" action="{{ route('assessments.import', $assessment) }}" enctype="multipart/form-data" class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
            @csrf
            <div class="flex-1 w-full sm:max-w-xs">
                <label class="label" for="import-file">File CSV</label>
                <input type="file" id="import-file" name="file" accept=".csv" class="input" required>
                @error('file')<p class="error-text">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Import</button>
            <a href="{{ route('assessments.template', $assessment) }}" class="btn btn-ghost">Download Template</a>
        </form>
    </div>
@endif

@if($canGrade)
    <form method="POST" action="{{ route('assessments.grades', $assessment) }}" id="grade-form" class="card overflow-hidden">
        @csrf
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>NISN</th>
                        <th>Nama</th>
                        <th class="w-32">Nilai (maks {{ $assessment->max_score }})</th>
                        <th class="w-64">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td class="text-text-muted">{{ $loop->iteration }}</td>
                            <td class="text-text-muted">{{ $student->nisn }}</td>
                            <td class="font-medium">{{ $student->name }}</td>
                            <td>
                                <input type="number"
                                    name="scores[{{ $student->id }}]"
                                    value="{{ old('scores.'.$student->id, $grades[$student->id]->score ?? '') }}"
                                    min="0" max="{{ $assessment->max_score }}" step="0.01" inputmode="decimal"
                                    class="input score-input text-right @error('scores.'.$student->id) border-danger @enderror">
                                @error('scores.'.$student->id)<p class="error-text">{{ $message }}</p>@enderror
                            </td>
                            <td>
                                <input type="text"
                                    name="notes[{{ $student->id }}]"
                                    value="{{ old('notes.'.$student->id, $grades[$student->id]->note ?? '') }}"
                                    maxlength="255" class="input" placeholder="opsional">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-text-muted">Penilaian belum memiliki rombel tujuan. Tetapkan rombel pada penilaian ini agar dapat mengisi nilai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->isNotEmpty())
            <div class="p-4 border-t border-surface flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <p class="text-xs text-text-muted">Enter pada kolom nilai memindah ke siswa berikutnya. Kolom kosong menghapus nilai siswa tersebut.</p>
                <button type="submit" class="btn btn-primary">Simpan Nilai</button>
            </div>
        @endif
    </form>

    <script>
    (function () {
        const form = document.getElementById('grade-form');
        if (!form) return;
        form.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            if (e.target.tagName.toLowerCase() === 'textarea') return;
            if (e.target.tagName.toLowerCase() === 'input') {
                e.preventDefault();
                const inputs = form.querySelectorAll('input.score-input');
                const idx = Array.prototype.indexOf.call(inputs, e.target);
                const next = inputs[idx + 1];
                if (next) {
                    next.focus();
                    next.select();
                }
            }
        });
    })();
    </script>
@else
    @if($grades->isEmpty())
        <div class="card p-12 text-center">
            <p class="text-text-muted">Belum ada nilai tercatat untuk penilaian ini.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Nama</th>
                            <th class="text-right">Nilai</th>
                            <th>Capaian</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($grades as $g)
                            <tr>
                                <td class="text-text-muted">{{ $g->student->nisn ?? '-' }}</td>
                                <td class="font-medium">{{ $g->student->name ?? '-' }}</td>
                                <td class="text-right font-display font-semibold {{ $g->score >= 75 ? 'text-text' : 'text-danger' }}">
                                    {{ number_format($g->score, 2) }}
                                </td>
                                <td>
                                    <span class="badge {{ $g->score >= 75 ? 'badge-success' : 'badge-danger' }}">{{ deskripsiCapaian((float) $g->score) }}</span>
                                </td>
                                <td class="text-text-muted">{{ $g->note ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endif

<div class="flex items-center gap-3 mt-6">
    <a href="{{ route('assessments.index') }}" class="btn btn-ghost">Kembali</a>
    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
        <a href="{{ route('assessments.export', $assessment) }}" class="btn btn-accent">Download CSV</a>
        <a href="{{ route('assessments.edit', $assessment) }}" class="btn btn-primary">Edit Penilaian</a>
    @endif
</div>
@endsection
