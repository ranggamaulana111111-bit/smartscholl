@extends('layouts.app')

@section('title', 'Buat Tugas')
@section('content')
<x-page-head title="Buat Tugas / PR"
    description="Beri batas waktu pengumpulan dan lampirkan berkas acuan jika perlu."
    eyebrow="Akademik">
</x-page-head>
<form method="POST" action="{{ route('assignments.store') }}" class="panel max-w-2xl p-6 space-y-5" enctype="multipart/form-data">
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
            <label for="rombel_id" class="label">Rombel <span class="text-danger">*</span></label>
            <select name="rombel_id" id="rombel_id" class="input @error('rombel_id') border-danger @enderror" aria-invalid="{{ $errors->has('rombel_id') ? 'true' : 'false' }}" aria-describedby="rombel_id-error">
                <option value="">-- Pilih Rombel --</option>
                @foreach($rombels as $r)
                    <option value="{{ $r->id }}" @selected(old('rombel_id') == $r->id)>[{{ $r->grade_level }}] {{ $r->name }}</option>
                @endforeach
            </select>
            @error('rombel_id')<p id="rombel_id-error" class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label for="title" class="label">Judul Tugas <span class="text-danger">*</span></label>
        <input type="text" id="title" name="title" value="{{ old('title') }}" maxlength="255" placeholder="contoh: PR Persamaan Linear"
            class="input @error('title') border-danger @enderror" aria-invalid="{{ $errors->has('title') ? 'true' : 'false' }}" aria-describedby="title-error">
        @error('title')<p id="title-error" class="error-text">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="description" class="label">Deskripsi</label>
        <textarea id="description" name="description" rows="3" maxlength="2000" placeholder="rincian tugas..." class="input">{{ old('description') }}</textarea>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="deadline_at" class="label">Batas Waktu <span class="text-danger">*</span></label>
            <input type="datetime-local" id="deadline_at" name="deadline_at" value="{{ old('deadline_at', now()->addDays(3)->format('Y-m-d\TH:i')) }}"
                class="input @error('deadline_at') border-danger @enderror" aria-invalid="{{ $errors->has('deadline_at') ? 'true' : 'false' }}" aria-describedby="deadline_at-error">
            @error('deadline_at')<p id="deadline_at-error" class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="attachment" class="label">Berkas Acuan (opsional)</label>
            <input type="file" id="attachment" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt"
                class="input @error('attachment') border-danger @enderror" aria-invalid="{{ $errors->has('attachment') ? 'true' : 'false' }}" aria-describedby="attachment-error">
            @error('attachment')<p id="attachment-error" class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn btn-primary">Simpan Tugas</button>
        <a href="{{ route('assignments.index') }}" class="link ml-auto">Batal</a>
    </div>
</form>
@endsection