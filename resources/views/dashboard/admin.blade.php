<div class="space-y-6">
    {{-- Focal: kehadiran hari ini (hero editorial) --}}
    <div class="bento">
        <section class="bento-card bento-card--inverse lg:col-span-8" aria-labelledby="attendance-heading">
            <div class="p-6 lg:p-8 flex flex-col gap-8 sm:flex-row sm:items-center sm:justify-between">
                <div class="metric">
                    <p id="attendance-heading" class="metric__label">
                        <span class="inline-block w-2 h-2 rounded-full bg-accent mr-2" aria-hidden="true"></span>
                        Kehadiran Hari Ini
                    </p>
                    <p class="metric__value metric__value--lg mt-2">
                        {{ $stats['attendance_rate'] }}<span class="text-2xl font-semibold text-white/60">%</span>
                    </p>
                    <p class="metric__hint">{{ $stats['today_present'] }} dari {{ $stats['total_students'] }} siswa tercatat hadir hari ini.</p>
                    <div class="mt-6 h-2 w-full max-w-md rounded-full bg-white/20 overflow-hidden">
                        <div class="h-full rounded-full bg-accent transition-all duration-slow" style="width: {{ $stats['attendance_rate'] }}%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 w-full sm:w-auto sm:min-w-[220px]">
                    <div class="stat-tile p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-white/60">Hadir</p>
                        <p class="mt-1 text-3xl font-semibold text-white">{{ $stats['today_present'] }}</p>
                    </div>
                    <div class="stat-tile p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-white/60">Belum Hadir</p>
                        <p class="mt-1 text-3xl font-semibold text-accent">{{ $stats['today_absent'] }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bento-card bento-card--surface lg:col-span-4" aria-label="Ringkasan jadwal hari ini">
            <div class="p-6 flex flex-col gap-5">
                <div>
                    <p class="metric__label">Jadwal Hari Ini</p>
                    <p class="metric__value mt-1">{{ $stats['today_schedules'] }}</p>
                    <p class="metric__hint">Sesi pelajaran terjadwal untuk hari ini.</p>
                </div>
                <div class="divider"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="metric__label">Guru Mengajar Hari Ini</p>
                        <p class="mt-1 text-2xl font-semibold text-text">{{ $stats['teachers_teaching_today'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="metric__label">Guru Tanpa Jadwal Hari Ini</p>
                        <p class="mt-1 text-2xl font-semibold text-text">{{ $stats['teachers_idle_today'] }}</p>
                    </div>
                </div>
                <p class="metric__hint">Tahun ajaran {{ $stats['active_academic_year'] ?? 'berjalan' }}.</p>
            </div>
        </section>
    </div>

    {{-- Tren & perhatian --}}
    <div class="bento">
        <section class="bento-card lg:col-span-7" aria-labelledby="trend-heading">
            <div class="bento-card__header">
                <div>
                    <p id="trend-heading" class="metric__label">Tren Kehadiran</p>
                    <p class="metric__hint mt-1">Siswa hadir per hari selama 7 hari terakhir.</p>
                </div>
                <span class="badge">7 hari terakhir</span>
            </div>
            <div class="bento-card__body pt-6">
                <div class="flex items-end gap-3 h-40">
                    @php $maxTrend = max(1, ...array_column($stats['attendance_trend'], 'present')); @endphp
                    @foreach($stats['attendance_trend'] as $point)
                        <div class="flex-1 flex flex-col items-center justify-end gap-2 h-full min-w-0">
                            <span class="text-xs font-semibold text-text-muted">{{ $point['present'] }}</span>
                            <div class="w-full rounded-t-md bg-primary transition-colors duration-fast hover:bg-accent"
                                style="height: {{ max(4, round(($point['present'] / $maxTrend) * 100)) }}%"></div>
                            <span class="text-xs text-text-muted">{{ $point['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <div class="lg:col-span-5 flex flex-col gap-4">
            @if(($stats['pending_journals'] ?? 0) > 0)
                <a href="{{ route('journals.index') }}" class="bento-card bento-card--link p-5 flex items-start gap-4">
                    <span class="bento-card__icon bg-warning/15 text-warning">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m2 19H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V22a2 2 0 0 1-2 2z"/></svg>
                    </span>
                    <span>
                        <span class="font-semibold text-text block">{{ $stats['pending_journals'] }} Jurnal Draft Hari Ini</span>
                        <span class="text-sm text-text-muted">Belum ditutup oleh guru.</span>
                    </span>
                </a>
            @endif

            @if(($stats['unresolved_ews'] ?? 0) > 0)
                <a href="{{ route('ews.index') }}" class="bento-card bento-card--link p-5 flex items-start gap-4">
                    <span class="bento-card__icon bg-danger/15 text-danger">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </span>
                    <span>
                        <span class="font-semibold text-text block">{{ $stats['unresolved_ews'] }} Peringatan Belum Selesai</span>
                        <span class="text-sm text-text-muted">Perlu tindak lanjut.</span>
                    </span>
                </a>
            @endif

            @if(($stats['pending_journals'] ?? 0) === 0 && ($stats['unresolved_ews'] ?? 0) === 0)
                <div class="bento-card bento-card--surface p-5 flex-1 flex flex-col justify-center">
                    <p class="bento-card__title">Semua jurnal &amp; peringatan beres</p>
                    <p class="mt-1 text-sm text-text-muted">Tag ini muncul saat ada yang butuh perhatian Anda.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Aktivitas & pengguna --}}
    <div class="bento">
        <section class="bento-card lg:col-span-7 overflow-hidden" aria-labelledby="activity-heading">
            <div class="bento-card__header">
                <p id="activity-heading" class="metric__label">Aktivitas Presensi Hari Ini</p>
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
                        {{ match($att->type) { 'gate_in' => 'Masuk', 'gate_out' => 'Pulang', 'lesson' => 'Pelajaran' } }} &middot; {{ ucfirst($att->status) }}
                    </span>
                </div>
            @empty
                <x-empty title="Belum ada presensi tercatat"
                    hint="Aktivitas masuk dan keluar siswa akan muncul di sini seiring berjalannya hari.">
                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M6 13h12m6-1a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-slot>
                </x-empty>
            @endforelse
        </section>

        <section class="bento-card lg:col-span-5 overflow-hidden" aria-labelledby="users-heading">
            <div class="bento-card__header">
                <p id="users-heading" class="metric__label">Pengguna Terbaru</p>
            </div>
            @forelse($recentUsers as $userRow)
                <div class="flex items-center gap-3 px-6 py-3 border-b border-border last:border-0">
                    <span class="brand-mark brand-mark--sm" aria-hidden="true">
                        {{ strtoupper(substr($userRow['name'], 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-text truncate">{{ $userRow['name'] }}</p>
                        <p class="text-xs text-text-muted truncate">{{ $userRow['email'] }}</p>
                    </div>
                    <span class="badge">{{ str_replace('_', ' ', ucfirst($userRow['role'])) }}</span>
                </div>
            @empty
                <x-empty title="Belum ada pengguna di sekolah ini"
                    hint="Akun guru, siswa, dan orang tua akan tampil di sini setelah dibuat.">
                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1m8-7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm6-3v6m3-3h-6"/></svg>
                    </x-slot>
                </x-empty>
            @endforelse
        </section>
    </div>
</div>