@extends('layouts.app')

@section('title', 'Edit Jurnal KBM')
@section('content')
<x-page-head title="Edit Jurnal KBM"
    description="Perbaiki catatan kegiatan belajar mengajar."
    eyebrow="Dokumentasi">
</x-page-head>
<form method="POST" action="{{ route('journals.update', $journal) }}" class="panel max-w-2xl p-6 space-y-5">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="date" class="label">Tanggal</label>
            <input type="date" id="date" name="date" value="{{ old('date', $journal->date?->toDateString()) }}" max="{{ now()->toDateString() }}"
                class="input @error('date') border-danger @enderror" aria-invalid="{{ $errors->has('date') ? 'true' : 'false' }}" aria-describedby="date-error">
            @error('date')<p id="date-error" class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="schedule_id" class="label">Jadwal (opsional)</label>
            <select name="schedule_id" id="schedule_id" class="input">
                <option value="">-- Tanpa jadwal --</option>
                @foreach($schedules as $s)
                    <option value="{{ $s->id }}" @selected(old('schedule_id', $journal->schedule_id) == $s->id)>
                        {{ \Carbon\Carbon::day($s->day_of_week)->translatedFormat('l') }} {{ $s->start_time?->format('H:i') }} - {{ $s->subject->name }} ({{ $s->rombel->name }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="subject_id" class="label">Mata Pelajaran</label>
            <select name="subject_id" id="subject_id" class="input">
                <option value="">-- Pilih --</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}" @selected(old('subject_id', $journal->subject_id) == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="rombel_id" class="label">Rombel <span class="text-danger">*</span></label>
            <select name="rombel_id" id="rombel_id" class="input @error('rombel_id') border-danger @enderror" aria-invalid="{{ $errors->has('rombel_id') ? 'true' : 'false' }}" aria-describedby="rombel_id-error">
                <option value="">-- Pilih --</option>
                @foreach($rombels as $r)
                    <option value="{{ $r->id }}" @selected(old('rombel_id', $journal->rombel_id) == $r->id)>[{{ $r->grade_level }}] {{ $r->name }}</option>
                @endforeach
            </select>
            @error('rombel_id')<p id="rombel_id-error" class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div>
        <label for="topic" class="label">Topik / Materi <span class="text-danger">*</span></label>
        <input type="text" id="topic" name="topic" value="{{ old('topic', $journal->topic) }}" maxlength="255"
            class="input @error('topic') border-danger @enderror" aria-invalid="{{ $errors->has('topic') ? 'true' : 'false' }}" aria-describedby="topic-error">
        @error('topic')<p id="topic-error" class="error-text">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="notes" class="label">Catatan</label>
        <textarea id="notes" name="notes" rows="3" maxlength="1000"
            class="input">{{ old('notes', $journal->notes) }}</textarea>
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" name="status" value="draft" class="btn btn-ghost">Simpan Draft</button>
        <button type="submit" name="status" value="closed" class="btn btn-primary">Simpan & Tutup</button>
        <a href="{{ route('journals.index') }}" class="link ml-auto">Batal</a>
    </div>
</form>
@endsection