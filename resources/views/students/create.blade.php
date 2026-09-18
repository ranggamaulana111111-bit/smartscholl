@extends('layouts.app')

@section('title', 'Tambah Siswa')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-forest-950">Tambah Siswa</h1>
    <p class="text-sm text-forest-700/70 mt-1">Lengkapi data pokok siswa.</p>
</div>

<form method="POST" action="{{ route('students.store') }}" class="max-w-2xl bg-paper-50 border border-forest-100 rounded-md p-6 space-y-5">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="nisn" class="block text-sm font-medium text-forest-800 mb-1">NISN <span class="text-red-500">*</span></label>
            <input type="text" id="nisn" name="nisn" value="{{ old('nisn') }}" maxlength="10"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('nisn') border-red-400 @enderror">
            @error('nisn')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="nis" class="block text-sm font-medium text-forest-800 mb-1">NIS</label>
            <input type="text" id="nis" name="nis" value="{{ old('nis') }}" maxlength="20"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
        </div>
    </div>

    <div>
        <label for="rfid_uid" class="block text-sm font-medium text-forest-800 mb-1">UID RFID</label>
        <input type="text" id="rfid_uid" name="rfid_uid" value="{{ old('rfid_uid') }}" maxlength="50"
            placeholder="mis. 04A2B3C4D5"
            class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none font-mono @error('rfid_uid') border-red-400 @enderror">
        <p class="mt-1 text-xs text-forest-700/60">Opsional. Kode unik kartu RFID siswa (huruf/angka tanpa spasi).</p>
        @error('rfid_uid')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

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
            <label for="gender" class="block text-sm font-medium text-forest-800 mb-1">Jenis Kelamin <span class="text-red-500">*</span></label>
            <select id="gender" name="gender" class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('gender') border-red-400 @enderror">
                <option value="">-- Pilih --</option>
                <option value="L" @selected(old('gender') === 'L')>Laki-laki</option>
                <option value="P" @selected(old('gender') === 'P')>Perempuan</option>
            </select>
            @error('gender')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="rombel_id" class="block text-sm font-medium text-forest-800 mb-1">Rombel</label>
            <select id="rombel_id" name="rombel_id" class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
                <option value="">-- Belum ada --</option>
                @foreach($rombels as $rombel)
                    <option value="{{ $rombel->id }}" @selected(old('rombel_id') == $rombel->id)>[{{ $rombel->grade_level }}] {{ $rombel->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="birth_place" class="block text-sm font-medium text-forest-800 mb-1">Tempat Lahir</label>
            <input type="text" id="birth_place" name="birth_place" value="{{ old('birth_place') }}" maxlength="100"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
        </div>
        <div>
            <label for="birth_date" class="block text-sm font-medium text-forest-800 mb-1">Tanggal Lahir <span class="text-red-500">*</span></label>
            <input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date') }}"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('birth_date') border-red-400 @enderror">
            @error('birth_date')
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
        <a href="{{ route('students.index') }}" class="px-5 py-2 text-sm text-forest-700 hover:text-forest-900 transition-colors">Batal</a>
    </div>
</form>
@endsection