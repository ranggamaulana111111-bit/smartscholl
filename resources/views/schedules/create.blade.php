@extends('layouts.app')

@section('title', 'Tambah Jadwal Mengajar')
@section('content')
<x-page-head title="Tambah Jadwal Mengajar"
    description="Atur jadwal kelas untuk tenaga pengajar."
    eyebrow="Penjadwalan">
</x-page-head>
<form method="POST" action="{{ route('schedules.store') }}" class="panel max-w-2xl p-6 space-y-5">
    @csrf
    @if($year)
        <input type="hidden" name="academic_year_id" value="{{ old('academic_year_id', $year->id) }}">
    @endif
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="teacher_id" class="label">Guru <span class="text-danger">*</span></label>
            <select name="teacher_id" id="teacher_id" class="input @error('teacher_id') border-danger @enderror" aria-invalid="{{ $errors->has('teacher_id') ? 'true' : 'false' }}" aria-describedby="teacher_id-error" data-schedule-teacher-select required>
                <option value="">-- Pilih Guru --</option>
                @forelse($teachers as $teacher)
                    <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }} ({{ $teacher->subject->name }})</option>
                @empty
                    <option value="" disabled>Belum ada data guru</option>
                @endforelse
            </select>
            @error('teacher_id')<p id="teacher_id-error" class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="subject_id" class="label">Mata Pelajaran <span class="text-danger">*</span></label>
            <select name="subject_id" id="subject_id" class="input @error('subject_id') border-danger @enderror" aria-invalid="{{ $errors->has('subject_id') ? 'true' : 'false' }}" aria-describedby="subject_id-error" data-schedule-subject-select required>
                <option value="">-- Pilih Mapel --</option>
                @forelse($subjects as $subject)
                    <option value="{{ $subject->id }}" data-teacher-ids="{{ $teachers->where('subject_id', $subject->id)->pluck('id')->join(' ') }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>
                @empty
                    <option value="" disabled>Belum ada mata pelajaran</option>
                @endforelse
            </select>
            @error('subject_id')<p id="subject_id-error" class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="rombel_id" class="label">Rombel <span class="text-danger">*</span></label>
            <select name="rombel_id" id="rombel_id" class="input @error('rombel_id') border-danger @enderror" aria-invalid="{{ $errors->has('rombel_id') ? 'true' : 'false' }}" aria-describedby="rombel_id-error">
                <option value="">-- Pilih Rombel --</option>
                @foreach($rombels as $r)
                    <option value="{{ $r->id }}" @selected(old('rombel_id') == $r->id)>[{{ $r->grade_level }}] {{ $r->name }}</option>
                @endforeach
            </select>
            @error('rombel_id')<p id="rombel_id-error" class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="day_of_week" class="label">Hari <span class="text-danger">*</span></label>
            <select name="day_of_week" id="day_of_week" class="input @error('day_of_week') border-danger @enderror" aria-invalid="{{ $errors->has('day_of_week') ? 'true' : 'false' }}" aria-describedby="day_of_week-error">
                <option value="">-- Pilih Hari --</option>
                @for($d=1; $d<=7; $d++)
                    <option value="{{ $d }}" @selected(old('day_of_week') == $d)>{{ \Carbon\Carbon::day($d)->translatedFormat('l') }}</option>
                @endfor
            </select>
            @error('day_of_week')<p id="day_of_week-error" class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label for="start_time" class="label">Jam Mulai <span class="text-danger">*</span></label>
            <input type="time" id="start_time" name="start_time" value="{{ old('start_time') }}"
                class="input @error('start_time') border-danger @enderror" aria-invalid="{{ $errors->has('start_time') ? 'true' : 'false' }}" aria-describedby="start_time-error">
            @error('start_time')<p id="start_time-error" class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="end_time" class="label">Jam Selesai <span class="text-danger">*</span></label>
            <input type="time" id="end_time" name="end_time" value="{{ old('end_time') }}"
                class="input @error('end_time') border-danger @enderror" aria-invalid="{{ $errors->has('end_time') ? 'true' : 'false' }}" aria-describedby="end_time-error">
            @error('end_time')<p id="end_time-error" class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="{{ route('schedules.index') }}" class="link ml-auto">Batal</a>
    </div>
</form>
@endsection
