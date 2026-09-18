@extends('layouts.app')

@section('title', 'Tambah Mata Pelajaran')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">Tambah Mata Pelajaran</h1>
    <p class="text-sm text-text-muted mt-1">Isi data mata pelajaran baru.</p>
</div>
<form method="POST" action="{{ route('subjects.store') }}" class="card max-w-lg p-6 space-y-5">
    @csrf
    <div>
        <label for="name" class="label">Nama <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="100"
            class="input @error('name') border-danger @enderror">
        @error('name')<p class="error-text">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="code" class="label">Kode</label>
        <input type="text" id="code" name="code" value="{{ old('code') }}" maxlength="20"
            class="input">
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('subjects.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection
