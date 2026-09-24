@extends('layouts.app')

@section('title', 'Tambah Guru')
@section('content')
<x-page-head
    eyebrow="Data Guru"
    title="Tambah Guru"
    description="Lengkapi data guru dan tenaga pendidik."
>
    <x-slot:actions>
        <a href="{{ route('teachers.index') }}" class="btn btn-ghost btn-sm">Kembali ke daftar</a>
    </x-slot:actions>
</x-page-head>

<form method="POST" action="{{ route('teachers.store') }}" class="max-w-3xl panel p-6 sm:p-8 space-y-6">
    @csrf

    <div>
        <label for="name" class="label">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name') }}"
            class="input @error('name') border-danger @enderror" @error('name') aria-invalid="true" @enderror>
        @error('name')
            <p class="error-text">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="nuptk" class="label">NUPTK</label>
            <input type="text" id="nuptk" name="nuptk" value="{{ old('nuptk') }}" maxlength="16"
                class="input @error('nuptk') border-danger @enderror" @error('nuptk') aria-invalid="true" @enderror>
            @error('nuptk')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="nip" class="label">NIP</label>
            <input type="text" id="nip" name="nip" value="{{ old('nip') }}" maxlength="18" class="input">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
            <label for="subject_id" class="label">Mata Pelajaran Utama</label>
            <select id="subject_id" name="subject_id" class="input @error('subject_id') border-danger @enderror" @error('subject_id') aria-invalid="true" @enderror>
                <option value="">-- Pilih --</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected((string) old('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
            <label for="subject_text" class="label mt-4">Mapel lain (catatan bebas)</label>
            <input type="text" id="subject_text" name="subject_text" value="{{ old('subject_text') }}" maxlength="100" class="input">
            @error('subject_id')
                <p class="error-text">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="employment_status" class="label">Status Kepegawaian <span class="text-danger">*</span></label>
            <select id="employment_status" name="employment_status" class="input @error('employment_status') border-danger @enderror" @error('employment_status') aria-invalid="true" @enderror>
                <option value="">-- Pilih --</option>
                <option value="gty" @selected(old('employment_status') === 'gty')>GTY (Guru Tetap Yayasan)</option>
                <option value="ptt" @selected(old('employment_status') === 'ptt')>PTT (Guru Tidak Tetap)</option>
                <option value="asn" @selected(old('employment_status') === 'asn')>ASN</option>
            </select>
            @error('employment_status')
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
        <button type="submit" class="btn btn-primary">Simpan Guru</button>
        <a href="{{ route('teachers.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection