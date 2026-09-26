<div class="space-y-6">
    {{-- Fokus hari ini + ringkasan keluarga --}}
    <div class="bento">
        <section class="bento-card lg:col-span-8 overflow-hidden" aria-labelledby="focus-heading">
            <div class="bento-card__header">
                <div>
                    <p id="focus-heading" class="metric__label">Fokus Hari Ini</p>
                    <p class="metric__hint mt-1">Jaga komunikasi dengan sekolah dan pantau perkembangan anak.</p>
                </div>
            </div>
            <div class="bento-card__body grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ route('parent.dashboard') }}" class="bento-card bento-card--link p-5" aria-label="Portfolio anak">
                    <span class="bento-card__icon" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m2 19H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V22a2 2 0 0 1-2 2z"/></svg>
                    </span>
                    <p class="mt-4 font-display font-semibold text-text">Portfolio Anak</p>
                    <p class="mt-1 text-sm text-text-muted leading-relaxed">Nilai, presensi, dan peringatan dalam satu tempat.</p>
                </a>
                <a href="{{ route('assignments.index') }}" class="bento-card bento-card--link p-5" aria-label="Tugas dan PR">
                    <span class="bento-card__icon" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                    </span>
                    <p class="mt-4 font-display font-semibold text-text">Tugas &amp; PR</p>
                    <p class="mt-1 text-sm text-text-muted leading-relaxed">Pantau tugas anak yang sedang berjalan.</p>
                </a>
                <div class="bento-card bento-card--surface p-5">
                    <span class="bento-card__icon" aria-hidden="true">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-16 0h4l-1-3-2-1-1 4zm4 0h6l-1-3-2-1-2 3v1zm6 0h2A2.5 2.5 0 0 1 21 8v-1l-1-1-1 2h-1l-1 1v1l1 1z"/></svg>
                    </span>
                    <p class="mt-4 font-display font-semibold text-text">Dukungan</p>
                    <p class="mt-1 text-sm text-text-muted leading-relaxed">Hubungi wali kelas atau guru BK melalui sekolah.</p>
                </div>
            </div>
        </section>

        <section class="bento-card bento-card--surface lg:col-span-4" aria-label="Ringkasan keluarga">
            <div class="p-6 flex flex-col gap-5">
                <div>
                    <p class="metric__label">Anak Terdaftar</p>
                    <p class="metric__value mt-1">{{ $stats['children_count'] }}</p>
                    <p class="metric__hint">Terhubung ke akun orang tua ini.</p>
                </div>
                <div class="divider"></div>
                <div>
                    <p class="metric__label">Peringatan Aktif</p>
                    <p class="metric__value {{ ($stats['warning_count'] ?? 0) > 0 ? 'metric__value--accent' : '' }} mt-1">{{ $stats['warning_count'] }}</p>
                    <p class="metric__hint">Perlu ditindaklanjuti bersama wali kelas.</p>
                </div>
            </div>
        </section>
    </div>

    @if(($stats['warning_count'] ?? 0) > 0)
        <div class="bento">
            <div class="bento-card lg:col-span-12 p-5 border-warning/40 flex items-start gap-4" role="alert">
                <span class="w-10 h-10 rounded-lg bg-warning/15 text-warning flex items-center justify-center shrink-0" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                </span>
                <div>
                    <p class="font-medium text-text">Ada {{ $stats['warning_count'] }} peringatan dini aktif untuk anak Anda.</p>
                    <p class="mt-1 text-sm text-text-muted">Tinjau rinciannya lewat portal orang tua agar cepat ditindaklanjuti bersama wali kelas.</p>
                </div>
                <a href="{{ route('parent.dashboard') }}" class="btn btn-ghost btn-sm ml-auto shrink-0">Tinjau</a>
            </div>
        </div>
    @endif
</div>