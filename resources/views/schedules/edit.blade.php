@extends('layouts.app')

@section('title', 'Edit Jadwal Mengajar')
@section('content')
<div class="mb-6">
    <h1 class="font-display text-2xl font-semibold text-text">Edit Jadwal Mengajar</h1>
</div>
<form method="POST" action="{{ route('schedules.update', $schedule) }}" class="card max-w-2xl p-6 space-y-5">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Guru <span class="text-danger">*</span></label>
            <select name="user_id" class="input @error('user_id') border-danger @enderror">
                @foreach($teachers as $t)
                    <option value="{{ $t->id }}" @selected(old('user_id', $schedule->user_id) == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
            @error('user_id')<p class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Mata Pelajaran <span class="text-danger">*</span></label>
            <select name="subject_id" class="input @error('subject_id') border-danger @enderror">
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}" @selected(old('subject_id', $schedule->subject_id) == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
            @error('subject_id')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Rombel <span class="text-danger">*</span></label>
            <select name="rombel_id" class="input @error('rombel_id') border-danger @enderror">
                @foreach($rombels as $r)
                    <option value="{{ $r->id }}" @selected(old('rombel_id', $schedule->rombel_id) == $r->id)>[{{ $r->grade_level }}] {{ $r->name }}</option>
                @endforeach
            </select>
            @error('rombel_id')<p class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Hari <span class="text-danger">*</span></label>
            <select name="day_of_week" class="input @error('day_of_week') border-danger @enderror">
                @for($d=1; $d<=7; $d++)
                    <option value="{{ $d }}" @selected(old('day_of_week', $schedule->day_of_week) == $d)>{{ \Carbon\Carbon::day($d)->translatedFormat('l') }}</option>
                @endfor
            </select>
            @error('day_of_week')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="label">Jam Mulai <span class="text-danger">*</span></label>
            <input type="time" name="start_time" value="{{ old('start_time', substr($schedule->start_time, 0, 5)) }}"
                class="input @error('start_time') border-danger @enderror">
            @error('start_time')<p class="error-text">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Jam Selesai <span class="text-danger">*</span></label>
            <input type="time" name="end_time" value="{{ old('end_time', substr($schedule->end_time, 0, 5)) }}"
                class="input @error('end_time') border-danger @enderror">
            @error('end_time')<p class="error-text">{{ $message }}</p>@enderror
        </div>
    </div>
    <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="btn btn-primary">Perbarui</button>
        <a href="{{ route('schedules.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection
