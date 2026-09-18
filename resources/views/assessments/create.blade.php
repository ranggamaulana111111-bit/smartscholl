@extends('layouts.app')

@section('title', 'Tambah Penilaian')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">Tambah Penilaian</h1>
    <p class="text-sm text-text-muted mt-1">Buat penilaian baru untuk siswa.</p>
</div>
<form method="POST" action="{{ route('assessments.store') }}" class="card max-w-2xl p-6 space-y-5">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Mata Pelajaran <span class="text-danger">*</span></label>
            <select name="subject_id" class="input @error('subject_id') border-danger @enderror">
                <option value="">-- Pilih Mapel --</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}" @selected(old('subject_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('subject_id')<p class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Kategori <span class="text-danger">*</span></label>
            <select name="category" class="input @error('category') border-danger @enderror">
                <option value="">-- Pilih Kategori --</option>
                <option value="tugas" @selected(old('category') === 'tugas')>Tugas</option>
                <option value="formatif" @selected(old('category') === 'formatif')>Formatif / UH</option>
                <option value="uts" @selected(old('category') === 'uts')>UTS</option>
                <option value="uas" @selected(old('category') === 'uas')>UAS</option>
            </select>
            @error('category')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label class="label">Judul <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title') }}" maxlength="255" placeholder="contoh: UTS Matematika Kelas 7A"
            class="input @error('title') border-danger @enderror">
        @error('title')<p class="error-text">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Rombel (opsional)</label>
            <select name="rombel_id" class="input">
                <option value="">-- Semua Rombel --</option>
                @foreach($rombels as $r)
                    <option value="{{ $r->id }}" @selected(old('rombel_id') == $r->id)>[{{ $r->grade_level }}] {{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Tanggal</label>
            <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}"
                class="input">
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Nilai Maks <span class="text-danger">*</span></label>
            <input type="number" name="max_score" value="{{ old('max_score', 100) }}" min="1" max="1000"
                class="input @error('max_score') border-danger @enderror">
            @error('max_score')<p class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Bobot (%)</label>
            <input type="number" name="weight_percentage" value="{{ old('weight_percentage') }}" min="0" max="100"
                class="input">
        </div>
    </div>
    <p class="text-xs text-text-muted">Bobot acuan Kurikulum: Tugas 20% &middot; Formatif 30% &middot; UTS 25% &middot; UAS 25%.</p>
    <div>
        <label class="label">Deskripsi</label>
        <textarea name="description" rows="2" maxlength="1000" placeholder="deskripsi penilaian (opsional)"
            class="input">{{ old('description') }}</textarea>
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('assessments.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection
