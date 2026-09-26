@extends('layouts.app')

@section('title', 'Progres Siswa')
@section('content')
<x-page-head title="{{ $student->name }}"
    description="NISN {{ $student->nisn }} &middot; Rombel {{ $student->rombel->name ?? '-' }}"
    eyebrow="Kesiswaan">
    <x-slot name="actions">
        <a href="{{ route('students.rapor', $student) }}" class="btn btn-primary" target="_blank">Cetak Rapor</a>
        <a href="{{ route('students.show', $student) }}" class="btn btn-ghost">Detail Siswa</a>
    </x-slot>
</x-page-head>

@if($ewsLogs->isNotEmpty())
    @php $openEws = $ewsLogs->where('is_resolved', false)->count(); @endphp
    <div class="panel p-5 mb-6 border-danger/40">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-danger mb-3">Peringatan Dini (EWS)</h2>
                <div class="space-y-2">
                    @foreach($ewsLogs as $log)
                        <div class="flex items-start justify-between gap-4 text-sm">
                            <span class="text-text">{{ $log->description }}</span>
                            <span class="badge {{ $log->is_resolved ? 'badge-success' : 'badge-danger' }} shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                {{ $log->is_resolved ? 'Selesai' : 'Aktif' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
            @if($openEws > 0)
                <span class="badge badge-danger shrink-0 font-display">{{ $openEws }}</span>
            @endif
        </div>
    </div>
@endif

@if($bySubject->isEmpty() && $scoreTrend->isEmpty())
    <div class="panel p-12 text-center mb-6">
        <span class="empty-state__icon" aria-hidden="true">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </span>
        <p class="empty-state__title">Belum ada nilai tercatat</p>
        <p class="empty-state__hint">Nilai siswa akan muncul setelah penilaian diinput.</p>
    </div>
@else
    @if($bySubject->isNotEmpty())
        <div class="panel overflow-hidden mb-6">
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
                                    <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
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

    @if($scoreTrend->isNotEmpty())
        <div class="panel overflow-hidden mb-6">
            <div class="px-4 py-3 bg-surface border-b border-border">
                <h2 class="text-sm font-medium text-text">Tren Nilai per Tahun Ajaran</h2>
            </div>
            <div class="p-4 space-y-4">
                @foreach($scoreTrend as $trend)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-text">
                                {{ $trend['year'] }}
                                @if($trend['semester'])
                                    <span class="text-text-muted font-normal">({{ ucfirst($trend['semester']) }})</span>
                                @endif
                            </span>
                            <span class="text-sm text-text-muted">Rata-rata: <strong class="text-text">{{ $trend['avg'] }}</strong></span>
                        </div>
                        <div class="h-2 rounded-full bg-surface overflow-hidden">
                            <div class="h-full rounded-full {{ $trend['avg'] >= 75 ? 'bg-accent' : 'bg-danger' }}" style="width: {{ min(100, max(0, $trend['avg'])) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="panel overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Statistik Kehadiran</h2>
        </div>
        <div class="grid grid-cols-4 divide-x divide-border text-center">
            <div class="p-4">
                <p class="font-display text-xl font-semibold text-text">{{ $attendance['hadir'] }}</p>
                <p class="text-xs text-text-muted mt-1">Hadir</p>
            </div>
            <div class="p-4">
                <p class="font-display text-xl font-semibold text-accent-deep">{{ $attendance['sakit'] }}</p>
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

    <div class="panel overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Tugas & PR</h2>
        </div>
        @if($assignments->isEmpty())
            <div class="px-4 py-6 text-center text-sm text-text-muted">Tidak ada tugas untuk rombel ini.</div>
        @else
            <div class="table-wrap">
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
                                <td class="font-medium text-text">{{ $item['assignment']->title }}</td>
                                <td class="text-text-muted">{{ $item['assignment']->deadline_label }}</td>
                                <td>
                                    <span class="badge {{ $item['submitted'] ? 'badge-success' : 'badge-warning' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
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
    <div class="panel overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Riwayat Presensi Terakhir</h2>
        </div>
        <div class="table-wrap">
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
                                <span class="badge {{ $a->status === 'hadir' ? 'badge-success' : 'badge-warning' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ ucfirst($a->status) }}
                                </span>
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