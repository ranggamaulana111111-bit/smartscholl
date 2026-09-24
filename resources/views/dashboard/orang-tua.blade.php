<div class="space-y-8">
    {{-- Metrik orang tua --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        @include('dashboard.partials.metric', [
            'label' => 'Anak Terdaftar',
            'value' => $stats['children_count'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 19l-7 3v-3H3a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h5v2H4v11h3v3l5-2 5 2v-3h3V6h-5V4h5a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2v3l-7-3zM7 8h10v2H7V8zm0 3h10v2H7v-2z"/>',
        ])
        @include('dashboard.partials.metric', [
            'label' => 'Peringatan Aktif',
            'value' => $stats['warning_count'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>',
        ])
    </div>

    <div class="card overflow-hidden">
        <div class="px-6 pt-6">
            <h2 class="font-semibold text-text">Fokus Hari Ini</h2>
            <p class="text-sm text-text-muted mt-0.5">Jaga komunikasi dengan sekolah dan pantau perkembangan anak Anda.</p>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('parent.dashboard') }}" class="p-5 rounded-xl border border-border bg-surface hover:bg-accent/10 hover:border-accent transition-colors">
                <span class="w-9 h-9 rounded-lg bg-primary text-accent flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m2 19H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V22a2 2 0 0 1-2 2z"/></svg>
                </span>
                <p class="mt-3 font-medium text-text">Portfolio Anak</p>
                <p class="mt-1 text-xs text-text-muted">Nilai, presensi, dan peringatan dalam satu tempat.</p>
            </a>
            <a href="{{ route('assignments.index') }}" class="p-5 rounded-xl border border-border bg-surface hover:bg-accent/10 hover:border-accent transition-colors">
                <span class="w-9 h-9 rounded-lg bg-primary text-accent flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/></svg>
                </span>
                <p class="mt-3 font-medium text-text">Tugas &amp; PR</p>
                <p class="mt-1 text-xs text-text-muted">Pantau tugas yang sedang berjalan.</p>
            </a>
            <div class="p-5 rounded-xl border border-border bg-surface">
                <span class="w-9 h-9 rounded-lg bg-primary text-accent flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-16 0h4l-1-3-2-1-1 4zm4 0h6l-1-3-2-1-2 3v1zm6 0h2A2.5 2.5 0 0 1 21 8v-1l-1-1-1 2h-1l-1 1v1l1 1z"/></svg>
                </span>
                <p class="mt-3 font-medium text-text">Dukungan</p>
                <p class="mt-1 text-xs text-text-muted">Hubungi wali kelas atau guru BK melalui sekolah.</p>
            </div>
        </div>
    </div>

    @if(($stats['warning_count'] ?? 0) > 0)
        <div class="p-5 rounded-xl border border-warning/40 bg-warning/10 flex items-start gap-4">
            <span class="w-9 h-9 rounded-lg bg-warning/15 text-warning flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
            </span>
            <div>
                <p class="font-medium text-text">Ada {{ $stats['warning_count'] }} peringatan dini aktif untuk anak Anda.</p>
                <p class="mt-1 text-sm text-text-muted">Tinjau rinciannya lewat portal orang tua agar cepat ditindaklanjuti bersama wali kelas.</p>
            </div>
        </div>
    @endif
</div>