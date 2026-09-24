@extends('layouts.app')

@section('title', 'Tambah Mata Pelajaran')
@section('content')
<x-page-head
    eyebrow="Data Mata Pelajaran"
    title="Tambah Mata Pelajaran"
    description="Isi data mata pelajaran baru."
>
    <x-slot:actions>
        <a href="{{ route('subjects.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
    </x-slot:actions>
</x-page-head>

<form method="POST" action="{{ route('subjects.store') }}" class="panel max-w-lg p-6 sm:p-8 space-y-6">
    @csrf
    <div>
        <label for="name" class="label">Nama <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="100"
            class="input @error('name') border-danger @enderror" @error('name') aria-invalid="true" @enderror>
        @error('name')<p class="error-text">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="code" class="label">Kode</label>
        <input type="text" id="code" name="code" value="{{ old('code') }}" maxlength="20" class="input">
    </div>
    <div>
        <label for="is_active" class="flex items-center gap-2 text-sm font-medium text-text">
            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="w-4 h-4 rounded border-border text-text focus:ring-accent">
            Aktif
        </label>
    </div>
    <div class="flex items-center gap-3 pt-2 border-t border-border">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('subjects.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection