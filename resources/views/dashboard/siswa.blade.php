<div class="space-y-6">
    {{-- Capaian + presensi hari ini --}}
    <div class="bento">
        <section class="bento-card bento-card--inverse lg:col-span-7" aria-labelledby="score-heading">
            <div class="p-6 lg:p-8">
                <div class="metric">
                    <p id="score-heading" class="metric__label">Rata-rata Nilai Anda</p>
                    <p class="metric__value metric__value--lg mt-1 {{ ($stats['avg_score'] ?? 0) >= 75 ? '' : 'text-danger' }}">
                        {{ $stats['avg_score'] ?? 0 }}
                        <span class="text-2xl font-semibold text-white/60">/ 100</span>
                    </p>
                    <p class="metric__hint">Skala 0 - 100. Target capaian minimal 75.</p>
                    <div class="mt-6 h-2 w-full max-w-md rounded-full bg-white/20 overflow-hidden">
                        <div class="h-full rounded-full bg-accent transition-all duration-slow"
                            style="width: {{ min(100, $stats['avg_score'] ?? 0) }}%"></div>
                    </div>
                    <p class="mt-3 text-sm text-white/70">
                        {{ ($stats['avg_score'] ?? 0) >= 75 ? 'Capaian aman, pertahankan.' : 'Perlu sedikit usaha lagi untuk capaian aman.' }}
                    </p>
                </div>
            </div>
        </section>

        <section class="bento-card bento-card--surface lg:col-span-5" aria-labelledby="presence-heading">
            <div class="p-6 lg:p-8">
                <p id="presence-heading" class="metric__label">Presensi Hari Ini</p>
                @if($stats['present_today'] ?? false)
                    <p class="mt-2 inline-flex items-center gap-2.5 text-4xl font-semibold tracking-tight text-success">
                        <span class="w-3 h-3 rounded-full bg-success" aria-hidden="true"></span> Hadir
                    </p>
                @else
                    <p class="mt-2 inline-flex items-center gap-2.5 text-4xl font-semibold tracking-tight text-danger">
                        <span class="w-3 h-3 rounded-full bg-danger" aria-hidden="true"></span> Belum
                    </p>
                @endif
                <p class="metric__hint mt-3">Status kehadiran yang tercatat hari ini.</p>

                <div class="divider mt-6"></div>

                <div class="mt-6">
                    <p class="metric__label">Alpha 30 Hari</p>
                    <p class="metric__value mt-1 {{ ($stats['absence_count'] ?? 0) > 3 ? 'text-danger' : '' }}">{{ $stats['absence_count'] ?? 0 }}</p>
                    <p class="metric__hint">{{ ($stats['absence_count'] ?? 0) > 3 ? 'Percaya diri: konsultasi dengan guru BK.' : 'Riwayat kehadiran baik.' }}</p>
                </div>
            </div>
        </section>
    </div>

    {{-- Navigasi belajar --}}
    <div class="bento">
        <a href="{{ route('assessments.index') }}"
           class="bento-card bento-card--link lg:col-span-8 p-6 lg:p-8 flex flex-col gap-3"
           aria-label="Nilai dan capaian saya">
            <div class="flex items-start justify-between gap-4">
                <div class="metric">
                    <p class="metric__label">Nilai &amp; Capaian Saya</p>
                    <p class="display-title text-2xl mt-2">Telusuri nilai dan perkembangan belajarmu</p>
                    <p class="metric__hint mt-2">Detail penilaian, capaian tiap mapel, dan tren pembelajaran.</p>
                </div>
                <span class="bento-card__icon" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5-5 5m-6-10l5 5-5 5"/></svg>
                </span>
            </div>
        </a>

        <a href="{{ route('assignments.index') }}"
           class="bento-card bento-card--link lg:col-span-4 p-6 flex flex-col gap-3"
           aria-label="Tugas dan PR">
            <div class="flex items-start justify-between gap-4">
                <div class="metric">
                    <p class="metric__label">Tugas &amp; PR</p>
                    <p class="display-title text-2xl mt-2">Cek tugas dan tenggat</p>
                    <p class="metric__hint mt-2">Tugas berjalan dan tenggat pengumpulan.</p>
                </div>
                <span class="bento-card__icon" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </span>
            </div>
        </a>
    </div>
</div>