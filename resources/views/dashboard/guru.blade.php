<div class="space-y-6">
    {{-- Jadwal hari ini + metrik guru --}}
    <div class="bento">
        <section class="bento-card lg:col-span-8 overflow-hidden" aria-labelledby="today-heading">
            <div class="bento-card__header">
                <div>
                    <p id="today-heading" class="metric__label">Jadwal Mengajar Hari Ini</p>
                    <p class="metric__hint mt-1">{{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
                <a href="{{ route('schedules.index') }}" class="btn btn-ghost btn-sm shrink-0">Semua Jadwal</a>
            </div>
            @if($stats['today_schedule']->isEmpty())
                <div class="bento-card__body">
                    <x-empty title="Tidak ada jadwal mengajar hari ini"
                        hint="Hari ini bebas dari sesi mengajar. Jadwal akan tampil di sini sesuai perencanaan.">
                        <x-slot name="icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </x-slot>
                    </x-empty>
                </div>
            @else
                <div class="bento-card__body pt-5">
                    <ol class="space-y-3">
                        @foreach($stats['today_schedule'] as $s)
                            <li class="flex items-center gap-4 rounded-xl border border-border px-4 py-3 transition-colors duration-fast hover:border-accent">
                                <div class="flex flex-col gap-0.5 w-28 shrink-0">
                                    <span class="text-sm font-semibold text-text">{{ $s->start_time?->format('H:i') }}</span>
                                    <span class="text-xs text-text-muted">{{ $s->end_time?->format('H:i') }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-text truncate">{{ $s->subject->name ?? '-' }}</p>
                                    <p class="text-xs text-text-muted">{{ $s->rombel->name ?? '-' }}</p>
                                </div>
                                <a href="{{ route('attendance.manualMeetings', $s->id) }}" class="btn btn-primary btn-sm shrink-0">Absen</a>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </section>

        <section class="bento-card bento-card--surface lg:col-span-4" aria-label="Statistik aktivitas guru">
            <div class="p-6 flex flex-col gap-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="metric">
                        <p class="metric__label">Total Jurnal</p>
                        <p class="metric__value mt-1">{{ $stats['total_journals'] }}</p>
                    </div>
                    <span class="bento-card__icon" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m2 19H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V22a2 2 0 0 1-2 2z"/></svg>
                    </span>
                </div>
                <div class="divider"></div>
                <div class="flex items-start justify-between gap-4">
                    <div class="metric">
                        <p class="metric__label">Jurnal Draft</p>
                        <p class="metric__value {{ ($stats['draft_journals'] ?? 0) > 0 ? 'metric__value--accent' : '' }} mt-1">{{ $stats['draft_journals'] }}</p>
                    </div>
                    <span class="bento-card__icon bg-warning/15 text-warning" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </span>
                </div>
                <div class="divider"></div>
                <div class="flex items-start justify-between gap-4">
                    <div class="metric">
                        <p class="metric__label">Siswa Binaan</p>
                        <p class="metric__value mt-1">{{ $stats['homeroom_students'] }}</p>
                    </div>
                    <span class="bento-card__icon" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1m8-7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm6-3v6m3-3h-6"/></svg>
                    </span>
                </div>
            </div>
        </section>
    </div>

    {{-- Jurnal belum diisi & aktivitas binaan --}}
    <div class="bento">
        <section class="bento-card lg:col-span-7 overflow-hidden" aria-labelledby="pending-heading">
            <div class="bento-card__header">
                <div>
                    <p id="pending-heading" class="metric__label">Jurnal yang Belum Diisi</p>
                    <p class="metric__hint mt-1">Jadwal hari ini tanpa jurnal.</p>
                </div>
            </div>
            @forelse($pendingJournals as $journal)
                <div class="flex items-center gap-3 px-6 py-3 border-b border-border last:border-0">
                    <span class="w-9 h-9 rounded-lg bg-warning/15 text-warning flex items-center justify-center shrink-0" aria-hidden="true">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-text truncate">{{ $journal->subject->name ?? '-' }} &middot; {{ $journal->rombel->name ?? '-' }}</p>
                        <p class="text-xs text-text-muted">{{ $journal->start_time?->format('H:i') }} - {{ $journal->end_time?->format('H:i') }}</p>
                    </div>
                    <a href="{{ route('journals.create', ['schedule_id' => $journal->id]) }}" class="btn btn-accent btn-sm shrink-0">Isi</a>
                </div>
            @empty
                <x-empty title="Semua jurnal hari ini sudah diisi"
                    hint="Tidak ada sesi yang menunggu jurnal."
                    class="!py-12">
                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-slot>
                </x-empty>
            @endforelse
        </section>

        <section class="bento-card lg:col-span-5 overflow-hidden" aria-labelledby="homeroom-feed-heading">
            <div class="bento-card__header">
                <p id="homeroom-feed-heading" class="metric__label">Aktivitas Presensi Binaan</p>
            </div>
            @forelse($recentAttendances as $att)
                <div class="flex items-center gap-3 px-6 py-3 border-b border-border last:border-0">
                    <span class="w-9 h-9 rounded-full bg-surface text-text-muted flex items-center justify-center text-xs font-semibold shrink-0" aria-hidden="true">
                        {{ strtoupper(substr($att->student?->name ?? '?', 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-text truncate">{{ $att->student?->name ?? '-' }}</p>
                        <p class="text-xs text-text-muted">{{ $att->time }}</p>
                    </div>
                    <span class="badge {{ $att->status === 'hadir' ? 'badge-success' : ($att->status === 'alpha' ? 'badge-danger' : 'badge-warning') }}">
                        {{ match($att->type) { 'gate_in' => 'Masuk', 'gate_out' => 'Pulang', 'lesson' => 'Pelajaran' } }}
                    </span>
                </div>
            @empty
                <x-empty title="Belum ada presensi tercatat"
                    hint="Presensi siswa binaan akan muncul di sini hari ini."
                    class="!py-12">
                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M6 13h12m6-1a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-slot>
                </x-empty>
            @endforelse
        </section>
    </div>
</div>