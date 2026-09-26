@extends('layouts.app')

@section('title', 'Tambah Penilaian')
@section('content')
<x-page-head title="Tambah Penilaian"
    description="Buat penilaian baru untuk siswa."
    eyebrow="Akademik">
</x-page-head>
<form method="POST" action="{{ route('assessments.store') }}" class="panel max-w-2xl p-6 space-y-5">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="subject_id" class="label">Mata Pelajaran <span class="text-danger">*</span></label>
            <select name="subject_id" id="subject_id" class="input @error('subject_id') border-danger @enderror" aria-invalid="{{ $errors->has('subject_id') ? 'true' : 'false' }}" aria-describedby="subject_id-error">
                <option value="">-- Pilih Mapel --</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}" @selected(old('subject_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('subject_id')<p id="subject_id-error" class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="category" class="label">Kategori <span class="text-danger">*</span></label>
            <select name="category" id="category" class="input @error('category') border-danger @enderror" aria-invalid="{{ $errors->has('category') ? 'true' : 'false' }}" aria-describedby="category-error">
                <option value="">-- Pilih Kategori --</option>
                <option value="tugas" @selected(old('category') === 'tugas')>Tugas</option>
                <option value="formatif" @selected(old('category') === 'formatif')>Formatif / UH</option>
                <option value="uts" @selected(old('category') === 'uts')>UTS</option>
                <option value="uas" @selected(old('category') === 'uas')>UAS</option>
            </select>
            @error('category')<p id="category-error" class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label for="title" class="label">Judul <span class="text-danger">*</span></label>
        <input type="text" id="title" name="title" value="{{ old('title') }}" maxlength="255" placeholder="contoh: UTS Matematika Kelas 7A"
            class="input @error('title') border-danger @enderror" aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}" aria-describedby="title-error">
        @error('title')<p id="title-error" class="error-text">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="rombel_id" class="label">Rombel (opsional)</label>
            <select name="rombel_id" id="rombel_id" class="input">
                <option value="">-- Semua Rombel --</option>
                @foreach($rombels as $r)
                    <option value="{{ $r->id }}" @selected(old('rombel_id') == $r->id)>[{{ $r->grade_level }}] {{ $r->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="date" class="label">Tanggal</label>
            <input type="date" id="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="input">
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="max_score" class="label">Nilai Maks <span class="text-danger">*</span></label>
            <input type="number" id="max_score" name="max_score" value="{{ old('max_score', 100) }}" min="1" max="1000"
                class="input @error('max_score') border-danger @enderror" aria-invalid="{{ $errors->has('max_score') ? 'true' : 'false' }}" aria-describedby="max_score-error">
            @error('max_score')<p id="max_score-error" class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="weight_percentage" class="label">Bobot (%)</label>
            <input type="number" id="weight_percentage" name="weight_percentage" value="{{ old('weight_percentage') }}" min="0" max="100" class="input">
        </div>
    </div>
    <p class="text-xs text-text-muted">Bobot acuan Kurikulum: Tugas 20% &middot; Formatif 30% &middot; UTS 25% &middot; UAS 25%.</p>
    <div>
        <label for="description" class="label">Deskripsi</label>
        <textarea id="description" name="description" rows="2" maxlength="1000" placeholder="deskripsi penilaian (opsional)"
            class="input">{{ old('description') }}</textarea>
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('assessments.index') }}" class="link ml-auto">Batal</a>
    </div>
</form>
@endsection