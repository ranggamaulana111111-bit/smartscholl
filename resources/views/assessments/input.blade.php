@extends('layouts.app')

@section('title', 'Input Nilai')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">Input Nilai Siswa</h1>
    <p class="text-sm text-text-muted mt-1">Isi atau perbarui nilai untuk setiap siswa.</p>
</div>
<form method="POST" action="{{ route('assessments.storeInput') }}" class="card p-6">
    @csrf
    <div class="mb-5">
        <label class="label">Penilaian <span class="text-danger">*</span></label>
        <select name="assessment_id" id="assessment_id" class="input max-w-md" required>
            <option value="">-- Pilih Penilaian --</option>
            @foreach(\App\Models\Assessment::with('subject')->latest()->get() as $a)
                <option value="{{ $a->id }}" data-max="{{ $a->max_score }}">{{ $a->title }} — {{ $a->subject->name }} ({{ $a->category_label }})</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th class="w-8">#</th>
                    <th>NISN</th>
                    <th>Nama</th>
                    <th>Rombel</th>
                    <th class="w-24">Nilai</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $i => $s)
                    <tr>
                        <td class="text-text-muted">{{ $i + 1 }}</td>
                        <td class="text-text-muted">{{ $s->nisn }}</td>
                        <td class="font-medium">{{ $s->name }}</td>
                        <td><span class="badge">{{ $s->rombel->name ?? '-' }}</span></td>
                        <td>
                            <input type="number" step="0.01" min="0" max="1000" name="scores[{{ $s->id }}]" id="score_{{ $s->id }}"
                                class="input py-1 px-2 text-sm"
                                placeholder="0">
                        </td>
                        <td>
                            <input type="text" name="notes[{{ $s->id }}]" maxlength="255"
                                class="input py-1 px-2 text-sm"
                                placeholder="catatan opsional">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex items-center gap-3 mt-5">
        <button type="submit" class="btn btn-primary">Simpan Nilai</button>
        <a href="{{ route('assessments.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection
