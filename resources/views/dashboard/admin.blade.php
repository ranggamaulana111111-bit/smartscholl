<div class="space-y-8">
    {{-- Focal: kehadiran hari ini --}}
    <div class="card bg-primary text-on-primary p-7 relative overflow-hidden">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-accent">Kehadiran Hari Ini</p>
                <p class="mt-3 text-6xl font-bold tracking-tight text-on-primary">
                    {{ $stats['attendance_rate'] }}<span class="text-2xl text-on-primary/70">%</span>
                </p>
                <p class="mt-2 text-sm text-on-primary/80">{{ $stats['today_present'] }} dari {{ $stats['total_students'] }} siswa hadir</p>
                <div class="mt-5 h-2 w-full max-w-md rounded-full bg-on-primary/20 overflow-hidden">
                    <div class="h-full rounded-full bg-accent transition-all duration-slow" style="width: {{ $stats['attendance_rate'] }}%"></div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="rounded-lg bg-on-primary/10 p-4">
                    <p class="text-sm text-on-primary/70">Hadir</p>
                    <p class="mt-1 text-3xl font-bold text-on-primary">{{ $stats['today_present'] }}</p>
                </div>
                <div class="rounded-lg bg-on-primary/10 p-4">
                    <p class="text-sm text-on-primary/70">Belum Hadir</p>
                    <p class="mt-1 text-3xl font-bold text-accent">{{ $stats['today_absent'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Metrik sekolah --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
        @include('dashboard.partials.metric', [
            'label' => 'Total Siswa',
            'value' => $stats['total_students'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1m8-7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm6-3v6m3-3h-6"/>',
        ])
        @include('dashboard.partials.metric', [
            'label' => 'Total Guru',
            'value' => $stats['total_teachers'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM5 20c.6-3.6 3.4-6 7-6s6.4 2.4 7 6m-5-9h.01"/>',
        ])
        @include('dashboard.partials.metric', [
            'label' => 'Rombel',
            'value' => $stats['total_rombels'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm3 7h8m-8 4h8m-8 4h5"/>',
        ])
        @include('dashboard.partials.metric', [
            'label' => 'Jadwal Hari Ini',
            'value' => $stats['today_schedules'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 10h18M4 4h16v14H4V4z"/>',
            'sub' => $stats['teachers_teaching_today'].' guru mengajar',
        ])
    </div>

    {{-- Tren kehadiran & alert --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 card p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="font-semibold text-text">Tren Kehadiran</h2>
                    <p class="text-xs text-text-muted">7 hari terakhir</p>
                </div>
                <span class="badge">Pertemuan</span>
            </div>
            <div class="flex items-end gap-3 h-40">
                @php $maxTrend = max(1, ...array_column($stats['attendance_trend'], 'present')); @endphp
                @foreach($stats['attendance_trend'] as $point)
                    <div class="flex-1 flex flex-col items-center justify-end gap-2 h-full min-w-0">
                        <span class="text-xs font-semibold text-text">{{ $point['present'] }}</span>
                        <div class="w-full rounded-t bg-primary transition-colors duration-fast hover:bg-accent"
                            style="height: {{ max(4, round(($point['present'] / $maxTrend) * 100)) }}%"></div>
                        <span class="text-xs text-text-muted">{{ $point['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="space-y-5">
            @if(($stats['pending_journals'] ?? 0) > 0)
                <a href="{{ route('journals.index') }}" class="card p-5 flex items-start gap-4 hover:shadow-elevated transition-shadow min-h-[44px]">
                    <span class="w-10 h-10 rounded-lg bg-warning/15 text-warning flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m2 19H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V22a2 2 0 0 1-2 2z"/></svg>
                    </span>
                    <div>
                        <p class="font-semibold text-text">{{ $stats['pending_journals'] }} Jurnal Draft Hari Ini</p>
                        <p class="text-xs text-text-muted mt-0.5">Belum ditutup oleh guru.</p>
                    </div>
                </a>
            @endif

            @if(($stats['unresolved_ews'] ?? 0) > 0)
                <a href="{{ route('ews.index') }}" class="card p-5 flex items-start gap-4 hover:shadow-elevated transition-shadow min-h-[44px]">
                    <span class="w-10 h-10 rounded-lg bg-danger/15 text-danger flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    </span>
                    <div>
                        <p class="font-semibold text-text">{{ $stats['unresolved_ews'] }} Peringatan Belum Selesai</p>
                        <p class="text-xs text-text-muted mt-0.5">Perlu tindak lanjut.</p>
                    </div>
                </a>
            @endif

            <div class="card p-5">
                <p class="text-xs font-semibold uppercase tracking-widest text-text-muted">Guru Mengajar Hari Ini</p>
                <p class="mt-2 text-3xl font-bold text-text">{{ $stats['teachers_teaching_today'] }} <span class="text-sm font-normal text-text-muted">mengajar</span></p>
                <p class="mt-1 text-xs text-text-muted">Guru Tanpa Jadwal Hari Ini: {{ $stats['teachers_idle_today'] }}.</p>
        </div>
    </div>

    {{-- Aktivitas & pengguna terbaru --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <div class="card overflow-hidden">
            <div class="px-6 pt-6">
                <h2 class="font-semibold text-text">Aktivitas Presensi Hari Ini</h2>
            </div>
            @forelse($recentAttendances as $att)
                <div class="flex items-center gap-3 px-6 py-3 border-b border-border last:border-0">
                    <span class="w-8 h-8 rounded-full bg-surface text-text-muted flex items-center justify-center text-xs font-semibold shrink-0">
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
                <p class="px-6 py-10 text-center text-sm text-text-muted">Belum ada presensi tercatat hari ini.</p>
            @endforelse
        </div>

        <div class="card overflow-hidden">
            <div class="px-6 pt-6">
                <h2 class="font-semibold text-text">Pengguna Terbaru</h2>
            </div>
            @forelse($recentUsers as $userRow)
                <div class="flex items-center gap-3 px-6 py-3 border-b border-border last:border-0">
                    <span class="w-8 h-8 rounded-full bg-primary text-accent flex items-center justify-center text-xs font-semibold shrink-0">
                        {{ strtoupper(substr($userRow['name'], 0, 1)) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-text truncate">{{ $userRow['name'] }}</p>
                        <p class="text-xs text-text-muted truncate">{{ $userRow['email'] }}</p>
                    </div>
                    <span class="badge">{{ str_replace('_', ' ', ucfirst($userRow['role'])) }}</span>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-text-muted">Belum ada pengguna di sekolah ini.</p>
            @endforelse
        </div>
    </div>
</div>