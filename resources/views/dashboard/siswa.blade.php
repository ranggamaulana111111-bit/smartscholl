<div class="space-y-8">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="card p-6">
            <p class="text-xs font-semibold uppercase tracking-widest text-text-muted">Rata-rata Nilai</p>
            <p class="mt-2 text-4xl font-bold tracking-tight {{ ($stats['avg_score'] ?? 0) >= 75 ? 'text-text' : 'text-danger' }}">
                {{ $stats['avg_score'] ?? 0 }}
            </p>
            <div class="mt-4 h-2 rounded-full bg-surface overflow-hidden">
                <div class="h-full rounded-full bg-primary transition-all duration-slow"
                    style="width: {{ min(100, $stats['avg_score'] ?? 0) }}%"></div>
            </div>
            <p class="mt-2 text-xs text-text-muted">Skala 0 - 100, target {{ ($stats['avg_score'] ?? 0) >= 75 ? 'aman' : 'perlu diperbaiki' }}.</p>
        </div>

        <div class="card p-6">
            <p class="text-xs font-semibold uppercase tracking-widest text-text-muted">Presensi Hari Ini</p>
            @if($stats['present_today'] ?? false)
                <p class="mt-2 inline-flex items-center gap-2 text-4xl font-bold tracking-tight text-success">
                    <span class="w-3 h-3 rounded-full bg-success"></span> Hadir
                </p>
            @else
                <p class="mt-2 inline-flex items-center gap-2 text-4xl font-bold tracking-tight text-danger">
                    <span class="w-3 h-3 rounded-full bg-danger"></span> Belum
                </p>
            @endif
            <p class="mt-3 text-xs text-text-muted">Status kehadiran yang tercatat hari ini.</p>
        </div>

        <div class="card p-6">
            <p class="text-xs font-semibold uppercase tracking-widest text-text-muted">Alpha 30 Hari</p>
            <p class="mt-2 text-4xl font-bold tracking-tight {{ ($stats['absence_count'] ?? 0) > 3 ? 'text-danger' : 'text-text' }}">
                {{ $stats['absence_count'] ?? 0 }}
            </p>
            <p class="mt-3 text-xs text-text-muted">{{ ($stats['absence_count'] ?? 0) > 3 ? 'Percaya diri: konsultasi dengan guru BK.' : 'Riwayat kehadiran baik.' }}</p>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="font-semibold text-text">Nilai Saya</h2>
        <p class="text-sm text-text-muted mt-2">Lihat detail nilai dan progres capaian lewat menu
            <a href="{{ route('assessments.index') }}" class="text-accent hover:text-accent-hover underline">Penilaian</a>.</p>
    </div>
</div>