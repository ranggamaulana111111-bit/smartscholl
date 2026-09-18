@extends('layouts.app')

@section('title', 'Detail '.$student->name)
@section('content')
<div class="mb-6">
    <a href="{{ route('students.index') }}" class="text-sm text-text-muted hover:text-accent transition-colors">&larr; Kembali</a>
    <h1 class="font-display text-2xl font-semibold text-text mt-2">Detail Siswa</h1>
</div>

<div class="card p-6 mb-6">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
        <div>
            <p class="text-xs text-text-muted uppercase">NISN</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $student->nisn }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">NIS</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $student->nis ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Nama</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $student->name }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Jenis Kelamin</p>
            <p class="mt-1 font-display text-lg font-semibold text-text">{{ $student->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</p>
        </div>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mt-5">
        <div>
            <p class="text-xs text-text-muted uppercase">Rombel</p>
            <p class="mt-1">
                @if($student->rombel)
                    <span class="badge">{{ $student->rombel->name }}</span>
                @else
                    <span class="text-text-muted">-</span>
                @endif
            </p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Tahun Ajaran</p>
            <p class="mt-1 font-medium text-text">{{ $student->rombel?->academicYear?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Tanggal Lahir</p>
            <p class="mt-1 font-medium text-text">{{ $student->birth_date?->translatedFormat('d M Y') ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Tempat Lahir</p>
            <p class="mt-1 font-medium text-text">{{ $student->birth_place ?? '-' }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5">
        <div>
            <p class="text-xs text-text-muted uppercase">Alamat</p>
            <p class="mt-1 text-sm text-text">{{ $student->address ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Telepon</p>
            <p class="mt-1 text-sm text-text">{{ $student->phone ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Akun Login</p>
            <p class="mt-1 text-sm text-text">{{ $student->user?->email ?? '-' }}</p>
        </div>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mt-5 pt-5 border-t border-border">
        <div>
            <p class="text-xs text-text-muted uppercase">UID RFID</p>
            <p class="mt-1 text-sm font-mono text-text">{{ $student->rfid_uid ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Token QR</p>
            <p class="mt-1 text-xs font-mono text-text break-all">{{ $student->qr_token ?? '-' }}</p>
        </div>
        <div>
            <p class="text-xs text-text-muted uppercase">Kartu QR</p>
            <p class="mt-1"><a href="{{ route('attendance.qrcode', $student) }}" target="_blank" class="text-sm text-accent hover:underline">Lihat / cetak QR</a></p>
        </div>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach(['hadir' => 'Hadir', 'sakit' => 'Sakit', 'izin' => 'Izin', 'alpha' => 'Alpha', 'total' => 'Total Presensi'] as $key => $label)
        <div class="card p-5 text-center">
            <p class="text-xs text-text-muted uppercase">{{ $label }}</p>
            <p class="mt-1 font-display text-2xl font-semibold {{ $key === 'alpha' && ($attendance[$key] ?? 0) > 3 ? 'text-danger' : 'text-text' }}">{{ $attendance[$key] ?? 0 }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="card overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Presensi Terbaru</h2>
        </div>
        @if($recentAttendance->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-text-muted">Belum ada presensi tercatat.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Jenis</th>
                            <th>Status</th>
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
                                <td>{{ ucfirst($a->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="px-4 py-3 bg-surface border-b border-border">
            <h2 class="text-sm font-medium text-text">Ringkasan Nilai per Mapel</h2>
        </div>
        @if($bySubject->isEmpty())
            <div class="px-4 py-8 text-center text-sm text-text-muted">Belum ada nilai tercatat.</div>
        @else
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mapel</th>
                            <th class="text-right">Rata-rata</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bySubject as $row)
                            <tr>
                                <td class="font-medium text-text">{{ $row['name'] }}</td>
                                <td class="text-right font-display font-semibold {{ $row['avg'] >= 75 ? 'text-text' : 'text-danger' }}">{{ $row['avg'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="flex items-center gap-3">
    <a href="{{ route('students.progress', $student) }}" class="btn btn-accent">Monitor Perkembangan</a>
    <a href="{{ route('students.rapor', $student) }}" class="btn btn-primary" target="_blank">Cetak Rapor</a>
    <a href="{{ route('students.edit', $student) }}" class="btn btn-ghost">Edit</a>
    <a href="{{ route('students.index') }}" class="btn btn-ghost">Kembali</a>
</div>
@endsection