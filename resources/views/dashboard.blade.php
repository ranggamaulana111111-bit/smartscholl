@extends('layouts.app')

@section('title', 'Dashboard')
@section('content')
<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-text" style="letter-spacing:-0.02em">Dashboard</h1>
        <div class="mt-3 w-16 h-0.5 bg-accent"></div>
        <p class="mt-4 text-sm text-text-muted">Selamat datang, {{ $user->name }}</p>
    </div>

    @if($user->role === 'super_admin')
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="card p-6">
                <p class="text-xs text-text-muted">Total Sekolah</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['total_tenants'] }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Total Pengguna</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['total_users'] }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Siswa</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['total_students'] }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Guru</p>
                <p class="mt-2 text-4xl font-bold text-accent">{{ $stats['total_teachers'] }}</p>
            </div>
        </div>

        <div class="mt-12">
            <h2 class="text-xl font-bold text-text mb-4">Daftar Sekolah</h2>
            <div class="card overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Sekolah</th>
                            <th>Domain</th>
                            <th>Pengguna</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $tenant)
                            <tr>
                                <td class="font-semibold text-text">{{ $tenant['name'] }}</td>
                                <td class="text-text-muted">{{ $tenant['domain'] }}</td>
                                <td class="text-text-muted">{{ $tenant['user_count'] }}</td>
                                <td>
                                    @if($tenant['status'] === 'active')
                                        <span class="badge-success">Aktif</span>
                                    @else
                                        <span class="badge-danger">Nonaktif</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-text-muted py-10">Belum ada sekolah terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($user->role === 'admin_sekolah')
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="card p-6">
                <p class="text-xs text-text-muted">Siswa</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['total_students'] }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Guru</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['total_teachers'] }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Hadir Hari Ini</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['today_present'] ?? 0 }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Tidak Hadir</p>
                <p class="mt-2 text-4xl font-bold text-danger">{{ $stats['today_absent'] ?? 0 }}</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="card p-6">
                <p class="text-xs text-text-muted">Jadwal Mengajar Hari Ini</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['today_schedules'] ?? 0 }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Guru Mengajar Hari Ini</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['teachers_teaching_today'] ?? 0 }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Guru Tanpa Jadwal Hari Ini</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['teachers_idle_today'] ?? 0 }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Rombel</p>
                <p class="mt-2 text-4xl font-bold text-accent">{{ $stats['total_rombels'] }}</p>
            </div>
        </div>

        @if(($stats['pending_journals'] ?? 0) > 0 || ($stats['unresolved_ews'] ?? 0) > 0)
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @if(($stats['pending_journals'] ?? 0) > 0)
                    <div class="p-5 rounded-lg border-l-4 border-warning bg-orange-50 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-warning/20 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-warning" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-text">{{ $stats['pending_journals'] }} Jurnal Hari Ini</p>
                            <p class="text-xs text-text-muted">Belum diisi oleh guru</p>
                        </div>
                    </div>
                @endif
                @if(($stats['unresolved_ews'] ?? 0) > 0)
                    <a href="{{ route('ews.index') }}" class="p-5 rounded-lg border-l-4 border-danger bg-red-50 flex items-center gap-3 hover:bg-red-100 transition-colors duration-fast min-h-[44px]">
                        <div class="w-10 h-10 rounded-full bg-danger/20 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-text">{{ $stats['unresolved_ews'] }} Peringatan Dini</p>
                            <p class="text-xs text-text-muted">Klik untuk melihat detail</p>
                        </div>
                    </a>
                @endif
            </div>
        @endif

        <div class="mt-12">
            <h2 class="text-xl font-bold text-text mb-4">Pengguna Terbaru</h2>
            <div class="card overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Peran</th>
                            <th>Bergabung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentUsers as $userRow)
                            <tr>
                                <td class="font-semibold text-text">{{ $userRow['name'] }}</td>
                                <td class="text-text-muted">{{ $userRow['email'] }}</td>
                                <td><span class="badge">{{ str_replace('_', ' ', ucfirst($userRow['role'])) }}</span></td>
                                <td class="text-text-muted">{{ $userRow['created_at']?->translatedFormat('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-text-muted py-10">Belum ada pengguna dalam sekolah ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-12">
            <h2 class="text-xl font-bold text-text mb-4">Aktivitas Presensi Hari Ini</h2>
            <div class="card overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Siswa</th>
                            <th>Jenis</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAttendances as $att)
                            <tr>
                                <td class="text-text-muted">{{ $att->time }}</td>
                                <td class="font-semibold text-text">{{ $att->student?->name ?? '-' }}</td>
                                <td><span class="badge">{{ match($att->type) { 'gate_in' => 'Masuk', 'gate_out' => 'Pulang', 'lesson' => 'Pelajaran' } }}</span></td>
                                <td class="text-text-muted">{{ ucfirst($att->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-text-muted py-10">Belum ada presensi tercatat hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($user->isGuru())
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
            <div class="card p-6">
                <p class="text-xs text-text-muted">Total Jurnal</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['total_journals'] }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Jurnal Draft</p>
                <p class="mt-2 text-4xl font-bold text-accent">{{ $stats['draft_journals'] }}</p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Siswa Binaan</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['homeroom_students'] }}</p>
            </div>
        </div>

        <div>
            <h2 class="text-xl font-bold text-text mb-4">Jadwal Hari Ini</h2>
            @if($stats['today_schedule']->isEmpty())
                <div class="card p-8 text-center">
                    <p class="text-text-muted">Tidak ada jadwal mengajar hari ini.</p>
                </div>
            @else
                <div class="card overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Jam</th>
                                <th>Mapel</th>
                                <th>Rombel</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['today_schedule'] as $s)
                                <tr>
                                    <td class="font-semibold text-text">{{ substr($s->start_time, 0, 5) }} - {{ substr($s->end_time, 0, 5) }}</td>
                                    <td class="text-text-muted">{{ $s->subject->name ?? '-' }}</td>
                                    <td><span class="badge">{{ $s->rombel->name ?? '-' }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    @elseif($user->isSiswa())
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="card p-6">
                <p class="text-xs text-text-muted">Rata-rata Nilai</p>
                <p class="mt-2 text-4xl font-bold {{ ($stats['avg_score'] ?? 0) >= 75 ? 'text-text' : 'text-danger' }}">
                    {{ $stats['avg_score'] ?? 0 }}
                </p>
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Presensi Hari Ini</p>
                @if($stats['present_today'] ?? false)
                    <p class="mt-2 inline-flex items-center gap-2 text-2xl font-bold text-success">
                        <span class="w-3 h-3 rounded-full bg-success"></span> Hadir
                    </p>
                @else
                    <p class="mt-2 inline-flex items-center gap-2 text-2xl font-bold text-danger">
                        <span class="w-3 h-3 rounded-full bg-danger"></span> Belum
                    </p>
                @endif
            </div>
            <div class="card p-6">
                <p class="text-xs text-text-muted">Alpha (30 hari)</p>
                <p class="mt-2 text-4xl font-bold {{ ($stats['absence_count'] ?? 0) > 3 ? 'text-danger' : 'text-text' }}">{{ $stats['absence_count'] ?? 0 }}</p>
            </div>
        </div>

        <div class="card p-6 mt-8">
            <h2 class="text-xl font-bold text-text mb-3">Nilai Saya</h2>
            <p class="text-sm text-text-muted">Lihat detail nilai melalui menu <a href="{{ route('assessments.index') }}" class="text-accent hover:text-accent-hover underline">Penilaian</a>.</p>
        </div>

    @elseif($user->isOrangTua())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="card p-6">
                <p class="text-xs text-text-muted">Anak Terhubung</p>
                <p class="mt-2 text-4xl font-bold text-text">{{ $stats['children_count'] }}</p>
            </div>
            <a href="{{ route('ews.index') }}" class="card p-6 flex items-center justify-between hover:bg-red-50 border-danger/30 min-h-[44px]">
                <div>
                    <p class="text-xs text-text-muted">Peringatan Aktif</p>
                    <p class="mt-2 text-4xl font-bold text-danger">{{ $stats['warning_count'] }}</p>
                </div>
                <svg class="w-6 h-6 text-danger" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </a>
        </div>

        <div class="card p-6 mt-8">
            <h2 class="text-xl font-bold text-text mb-3">Portal Anak</h2>
            <p class="text-sm text-text-muted">Lihat detail kehadiran dan nilai anak Anda melalui menu <a href="{{ route('parent.dashboard') }}" class="text-accent hover:text-accent-hover underline">Portal Anak</a>.</p>
        </div>
    @endif
</div>
@endsection
