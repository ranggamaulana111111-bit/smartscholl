@extends('layouts.app')

@section('title', 'Tambah Rombel')
@section('content')
<x-page-head
    eyebrow="Data Rombel"
    title="Tambah Rombel"
    description="Kelompok belajar siswa dalam satu tingkat dan tahun ajaran."
>
    <x-slot:actions>
        <a href="{{ route('rombels.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
    </x-slot:actions>
</x-page-head>

<form method="POST" action="{{ route('rombels.store') }}" class="max-w-3xl panel p-6 sm:p-8 space-y-6">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="academic_year_id" class="label">Tahun Ajaran <span class="text-danger">*</span></label>
            <select id="academic_year_id" name="academic_year_id" class="input @error('academic_year_id') border-danger @enderror" @error('academic_year_id') aria-invalid="true" @enderror>
                <option value="">-- Pilih --</option>
                @foreach($academicYears as $year)
                    <option value="{{ $year->id }}" @selected(old('academic_year_id') == $year->id)>
                        {{ $year->name }} ({{ $year->semester }}){{ $year->is_active ? ' - aktif' : '' }}
                    </option>
                @endforeach
            </select>
            @error('academic_year_id')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="grade_level" class="label">Tingkat Kelas <span class="text-danger">*</span></label>
            <input type="text" id="grade_level" name="grade_level" value="{{ old('grade_level') }}" maxlength="10" placeholder="contoh: X"
                class="input @error('grade_level') border-danger @enderror" @error('grade_level') aria-invalid="true" @enderror>
            @error('grade_level')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="name" class="label">Nama Rombel <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" maxlength="50" placeholder="contoh: X-1"
            class="input @error('name') border-danger @enderror" @error('name') aria-invalid="true" @enderror>
        @error('name')
            <p class="error-text">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="homeroom_teacher_id" class="label">Wali Kelas</label>
        <select id="homeroom_teacher_id" name="homeroom_teacher_id" class="input @error('homeroom_teacher_id') border-danger @enderror" @error('homeroom_teacher_id') aria-invalid="true" @enderror>
            <option value="">-- Pilih --</option>
            @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected(old('homeroom_teacher_id') == $teacher->id)>{{ $teacher->name }}@if($teacher->subject) ({{ $teacher->subject->name }})@endif</option>
            @endforeach
        </select>
        @error('homeroom_teacher_id')
            <p class="error-text">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center gap-3 pt-2 border-t border-border">
        <button type="submit" class="btn btn-primary">Simpan Rombel</button>
        <a href="{{ route('rombels.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection