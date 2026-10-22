@extends('layouts.app')

@section('title', 'Scan QR: '.$schedule->rombel->name)

@section('content')
<div
    data-lesson-scan
    data-schedule-id="{{ $schedule->id }}"
    data-record-url="{{ route('attendance.record', ['mode' => 'lesson']) }}"
    data-status-url="{{ route('attendance.schedule.scan.status', $schedule) }}"
    data-csrf="{{ csrf_token() }}"
>
    <x-page-head title="Scan Kartu QR {{ $schedule->rombel->name }}"
        description="{{ $schedule->subject->name }} &middot; {{ hariName($schedule->day_of_week) }} &middot; {{ $schedule->start_time->format('H:i') }}-{{ $schedule->end_time->format('H:i') }} &middot; Guru {{ $schedule->teacher->name }}"
        eyebrow="Absensi Pelajaran">
        <x-slot name="actions">
            <a href="{{ route('attendance.manualSchedule', $schedule->id) }}" class="btn btn-ghost btn-sm">Isi Manual</a>
            <a href="{{ route('attendance.manualMeetings', $schedule->id) }}" class="btn btn-ghost btn-sm">&larr; Jadwal Saya</a>
        </x-slot>
    </x-page-head>

    @unless($window['open'])
        <div class="scan-feedback scan-feedback--info mb-6" role="status">
            {{ $window['error'] }} Pengecatan dibuka pukul {{ $window['opens_at']->format('H:i') }} dan ditutup pukul {{ $window['closes_at']->format('H:i') }}. Daftar siswa di bawah tetap dapat dibaca.
        </div>
    @endunless

    <div class="bento">
        <section class="bento-card overflow-hidden lg:col-span-7" aria-labelledby="viewfinder-heading">
            <div class="bento-card__header">
                <div>
                    <p id="viewfinder-heading" class="bento-card__title">Kamera</p>
                    <p class="metric__hint mt-1">Pindai kartu QR siswa, satu kartu per scan.</p>
                </div>
            </div>

            <div class="bento-card__body pt-5">
                <div class="scan-viewfinder">
                    <video
                        data-scan-video
                        data-active="false"
                        class="scan-video"
                        muted
                        playsinline
                        aria-label="Pratinjau kamera untuk pemindaian QR"
                    ></video>

                    <div class="scan-reticle" aria-hidden="true">
                        <span></span><span></span><span></span><span></span>
                    </div>

                    <div class="scan-frame" data-scan-overlay>
                        <p>Arahkan kartu QR siswa ke dalam bingkai, atau pakai kolom kode di bawah bila kamera tidak tersedia.</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <button type="button" data-scan-toggle aria-pressed="false" class="btn btn-primary">
                        Mulai Kamera
                    </button>
                    <p class="text-xs text-text-muted sm:flex-1" data-scan-state hidden aria-live="polite"></p>
                </div>

                <p class="text-xs text-text-muted mt-3">
                    Kamera dibuka atas izin Anda dan berhenti sendiri saat tab ditinggalkan.
                </p>

                <p class="scan-feedback mt-4" data-scan-feedback hidden role="status" aria-live="polite"></p>

                <form
                    method="POST"
                    action="{{ route('attendance.record', ['mode' => 'lesson']) }}"
                    data-scan-manual
                    class="mt-5 pt-5 border-t border-border"
                >
                    @csrf
                    <input type="hidden" name="mode" value="lesson">
                    <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">

                    <label for="payload" class="label">Kode QR / Kartu RFID</label>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input
                            type="text"
                            id="payload"
                            name="payload"
                            data-scan-payload
                            class="input font-mono flex-1"
                            placeholder="SS:..."
                            autocomplete="off"
                            enterkeyhint="go"
                            aria-describedby="payload-hint"
                        >
                        <button type="submit" class="btn btn-accent shrink-0">Catat</button>
                    </div>
                    <p id="payload-hint" class="text-xs text-text-muted mt-2">
                        Untuk scanner USB, kode terbaca otomatis lalu tekan Enter. Baris ini juga bekerja tanpa kamera.
                    </p>
                </form>
            </div>
        </section>

        <section class="bento-card bento-card--surface lg:col-span-5 overflow-hidden" aria-labelledby="roster-heading">
            <div class="bento-card__header">
                <div>
                    <p id="roster-heading" class="bento-card__title">Daftar Siswa</p>
                    <p class="metric__hint mt-1" data-scan-status-note hidden></p>
                </div>
                <p class="font-display text-2xl font-semibold text-text shrink-0">
                    <span data-scan-count="recorded">{{ $summary['recorded'] }}</span><span class="text-text-muted text-lg">/{{ $summary['total'] }}</span>
                </p>
            </div>

            <div class="bento-card__body pt-5">
                <dl class="grid grid-cols-3 gap-3">
                    @foreach(['hadir' => 'Hadir', 'sakit' => 'Sakit', 'alpha' => 'Alpa'] as $key => $label)
                        <div class="rounded-lg border border-border bg-white px-3 py-2">
                            <dt class="text-[11px] uppercase tracking-wider text-text-muted">{{ $label }}</dt>
                            <dd class="font-display text-xl font-semibold text-text mt-0.5" data-scan-count="{{ $key }}">{{ $summary[$key] }}</dd>
                        </div>
                    @endforeach
                </dl>

                @if($roster === [])
                    <div class="panel mt-4">
                        <div class="empty-state">
                            <span class="empty-state__icon" aria-hidden="true">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1m8-7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm6-3v6m3-3h-6"/></svg>
                            </span>
                            <p class="empty-state__title">Rombel ini belum punya siswa</p>
                            <p class="empty-state__hint">Tambahkan siswa ke rombel melalui menu Master Data, lalu kembali ke halaman ini.</p>
                        </div>
                    </div>
                @else
                    <div class="panel mt-4 overflow-hidden">
                        <div class="table-wrap" style="max-height: 26rem; overflow-y: auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Siswa</th>
                                        <th>NISN</th>
                                        <th class="text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody data-scan-roster>
                                    @foreach($roster as $row)
                                        <tr>
                                            <td>
                                                <span class="font-medium text-text">{{ $row['name'] }}</span>
                                                <span class="block text-xs font-normal text-text-muted sm:hidden">{{ $row['nisn'] }}</span>
                                            </td>
                                            <td class="text-text-muted hidden sm:table-cell">{{ $row['nisn'] }}</td>
                                            <td class="text-right">
                                                @if($row['status'] === null)
                                                    <span class="badge badge-pending">Belum</span>
                                                @else
                                                    <span class="badge {{ $row['status'] === 'hadir' ? 'badge-success' : ($row['status'] === 'alpha' ? 'badge-danger' : 'badge-warning') }}">
                                                        {{ ucfirst($row['status']) }}
                                                    </span>
                                                    <span class="block text-xs text-text-muted mt-1">{{ $row['time'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('attendance.schedule.scan.remaining', $schedule) }}"
                        data-scan-remaining-form
                        data-confirm="Tandai {{ $summary['remaining'] }} siswa yang belum tercatat dengan status ini?"
                        class="mt-5 pt-5 border-t border-border"
                        @if($summary['remaining'] === 0) hidden @endif
                    >
                        @csrf
                        <p class="font-medium text-text text-sm">
                            <span data-scan-count="remaining">{{ $summary['remaining'] }}</span> siswa belum tercatat
                        </p>
                        <p class="text-xs text-text-muted mt-1">
                            Tandai sekaligus untuk siswa yang tidak perlu dipindai satu per satu.
                        </p>

                        <label for="remaining_note" class="label mt-4">Catatan (opsional)</label>
                        <input
                            type="text"
                            id="remaining_note"
                            name="note"
                            class="input"
                            maxlength="255"
                            placeholder="Contoh: siswa diabsen oleh guru piket"
                        >

                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <button type="submit" name="status" value="sakit" class="btn btn-ghost">Sakit</button>
                            <button type="submit" name="status" value="izin" class="btn btn-ghost">Izin</button>
                            <button type="submit" name="status" value="alpha" class="btn btn-ghost">Alpa</button>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
