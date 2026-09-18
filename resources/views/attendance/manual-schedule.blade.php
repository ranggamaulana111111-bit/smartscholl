@extends('layouts.app')

@section('title', 'Absen Manual: '.$schedule->subject->name)
@section('content')
<div class="max-w-3xl">
    <div class="mb-6">
        <a href="{{ route('attendance.manual') }}" class="text-xs text-forest-700 hover:text-forest-900 transition-colors">&larr; Kembali ke pilihan jadwal</a>
        <h1 class="font-display text-2xl font-semibold text-forest-950 mt-2">Absen Manual Kelas {{ $schedule->rombel->name }}</h1>
        <p class="text-sm text-forest-700/70 mt-1">
            {{ $schedule->subject->name }} &middot;
            Hari {{ \Carbon\Carbon::day($schedule->day_of_week)->translatedFormat('l') }} &middot;
            {{ $schedule->start_time->format('H:i') }}-{{ $schedule->end_time->format('H:i') }} &middot;
            Guru {{ $schedule->teacher->name }}
        </p>
    </div>

    @if(session('success'))
        <div class="mb-5 p-4 bg-forest-50 border border-forest-200 rounded-md text-sm text-forest-800">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <form method="GET" action="{{ route('attendance.manualSchedule', $schedule->id) }}" class="bg-paper-50 border border-forest-100 rounded-md p-4 mb-6 flex items-end gap-3">
        <div class="flex-1">
            <label for="date" class="block text-sm font-medium text-forest-800 mb-1">Tanggal <span class="text-red-500">*</span></label>
            <input type="date" id="date" name="date" value="{{ $date }}" required
                class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
        </div>
        <button type="submit" class="px-5 py-2 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">Tampilkan</button>
    </form>

    @if($students->isEmpty())
        <div class="mb-5 p-8 text-center bg-paper-100 border border-forest-100 rounded-md">
            <p class="text-sm text-forest-800 font-medium">Belum ada siswa di rombel ini.</p>
        </div>
    @else
        <div class="bg-paper-50 border border-forest-100 rounded-md overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-forest-800 text-paper-50">
                        <th class="px-4 py-3 text-left font-medium">Nama Siswa</th>
                        <th class="px-4 py-3 text-left font-medium hidden sm:table-cell">NISN</th>
                        <th class="px-4 py-3 text-left font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        <tr class="border-t border-forest-100">
                            <td class="px-4 py-3">
                                <p class="text-forest-950 font-medium">{{ $student->name }}</p>
                                <p class="text-xs text-forest-700/60 sm:hidden mt-0.5">{{ $student->nisn }}</p>
                            </td>
                            <td class="px-4 py-3 text-forest-700/70 hidden sm:table-cell">{{ $student->nisn }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach(['hadir' => 'Masuk', 'sakit' => 'Sakit', 'izin' => 'Izin', 'alpha' => 'Alpa'] as $status => $label)
                                        <form method="POST" action="{{ route('attendance.storeManual') }}">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
                                            <input type="hidden" name="type" value="lesson">
                                            <input type="hidden" name="status" value="{{ $status }}">
                                            <input type="hidden" name="date" value="{{ $date }}">
                                            <button type="submit"
                                                class="px-3 py-1.5 text-xs rounded-md border transition-colors
                                                    {{ $status === 'hadir'
                                                        ? 'border-forest-700 bg-forest-700 text-paper-50 hover:bg-forest-600'
                                                        : ($status === 'sakit'
                                                            ? 'border-orange-300 bg-orange-100 text-orange-900 hover:bg-orange-200'
                                                            : ($status === 'izin'
                                                                ? 'border-sky-300 bg-sky-100 text-sky-900 hover:bg-sky-200'
                                                                : 'border-red-300 bg-red-100 text-red-900 hover:bg-red-200')) }}">
                                                {{ $label }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-8 bg-paper-50 border border-forest-100 rounded-md p-5">
        <h2 class="font-display text-lg font-semibold text-forest-950 mb-1">Rekap Bulanan &amp; Semesteran</h2>
        <p class="text-xs text-forest-700/60 mb-4">Jumlah status per siswa untuk jadwal ini (bulan berjalan).</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-forest-700 border-b border-forest-100">
                        <th class="px-3 py-2 text-left font-medium">Siswa</th>
                        <th class="px-3 py-2 text-center font-medium text-forest-800">Masuk</th>
                        <th class="px-3 py-2 text-center font-medium text-orange-700">Sakit</th>
                        <th class="px-3 py-2 text-center font-medium text-sky-700">Izin</th>
                        <th class="px-3 py-2 text-center font-medium text-red-700">Alpa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthly as $studentId => $counts)
                        <tr class="border-t border-forest-100">
                            <td class="px-3 py-2 text-forest-950">{{ $students->firstWhere('id', $studentId)?->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-center text-forest-800">{{ $counts['hadir'] }}</td>
                            <td class="px-3 py-2 text-center text-orange-700">{{ $counts['sakit'] }}</td>
                            <td class="px-3 py-2 text-center text-sky-700">{{ $counts['izin'] }}</td>
                            <td class="px-3 py-2 text-center text-red-700">{{ $counts['alpha'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-sm text-forest-700/60">Belum ada catatan absen bulan ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection