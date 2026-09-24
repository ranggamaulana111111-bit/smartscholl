@extends('layouts.app')

@section('title', 'Edit Tahun Ajaran')
@section('content')
<x-page-head
    eyebrow="Data Tahun Ajaran"
    :title="'Edit: ' . $academicYear->name"
    :description="'Semester ' . $academicYear->semester"
>
    <x-slot:actions>
        <a href="{{ route('academic-years.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
    </x-slot:actions>
</x-page-head>

<form method="POST" action="{{ route('academic-years.update', $academicYear) }}" class="max-w-3xl panel p-6 sm:p-8 space-y-6">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="name" class="label">Nama Tahun Ajaran <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $academicYear->name) }}" maxlength="20" placeholder="contoh: 2027/2028"
                class="input @error('name') border-danger @enderror" @error('name') aria-invalid="true" @enderror>
            @error('name')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="semester" class="label">Semester <span class="text-danger">*</span></label>
            <select id="semester" name="semester" class="input @error('semester') border-danger @enderror" @error('semester') aria-invalid="true" @enderror>
                <option value="">-- Pilih --</option>
                <option value="ganjil" @selected(old('semester', $academicYear->semester) === 'ganjil')>Ganjil</option>
                <option value="genap" @selected(old('semester', $academicYear->semester) === 'genap')>Genap</option>
            </select>
            @error('semester')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="start_date" class="label">Tanggal Mulai <span class="text-danger">*</span></label>
            <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $academicYear->start_date?->toDateString()) }}"
                class="input @error('start_date') border-danger @enderror" @error('start_date') aria-invalid="true" @enderror>
            @error('start_date')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="end_date" class="label">Tanggal Selesai <span class="text-danger">*</span></label>
            <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $academicYear->end_date?->toDateString()) }}"
                class="input @error('end_date') border-danger @enderror" @error('end_date') aria-invalid="true" @enderror>
            @error('end_date')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="flex items-center gap-2 text-sm text-text cursor-pointer">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $academicYear->is_active)) class="w-4 h-4 rounded border-border text-text focus:ring-accent">
            Jadikan tahun ajaran aktif
        </label>
        @error('is_active')
            <p class="error-text">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-3 pt-2 border-t border-border">
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        <a href="{{ route('academic-years.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection