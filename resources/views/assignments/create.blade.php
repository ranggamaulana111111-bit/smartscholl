@extends('layouts.app')

@section('title', 'Buat Tugas')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">Buat Tugas / PR</h1>
    <p class="text-sm text-text-muted mt-1">Beri batas waktu pengumpulan dan lampirkan berkas acuan jika perlu.</p>
</div>
<form method="POST" action="{{ route('assignments.store') }}" class="card max-w-2xl p-6 space-y-5" enctype="multipart/form-data">
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
            <label class="label">Rombel <span class="text-danger">*</span></label>
            <select name="rombel_id" class="input @error('rombel_id') border-danger @enderror">
                <option value="">-- Pilih Rombel --</option>
                @foreach($rombels as $r)
                    <option value="{{ $r->id }}" @selected(old('rombel_id') == $r->id)>[{{ $r->grade_level }}] {{ $r->name }}</option>
                @endforeach
            </select>
            @error('rombel_id')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label class="label">Judul Tugas <span class="text-danger">*</span></label>
        <input type="text" name="title" value="{{ old('title') }}" maxlength="255" placeholder="contoh: PR Persamaan Linear"
            class="input @error('title') border-danger @enderror">
        @error('title')<p class="error-text">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="label">Deskripsi</label>
        <textarea name="description" rows="3" maxlength="2000" placeholder="rincian tugas..."
            class="input">{{ old('description') }}</textarea>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Batas Waktu <span class="text-danger">*</span></label>
            <input type="datetime-local" name="deadline_at" value="{{ old('deadline_at', now()->addDays(3)->format('Y-m-d\TH:i')) }}"
                class="input @error('deadline_at') border-danger @enderror">
            @error('deadline_at')<p class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Berkas Acuan (opsional)</label>
            <input type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt"
                class="input @error('attachment') border-danger @enderror">
            @error('attachment')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn btn-primary">Simpan Tugas</button>
        <a href="{{ route('assignments.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection