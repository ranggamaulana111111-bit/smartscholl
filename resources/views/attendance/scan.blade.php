@extends('layouts.app')

@section('title', 'Mode Scan Absensi')
@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6 text-center">
        <h1 class="font-display text-2xl font-semibold text-forest-950">Mode Scan Absensi</h1>
        <p class="text-sm text-forest-700/70 mt-1">Scan QR atau tap kartu siswa. Scanner USB berfungsi seperti keyboard, cukup arahkan lalu tekan Enter.</p>
    </div>

    <div class="bg-paper-50 border border-forest-100 rounded-md p-5">
        <div class="flex gap-2 mb-5">
            @foreach(['gate_in' => 'Masuk Gerbang', 'gate_out' => 'Pulang Gerbang', 'lesson' => 'Hadir Pelajaran'] as $value => $label)
                <a href="{{ route('attendance.scan', ['mode' => $value]) }}"
                    class="px-3 py-2 rounded-md text-sm font-medium {{ $mode === $value ? 'bg-forest-800 text-paper-50' : 'bg-paper-100 text-forest-800 hover:bg-forest-100' }} transition-colors">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if(session('success'))
            <div class="mb-5 p-4 bg-forest-50 border border-forest-300 rounded-md text-sm text-forest-900 font-medium" role="status">
                {{ session('success') }}
            </div>
        @elseif(session('info'))
            <div class="mb-5 p-4 bg-paper-200 border border-paper-300 rounded-md text-sm text-forest-800" role="status">
                {{ session('info') }}
            </div>
        @elseif(session('error'))
            <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-md text-sm text-red-700" role="alert">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('attendance.record') }}" id="scan-form" autocomplete="off">
            @csrf
            <input type="hidden" name="mode" value="{{ $mode }}">

            @if($mode === 'lesson')
                <div class="mb-5">
                    <label for="schedule_id" class="block text-sm font-medium text-forest-800 mb-1">
                        Jadwal Pelajaran <span class="text-red-500">*</span>
                    </label>
                    <select name="schedule_id" id="schedule_id" required
                        class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
                        <option value="">-- Pilih jadwal --</option>
                        @foreach($schedules as $sched)
                            <option value="{{ $sched->id }}">
                                {{ $sched->start_time?->format('H:i') }} - {{ $sched->subject->name ?? '-' }} ({{ $sched->rombel->name ?? '-' }}), {{ $sched->teacher->name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                    @if($schedules->isEmpty())
                        <p class="mt-1 text-xs text-red-600">Tidak ada jadwal pelajaran hari ini. Hadir pelajaran tidak dapat dicatat.</p>
                    @endif
                </div>
            @endif

            <label for="payload" class="block text-sm font-medium text-forest-800 mb-1">
                Kode QR / Kartu RFID <span class="text-forest-700/60">(scan QR atau tap kartu)</span>
            </label>
            <input
                type="text"
                id="payload"
                name="payload"
                class="w-full px-4 py-3 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-lg tracking-widest text-center focus:border-gold-500 outline-none"
                placeholder="SS:..."
                autofocus
                enterkeyhint="go"
            >

            <button type="submit" class="w-full mt-4 px-4 py-3 bg-forest-800 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-700 transition-colors">
                Catat Absensi
            </button>
        </form>
    </div>

    <div class="mt-4 bg-paper-50 border border-forest-100 rounded-md p-5">
        <h2 class="text-sm font-semibold text-forest-900">Scan Massal</h2>
        <p class="text-xs text-forest-700/70 mt-1">Tempel beberapa kode sekaligus. Satu kode per baris, atau pisahkan dengan koma.</p>

        <form method="POST" action="{{ route('attendance.recordBulk') }}">
            @csrf
            <input type="hidden" name="mode" value="{{ $mode }}">

            @if($mode === 'lesson')
                <div class="mt-3">
                    <label for="bulk_schedule_id" class="block text-sm font-medium text-forest-800 mb-1">
                        Jadwal Pelajaran <span class="text-red-500">*</span>
                    </label>
                    <select name="schedule_id" id="bulk_schedule_id" required
                        class="w-full px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none">
                        <option value="">-- Pilih jadwal --</option>
                        @foreach($schedules as $sched)
                            <option value="{{ $sched->id }}">
                                {{ $sched->start_time?->format('H:i') }} - {{ $sched->subject->name ?? '-' }} ({{ $sched->rombel->name ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <textarea
                id="payloads"
                name="payloads"
                rows="4"
                placeholder="SS:...&#10;04A2B3C4D5&#10;0039123456"
                class="w-full mt-3 px-3 py-2 rounded-md border border-forest-200 bg-paper-50 text-forest-950 text-sm focus:border-gold-500 outline-none font-mono"
            ></textarea>

            <button type="submit" class="w-full mt-3 px-4 py-3 bg-forest-700 text-paper-50 text-sm font-medium rounded-md hover:bg-forest-600 transition-colors">
                Proses Semua Kode
            </button>
        </form>
    </div>
</div>

<script>
    (function () {
        const input = document.getElementById('payload');
        const form = document.getElementById('scan-form');

        form.addEventListener('submit', function () {
            input.value = '';
        });

        input.focus();
        document.addEventListener('click', function (e) {
            if (!input.contains(e.target)) {
                input.focus();
            }
        });
    })();
</script>
@endsection