<div class="space-y-8">
    {{-- Metrik guru --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        @include('dashboard.partials.metric', [
            'label' => 'Total Jurnal',
            'value' => $stats['total_journals'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6m2 19H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V22a2 2 0 0 1-2 2z"/>',
        ])
        @include('dashboard.partials.metric', [
            'label' => 'Jurnal Draft',
            'value' => $stats['draft_journals'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 1 1 3.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>',
        ])
        @include('dashboard.partials.metric', [
            'label' => 'Siswa Binaan',
            'value' => $stats['homeroom_students'],
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1m8-7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm6-3v6m3-3h-6"/>',
        ])
    </div>

    {{-- Jadwal hari ini --}}
    <div class="card overflow-hidden">
        <div class="px-6 pt-6 flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-text">Jadwal Mengajar Hari Ini</h2>
                <p class="text-xs text-text-muted mt-0.5">{{ now()->translatedFormat('l') }}, {{ now()->translatedFormat('d F Y') }}</p>
            </div>
            <a href="{{ route('schedules.index') }}" class="btn btn-ghost btn-sm shrink-0">Semua Jadwal</a>
        </div>
        @if($stats['today_schedule']->isEmpty())
            <p class="px-6 py-10 text-center text-sm text-text-muted">Tidak ada jadwal mengajar hari ini.</p>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Jam</th>
                            <th>Mapel</th>
                            <th>Rombel</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats['today_schedule'] as $s)
                            <tr>
                                <td class="font-semibold text-text whitespace-nowrap">{{ $s->start_time?->format('H:i') }} - {{ $s->end_time?->format('H:i') }}</td>
                                <td class="text-text">{{ $s->subject->name ?? '-' }}</td>
                                <td><span class="badge">{{ $s->rombel->name ?? '-' }}</span></td>
                                <td>
                                    <a href="{{ route('attendance.manualMeetings', $s->id) }}" class="btn btn-primary btn-sm">Absen</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Jurnal belum diisi & aktivitas binaan --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <div class="card overflow-hidden">
            <div class="px-6 pt-6">
                <h2 class="font-semibold text-text">Jurnal yang Belum Diisi</h2>
                <p class="text-xs text-text-muted mt-0.5">Jadwal hari ini tanpa jurnal.</p>
            </div>
            @forelse($pendingJournals as $journal)
                <div class="flex items-center gap-3 px-6 py-3 border-b border-border last:border-0">
                    <span class="w-8 h-8 rounded-lg bg-warning/15 text-warning flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-text truncate">{{ $journal->subject->name ?? '-' }} &middot; {{ $journal->rombel->name ?? '-' }}</p>
                        <p class="text-xs text-text-muted">{{ $journal->start_time?->format('H:i') }} - {{ $journal->end_time?->format('H:i') }}</p>
                    </div>
                    <a href="{{ route('journals.create', ['schedule_id' => $journal->id]) }}" class="btn btn-accent btn-sm shrink-0">Isi</a>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-text-muted">Semua jurnal hari ini sudah diisi.</p>
            @endforelse
        </div>

        <div class="card overflow-hidden">
            <div class="px-6 pt-6">
                <h2 class="font-semibold text-text">Aktivitas Presensi Binaan</h2>
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
                        {{ match($att->type) { 'gate_in' => 'Masuk', 'gate_out' => 'Pulang', 'lesson' => 'Pelajaran' } }}
                    </span>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-text-muted">Belum ada presensi tercatat hari ini.</p>
            @endforelse
        </div>
    </div>
</div>