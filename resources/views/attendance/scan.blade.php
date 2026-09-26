@extends('layouts.app')

@section('title', 'Mode Scan Absensi')
@section('content')
<div class="max-w-2xl mx-auto">
    <x-page-head title="Mode Scan Absensi"
        description="Scan QR atau tap kartu siswa. Scanner USB berfungsi seperti keyboard, cukup arahkan lalu tekan Enter."
        eyebrow="Scan">
    </x-page-head>

    <div class="panel p-5">
        <div class="flex flex-wrap gap-2 mb-6" role="tablist" aria-label="Mode scan">
            @foreach(['gate_in' => 'Masuk Gerbang', 'gate_out' => 'Pulang Gerbang', 'lesson' => 'Hadir Pelajaran'] as $value => $label)
                <a href="{{ route('attendance.scan', ['mode' => $value]) }}"
                    role="tab"
                    aria-selected="{{ $mode === $value ? 'true' : 'false' }}"
                    class="btn {{ $mode === $value ? 'btn-primary' : 'btn-ghost' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="POST" action="{{ route('attendance.record') }}" id="scan-form" class="space-y-4" autocomplete="off">
            @csrf
            <input type="hidden" name="mode" value="{{ $mode }}">

            @if($mode === 'lesson')
                <div>
                    <label for="schedule_id" class="label">Jadwal Pelajaran <span class="text-danger">*</span></label>
                    <select name="schedule_id" id="schedule_id" required class="input">
                        <option value="">-- Pilih jadwal --</option>
                        @foreach($schedules as $sched)
                            <option value="{{ $sched->id }}">
                                {{ $sched->start_time?->format('H:i') }} - {{ $sched->subject->name ?? '-' }} ({{ $sched->rombel->name ?? '-' }}), {{ $sched->teacher->name ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                    @if($schedules->isEmpty())
                        <p class="error-text">Tidak ada jadwal pelajaran hari ini. Hadir pelajaran tidak dapat dicatat.</p>
                    @endif
                </div>
            @endif

            <div>
                <label for="payload" class="label">Kode QR / Kartu RFID</label>
                <input type="text" id="payload" name="payload"
                    class="input font-mono text-center text-lg tracking-widest"
                    placeholder="SS:..."
                    autofocus
                    enterkeyhint="go"
                    aria-describedby="payload-hint">
                <p id="payload-hint" class="text-xs text-text-muted mt-1">Kode unik siswa. Scanner USB membacanya otomatis.</p>
            </div>

            <button type="submit" class="btn btn-primary w-full py-3">Catat Absensi</button>
        </form>
    </div>

    <div class="mt-4 panel p-5">
        <h2 class="text-sm font-semibold text-text">Scan Massal</h2>
        <p class="text-xs text-text-muted mt-1">Tempel beberapa kode sekaligus. Satu kode per baris, atau pisahkan dengan koma.</p>

        <form method="POST" action="{{ route('attendance.recordBulk') }}" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="mode" value="{{ $mode }}">

            @if($mode === 'lesson')
                <div>
                    <label for="bulk_schedule_id" class="label">Jadwal Pelajaran <span class="text-danger">*</span></label>
                    <select name="schedule_id" id="bulk_schedule_id" required class="input">
                        <option value="">-- Pilih jadwal --</option>
                        @foreach($schedules as $sched)
                            <option value="{{ $sched->id }}">
                                {{ $sched->start_time?->format('H:i') }} - {{ $sched->subject->name ?? '-' }} ({{ $sched->rombel->name ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label for="payloads" class="label">Kode-kode</label>
                <textarea id="payloads" name="payloads" rows="4"
                    placeholder="SS:...&#10;04A2B3C4D5&#10;0039123456"
                    class="input font-mono"></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-full py-3">Proses Semua Kode</button>
        </form>
    </div>
</div>

<script>
    (function () {
        const input = document.getElementById('payload');
        const form = document.getElementById('scan-form');
        const tabs = Array.from(document.querySelectorAll('[role="tab"]'));

        form.addEventListener('submit', function () {
            input.value = '';
        });

        if (tabs.length > 0) {
            tabs.forEach(function (tab, index) {
                tab.addEventListener('keydown', function (e) {
                    const len = tabs.length;
                    let move = -1;
                    if (e.key === 'ArrowRight') move = 1;
                    else if (e.key === 'ArrowLeft') move = -1;
                    else if (e.key === 'Home') move = -index;
                    else if (e.key === 'End') move = len - 1 - index;
                    if (move !== -1) {
                        e.preventDefault();
                        tabs[(index + move + len) % len].click();
                    }
                });
            });
        }

        input.focus();
        document.addEventListener('click', function (e) {
            if (input && !input.contains(e.target)) {
                input.focus();
            }
        });
    })();
</script>
@endsection