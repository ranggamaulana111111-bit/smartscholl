@extends('layouts.app')

@section('title', 'Isi Jurnal KBM')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">Isi Jurnal KBM</h1>
    <p class="text-sm text-text-muted mt-1">Catat topik, materi, dan status kegiatan pembelajaran hari ini.</p>
</div>
<form method="POST" action="{{ route('journals.store') }}" class="card max-w-2xl p-6 space-y-5">
    @csrf
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Tanggal <span class="text-danger">*</span></label>
            <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                class="input @error('date') border-danger @enderror">
            @error('date')<p class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Jadwal (opsional)</label>
            <select name="schedule_id" class="input">
                <option value="">-- Tanpa jadwal --</option>
                @foreach($schedules as $s)
                    <option value="{{ $s->id }}" @selected(old('schedule_id') == $s->id)>
                        {{ \Carbon\Carbon::day($s->day_of_week)->translatedFormat('l') }} {{ substr($s->start_time,0,5) }} - {{ $s->subject->name }} ({{ $s->rombel->name }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Mata Pelajaran</label>
            <select name="subject_id" class="input">
                <option value="">-- Pilih --</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}" @selected(old('subject_id') == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Rombel <span class="text-danger">*</span></label>
            <select name="rombel_id" class="input @error('rombel_id') border-danger @enderror">
                <option value="">-- Pilih --</option>
                @foreach($rombels as $r)
                    <option value="{{ $r->id }}" @selected(old('rombel_id') == $r->id)>[{{ $r->grade_level }}] {{ $r->name }}</option>
                @endforeach
            </select>
            @error('rombel_id')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label class="label">Topik / Materi <span class="text-danger">*</span></label>
        <input type="text" name="topic" value="{{ old('topic') }}" maxlength="255" placeholder="contoh: Persamaan Linear Dua Variabel"
            class="input @error('topic') border-danger @enderror">
        @error('topic')<p class="error-text">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="label">Catatan Kejadian / Observasi</label>
        <textarea name="notes" rows="3" maxlength="1000" placeholder="catatan untuk sesi ini..."
            class="input">{{ old('notes') }}</textarea>
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" name="status" value="draft" class="btn btn-accent">Simpan Draft</button>
        <button type="submit" name="status" value="closed" class="btn btn-primary">Simpan & Tutup</button>
        <a href="{{ route('journals.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection
