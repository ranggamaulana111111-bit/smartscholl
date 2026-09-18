@extends('layouts.app')

@section('title', 'Tambah Guru')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-forest-950">Tambah Guru</h1>
    <p class="text-sm text-forest-700/70 mt-1">Lengkapi data guru dan tenaga pendidik.</p>
</div>

<form method="POST" action="{{ route('teachers.store') }}" class="max-w-2xl bg-paper-50 border border-forest-100 rounded-md p-6 space-y-5">
    @csrf

    <div>
        <label for="name" class="block text-sm font-medium text-forest-800 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name') }}"
            class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('name') border-red-400 @enderror">
        @error('name')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="nuptk" class="block text-sm font-medium text-forest-800 mb-1">NUPTK</label>
            <input type="text" id="nuptk" name="nuptk" value="{{ old('nuptk') }}" maxlength="16"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('nuptk') border-red-400 @enderror">
            @error('nuptk')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="nip" class="block text-sm font-medium text-forest-800 mb-1">NIP</label>
            <input type="text" id="nip" name="nip" value="{{ old('nip') }}" maxlength="18"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="subject_id" class="block text-sm font-medium text-forest-800 mb-1">Mata Pelajaran Utama</label>
            <select id="subject_id" name="subject_id" class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('subject_id') border-red-400 @enderror">
                <option value="">-- Pilih --</option>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected((string) old('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
            <label for="subject_text" class="block text-xs text-forest-600 mt-2 mb-1">Mapel lain (catatan bebas, opsional)</label>
            <input type="text" id="subject_text" name="subject_text" value="{{ old('subject_text') }}" maxlength="100"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
            @error('subject_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="employment_status" class="block text-sm font-medium text-forest-800 mb-1">Status Kepegawaian <span class="text-red-500">*</span></label>
            <select id="employment_status" name="employment_status" class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('employment_status') border-red-400 @enderror">
                <option value="">-- Pilih --</option>
                <option value="gty" @selected(old('employment_status') === 'gty')>GTY (Guru Tetap Yayasan)</option>
                <option value="ptt" @selected(old('employment_status') === 'ptt')>PTT (Guru Tidak Tetap)</option>
                <option value="asn" @selected(old('employment_status') === 'asn')>ASN</option>
            </select>
            @error('employment_status')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label for="address" class="block text-sm font-medium text-forest-800 mb-1">Alamat</label>
        <textarea id="address" name="address" rows="2" class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">{{ old('address') }}</textarea>
    </div>

    <div>
        <label for="phone" class="block text-sm font-medium text-forest-800 mb-1">No. HP / Telepon</label>
        <input type="text" id="phone" name="phone" value="{{ old('phone') }}" maxlength="20"
            class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="px-5 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">Simpan</button>
        <a href="{{ route('teachers.index') }}" class="px-5 py-2 text-sm text-forest-700 hover:text-forest-900 transition-colors">Batal</a>
    </div>
</form>
@endsection