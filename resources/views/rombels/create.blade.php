@extends('layouts.app')

@section('title', 'Tambah Rombel')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-forest-950">Tambah Rombel</h1>
    <p class="text-sm text-forest-700/70 mt-1">Kelompok belajar siswa dalam satu tingkat dan tahun ajaran.</p>
</div>

<form method="POST" action="{{ route('rombels.store') }}" class="max-w-2xl bg-paper-50 border border-forest-100 rounded-md p-6 space-y-5">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="academic_year_id" class="block text-sm font-medium text-forest-800 mb-1">Tahun Ajaran <span class="text-red-500">*</span></label>
            <select id="academic_year_id" name="academic_year_id" class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('academic_year_id') border-red-400 @enderror">
                <option value="">-- Pilih --</option>
                @foreach($academicYears as $year)
                    <option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>
                        {{ $year->name }} ({{ $year->semester }}){{ $year->is_active ? ' - aktif' : '' }}
                    </option>
                @endforeach
            </select>
            @error('academic_year_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="grade_level" class="block text-sm font-medium text-forest-800 mb-1">Tingkat Kelas <span class="text-red-500">*</span></label>
            <input type="text" id="grade_level" name="grade_level" value="{{ old('grade_level') }}" maxlength="10" placeholder="contoh: X"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('grade_level') border-red-400 @enderror">
            @error('grade_level')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="name" class="block text-sm font-medium text-forest-800 mb-1">Nama Rombel <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="50" placeholder="contoh: X-1"
            class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('name') border-red-400 @enderror">
        @error('name')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="homeroom_teacher_id" class="block text-sm font-medium text-forest-800 mb-1">Wali Kelas</label>
        <select id="homeroom_teacher_id" name="homeroom_teacher_id" class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('homeroom_teacher_id') border-red-400 @enderror">
            <option value="">-- Pilih --</option>
            @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected(old('homeroom_teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
            @endforeach
        </select>
        @error('homeroom_teacher_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="px-5 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">Simpan</button>
        <a href="{{ route('rombels.index') }}" class="px-5 py-2 text-sm text-forest-700 hover:text-forest-900 transition-colors">Batal</a>
    </div>
</form>
@endsection