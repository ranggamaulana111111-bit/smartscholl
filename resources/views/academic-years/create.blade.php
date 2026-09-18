@extends('layouts.app')

@section('title', 'Tambah Tahun Ajaran')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-forest-950">Tambah Tahun Ajaran</h1>
    <p class="text-sm text-forest-700/70 mt-1">Satu tahun ajaran bisa berisi dua semester.</p>
</div>

<form method="POST" action="{{ route('academic-years.store') }}" class="max-w-2xl bg-paper-50 border border-forest-100 rounded-md p-6 space-y-5">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="name" class="block text-sm font-medium text-forest-800 mb-1">Nama Tahun Ajaran <span class="text-red-500">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="20" placeholder="contoh: 2027/2028"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('name') border-red-400 @enderror">
            @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="semester" class="block text-sm font-medium text-forest-800 mb-1">Semester <span class="text-red-500">*</span></label>
            <select id="semester" name="semester" class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('semester') border-red-400 @enderror">
                <option value="">-- Pilih --</option>
                <option value="ganjil" @selected(old('semester') === 'ganjil')>Ganjil</option>
                <option value="genap" @selected(old('semester') === 'genap')>Genap</option>
            </select>
            @error('semester')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="start_date" class="block text-sm font-medium text-forest-800 mb-1">Tanggal Mulai <span class="text-red-500">*</span></label>
            <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('start_date') border-red-400 @enderror">
            @error('start_date')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="end_date" class="block text-sm font-medium text-forest-800 mb-1">Tanggal Selesai <span class="text-red-500">*</span></label>
            <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('end_date') border-red-400 @enderror">
            @error('end_date')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="flex items-center gap-2 text-sm text-forest-800 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="w-4 h-4 rounded border-forest-200 text-forest-800 focus:ring-forest-500">
            Jadikan tahun ajaran aktif
        </label>
        @error('is_active')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="px-5 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">Simpan</button>
        <a href="{{ route('academic-years.index') }}" class="px-5 py-2 text-sm text-forest-700 hover:text-forest-900 transition-colors">Batal</a>
    </div>
</form>
@endsection