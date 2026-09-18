@extends('layouts.app')

@section('title', 'Edit Penilaian')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">Edit Penilaian</h1>
</div>
<form method="POST" action="{{ route('assessments.update', $assessment) }}" class="card max-w-2xl p-6 space-y-5">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Mata Pelajaran <span class="text-danger">*</span></label>
            <select name="subject_id" class="input @error('subject_id') border-danger @enderror">
                @foreach(\App\Models\Subject::where('is_active', true)->orderBy('name')->get() as $s)
                    <option value="{{ $s->id }}" @selected(old('subject_id', $assessment->subject_id) == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('subject_id')<p class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Kategori <span class="text-danger">*</span></label>
            <select name="category" class="input @error('category') border-danger @enderror">
                <option value="tugas" @selected(old('category', $assessment->category) === 'tugas')>Tugas</option>
                <option value="formatif" @selected(old('category', $assessment->category) === 'formatif')>Formatif / UH</option>
                <option value="uts" @selected(old('category', $assessment->category) === 'uts')>UTS</option>
                <option value="uas" @selected(old('category', $assessment->category) === 'uas')>UAS</option>
            </select>
            @error('category')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label class="label">Judul <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title', $assessment->title) }}" maxlength="255"
            class="input @error('title') border-danger @enderror">
        @error('title')<p class="error-text">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <label class="label">Nilai Maks</label>
            <input type="number" name="max_score" value="{{ old('max_score', $assessment->max_score) }}" min="1" max="1000"
                class="input">
        </div>
        <div>
            <label class="label">Bobot (%)</label>
            <input type="number" name="weight_percentage" value="{{ old('weight_percentage', $assessment->weight_percentage) }}" min="0" max="100"
                class="input">
        </div>
        <div>
            <label class="label">Tanggal</label>
            <input type="date" name="date" value="{{ old('date', $assessment->date?->toDateString()) }}"
                class="input">
        </div>
    </div>
    <div>
        <label class="label">Deskripsi</label>
        <textarea name="description" rows="2" maxlength="1000"
            class="input">{{ old('description', $assessment->description) }}</textarea>
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn btn-primary">Perbarui</button>
        <a href="{{ route('assessments.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection
