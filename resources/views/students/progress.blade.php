@extends('layouts.app')

@section('title', 'Progres Siswa')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="font-display text-2xl font-semibold text-text">{{ $student->name }}</h1>
        <p class="text-sm text-text-muted mt-1">
            NISN {{ $student->nisn }} &middot; Rombel {{ $student->rombel->name ?? '-' }}
        </p>
    </div>
    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru', 'orang_tua', 'siswa']))
        <a href="{{ route('students.rapor', $student) }}" class="btn btn-accent" target="_blank">Cetak Rapor</a>
    @endif
</div>

@if($ewsLogs->isNotEmpty())
    <div class="card p-5 mb-6 border-danger">
        <h2 class="font-display text-sm font-semibold text-danger mb-3">Peringatan Dini (EWS)</h2>
        <div class="space-y-2">
            @foreach($ewsLogs as $log)
                <div class="flex items-start justify-between gap-4 text-sm">
                    <span class="text-text">{{ $log->description }}</span>
                    <span class="badge {{ $log->is_resolved ? 'badge-success' : 'badge-danger' }} shrink-0">{{ $log->is_resolved ? 'Selesai' : 'Aktif' }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if($bySubject->isEmpty())
    <div class="card p-12 text-center mb-6">
        <p class="text-text-muted">Belum ada nilai tercatat untuk siswa ini.</p>
    </div>
@else
    <div class="card overflow-hidden mb-6">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Tren Nilai per Mata Pelajaran</h2>
        </div>
        <div class="p-4 space-y-4">
            @foreach($bySubject as $row)
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-text">{{ $row['name'] }}</span>
                        <span class="text-sm text-text-muted">Rata-rata: <strong class="text-text">{{ $row['avg'] }}</strong></span>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach($row['categories'] as $category => $cat)
                            <span class="badge {{ $cat['avg'] >= 75 ? 'badge-success' : 'badge-danger' }}">
                                {{ App\Models\Assessment::instanceCategories()[$category] ?? ucfirst($category) }}: {{ $cat['avg'] }} ({{ $cat['count'] }})
                            </span>
                        @endforeach
                    </div>
                    <div class="h-2 rounded-full bg-surface overflow-hidden">
                        <div class="h-full rounded-full {{ $row['avg'] >= 75 ? 'bg-accent' : 'bg-danger' }}" style="width: {{ min(100, max(0, $row['avg'])) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Statistik Kehadiran</h2>
        </div>
        <div class="grid grid-cols-4 divide-x divide-border text-center">
            <div class="p-4">
                <p class="font-display text-xl font-semibold text-text">{{ $attendance['hadir'] }}</p>
                <p class="text-xs text-text-muted mt-1">Hadir</p>
            </div>
            <div class="p-4">
                <p class="font-display text-xl font-semibold text-accent">{{ $attendance['sakit'] }}</p>
                <p class="text-xs text-text-muted mt-1">Sakit</p>
            </div>
            <div class="p-4">
                <p class="font-display text-xl font-semibold text-warning">{{ $attendance['izin'] }}</p>
                <p class="text-xs text-text-muted mt-1">Izin</p>
            </div>
            <div class="p-4">
                <p class="font-display text-xl font-semibold text-danger">{{ $attendance['alpha'] }}</p>
                <p class="text-xs text-text-muted mt-1">Alpha</p>
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Tugas & PR</h2>
        </div>
        @if($assignments->isEmpty())
            <div class="px-4 py-6 text-center text-sm text-text-muted">Tidak ada tugas untuk rombel ini.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Judul</th>
                            <th>Batas</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $item)
                            <tr>
                                <td class="font-medium">{{ $item['assignment']->title }}</td>
                                <td class="text-text-muted">{{ $item['assignment']->deadline_label }}</td>
                                <td>
                                    <span class="badge {{ $item['submitted'] ? 'badge-success' : 'badge-warning' }}">
                                        {{ $item['submitted'] ? 'Dikumpulkan' : 'Belum' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@if($recentAttendance->isNotEmpty())
    <div class="card overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Riwayat Presensi Terakhir</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th>Sumber</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentAttendance as $a)
                        <tr>
                            <td class="text-text-muted">{{ $a->date?->translatedFormat('d M Y') }}</td>
                            <td class="text-text-muted">{{ $a->time }}</td>
                            <td>
                                <span class="badge">{{ match($a->type) { 'gate_in' => 'Masuk', 'gate_out' => 'Pulang', 'lesson' => 'Pelajaran', default => '-' } }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $a->status === 'hadir' ? 'badge-success' : 'badge-warning' }}">{{ ucfirst($a->status) }}</span>
                            </td>
                            <td class="text-text-muted">{{ $a->source }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection