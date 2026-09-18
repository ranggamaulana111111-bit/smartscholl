@extends('layouts.app')

@section('title', 'Absen Manual')
@section('content')
<div class="max-w-xl">
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold text-forest-950">Absen Manual</h1>
        <p class="text-sm text-forest-700/70 mt-1">Atur status kehadiran siswa untuk tanggal tertentu.</p>
    </div>

    @if(session('success'))
        <div class="mb-5 p-4 bg-forest-50 border border-forest-200 rounded-md text-sm text-forest-800">
            {{ session('success') }}
        </div>
    @endif

    @if(auth()->user()->hasRole('guru'))
        <div class="mb-5 p-4 bg-paper-100 border border-forest-100 rounded-md text-xs text-forest-800">
            Hanya siswa di rombel binaan Anda yang ditampilkan.
        </div>
    @endif

    @if($students->isEmpty())
        <div class="mb-5 p-8 text-center bg-paper-100 border border-forest-100 rounded-md">
            <p class="text-sm text-forest-800 font-medium">Belum ada siswa yang bisa diisi.</p>
            <p class="text-xs text-forest-700/70 mt-1">Tidak ada rombel binaan, atau belum ada siswa terdaftar.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('attendance.storeManual') }}" class="bg-paper-50 border border-forest-100 rounded-md p-6 space-y-5">
        @csrf

        <div>
            <label for="student_id" class="block text-sm font-medium text-forest-800 mb-1">Siswa <span class="text-red-500">*</span></label>
            <select id="student_id" name="student_id" required
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('student_id') border-red-400 @enderror">
                <option value="">-- Pilih Siswa --</option>
                @foreach($students as $student)
                    <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                        {{ $student->name }} ({{ $student->nisn }}) - {{ $student->rombel?->name ?? '-' }}
                    </option>
                @endforeach
            </select>
            @error('student_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label for="type" class="block text-sm font-medium text-forest-800 mb-1">Jenis Absensi <span class="text-red-500">*</span></label>
                <select id="type" name="type" required class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
                    <option value="gate_in" @selected(old('type') === 'gate_in')>Masuk Gerbang</option>
                    <option value="gate_out" @selected(old('type') === 'gate_out')>Pulang Gerbang</option>
                    <option value="lesson" @selected(old('type') === 'lesson')>Hadir Pelajaran</option>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-forest-800 mb-1">Status <span class="text-red-500">*</span></label>
                <select id="status" name="status" required class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
                    <option value="hadir" @selected(old('status', 'hadir') === 'hadir')>Hadir</option>
                    <option value="sakit" @selected(old('status') === 'sakit')>Sakit</option>
                    <option value="izin" @selected(old('status') === 'izin')>Izin</option>
                    <option value="alpha" @selected(old('status') === 'alpha')>Alpha</option>
                </select>
            </div>
        </div>

        <div>
            <label for="date" class="block text-sm font-medium text-forest-800 mb-1">Tanggal <span class="text-red-500">*</span></label>
            <input type="date" id="date" name="date" value="{{ old('date', now()->toDateString()) }}" required
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none @error('date') border-red-400 @enderror">
            @error('date')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="schedule_id" class="block text-sm font-medium text-forest-800 mb-1">Jadwal Pelajaran</label>
            <select name="schedule_id" id="schedule_id"
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
                <option value="">-- Pilih jadwal --</option>
                @foreach($schedules as $sched)
                    <option value="{{ $sched->id }}" @selected(old('schedule_id') == $sched->id)>
                        {{ \Carbon\Carbon::day($sched->day_of_week)->translatedFormat('l') }} {{ substr($sched->start_time, 0, 5) }} - {{ $sched->subject->name }} ({{ $sched->rombel->name }}), {{ $sched->teacher->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-forest-700/60">Wajib dipilih bila jenis = Hadir Pelajaran, dan harus sesuai tanggal.</p>
        </div>

        <div>
            <label for="note" class="block text-sm font-medium text-forest-800 mb-1">Catatan</label>
            <textarea id="note" name="note" rows="2" class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">{{ old('note') }}</textarea>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="px-5 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">Simpan</button>
            <a href="{{ route('attendance.index') }}" class="px-5 py-2 text-sm text-forest-700 hover:text-forest-900 transition-colors">Batal</a>
        </div>
    </form>
</div>
@endsection