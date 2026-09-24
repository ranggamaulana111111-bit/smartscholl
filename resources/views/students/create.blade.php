@extends('layouts.app')

@section('title', 'Tambah Siswa')
@section('content')
<x-page-head
    eyebrow="Data Siswa"
    title="Tambah Siswa"
    description="Lengkapi data pokok siswa."
>
    <x-slot:actions>
        <a href="{{ route('students.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
    </x-slot:actions>
</x-page-head>

<form method="POST" action="{{ route('students.store') }}" class="max-w-3xl panel p-6 sm:p-8 space-y-6">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="nisn" class="label">NISN <span class="text-danger">*</span></label>
            <input type="text" id="nisn" name="nisn" value="{{ old('nisn') }}" maxlength="10"
                class="input @error('nisn') border-danger @enderror" @error('nisn') aria-invalid="true" @enderror>
            @error('nisn')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="nis" class="label">NIS</label>
            <input type="text" id="nis" name="nis" value="{{ old('nis') }}" maxlength="20" class="input">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="rfid_uid" class="label">UID RFID</label>
            <input type="text" id="rfid_uid" name="rfid_uid" value="{{ old('rfid_uid') }}" maxlength="50"
                placeholder="mis. 04A2B3C4D5"
                class="input font-mono @error('rfid_uid') border-danger @enderror" @error('rfid_uid') aria-invalid="true" @enderror>
            <p class="mt-1.5 text-xs text-text-muted">Opsional. Kode unik kartu RFID siswa.</p>
            @error('rfid_uid')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="name" class="label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name') }}"
                class="input @error('name') border-danger @enderror" @error('name') aria-invalid="true" @enderror>
            @error('name')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="gender" class="label">Jenis Kelamin <span class="text-danger">*</span></label>
            <select id="gender" name="gender" class="input @error('gender') border-danger @enderror" @error('gender') aria-invalid="true" @enderror>
                <option value="">-- Pilih --</option>
                <option value="L" @selected(old('gender') === 'L')>Laki-laki</option>
                <option value="P" @selected(old('gender') === 'P')>Perempuan</option>
            </select>
            @error('gender')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="rombel_id" class="label">Rombel</label>
            <select id="rombel_id" name="rombel_id" class="input">
                <option value="">-- Belum ada --</option>
                @foreach($rombels as $rombel)
                    <option value="{{ $rombel->id }}" @selected(old('rombel_id') == $rombel->id)>[{{ $rombel->grade_level }}] {{ $rombel->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="birth_place" class="label">Tempat Lahir</label>
            <input type="text" id="birth_place" name="birth_place" value="{{ old('birth_place') }}" maxlength="100" class="input">
        </div>
        <div>
            <label for="birth_date" class="label">Tanggal Lahir <span class="text-danger">*</span></label>
            <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date') }}"
                class="input @error('birth_date') border-danger @enderror" @error('birth_date') aria-invalid="true" @enderror>
            @error('birth_date')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="address" class="label">Alamat</label>
        <textarea id="address" name="address" rows="2" class="input">{{ old('address') }}</textarea>
    </div>

    <div>
        <label for="phone" class="label">No. HP / Telepon</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone') }}" maxlength="20" class="input">
    </div>

    <div class="flex items-center gap-3 pt-2 border-t border-border">
        <button type="submit" class="btn btn-primary">Simpan Siswa</button>
        <a href="{{ route('students.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection