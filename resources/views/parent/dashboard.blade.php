@extends('layouts.app')

@section('title', 'Portal Orang Tua')
@section('content')
<div class="max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="font-display text-3xl font-semibold text-text">Portal Orang Tua</h1>
        <div class="mt-3 w-16 h-0.5 bg-accent"></div>
        <p class="mt-4 text-sm text-text-muted">Pantau perkembangan anak Anda.</p>
    </div>

    @if($warnings->isNotEmpty())
        <div class="mb-6 bg-white border border-danger rounded-lg px-6 py-5 shadow-card" role="alert">
            <h2 class="font-display text-lg font-semibold text-danger mb-3">Peringatan Dini (EWS)</h2>
            <div class="space-y-2">
                @foreach($warnings as $w)
                    <div class="flex items-start gap-2 text-sm">
                        <span class="w-2 h-2 rounded-full bg-danger mt-1.5 shrink-0"></span>
                        <span class="text-danger"><strong>{{ $w->student->name ?? '-' }}:</strong> {{ $w->description }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @forelse($children as $child)
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                <h2 class="font-display text-xl font-semibold text-text">{{ $child->name }}</h2>
                @if($child->rombel)
                    <span class="badge">{{ $child->rombel->name }}</span>
                @endif
                <div class="ml-auto flex items-center gap-3 text-sm">
                    <a href="{{ route('students.progress', $child) }}" class="text-text-muted hover:text-accent transition-colors">Monitor</a>
                    <a href="{{ route('students.rapor', $child) }}" class="text-text-muted hover:text-accent transition-colors" target="_blank">Cetak Rapor</a>
                </div>
            </div>

            @php $avg = $avgScores->get($child->id, []); @endphp
            @if($avg)
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-4">
                    <div class="card p-4 text-center">
                        <p class="text-xs text-text-muted">Tugas</p>
                        <p class="font-display text-lg font-semibold mt-1 {{ ($avg['tugas'] ?? 0) >= 75 ? 'text-text' : 'text-danger' }}">{{ number_format($avg['tugas'] ?? 0, 1) }}</p>
                    </div>
                    <div class="card p-4 text-center">
                        <p class="text-xs text-text-muted">Formatif</p>
                        <p class="font-display text-lg font-semibold mt-1 {{ ($avg['formatif'] ?? 0) >= 75 ? 'text-text' : 'text-danger' }}">{{ number_format($avg['formatif'] ?? 0, 1) }}</p>
                    </div>
                    <div class="card p-4 text-center">
                        <p class="text-xs text-text-muted">UTS</p>
                        <p class="font-display text-lg font-semibold mt-1 {{ ($avg['uts'] ?? 0) >= 75 ? 'text-text' : 'text-danger' }}">{{ number_format($avg['uts'] ?? 0, 1) }}</p>
                    </div>
                    <div class="card p-4 text-center">
                        <p class="text-xs text-text-muted">UAS</p>
                        <p class="font-display text-lg font-semibold mt-1 {{ ($avg['uas'] ?? 0) >= 75 ? 'text-text' : 'text-danger' }}">{{ number_format($avg['uas'] ?? 0, 1) }}</p>
                    </div>
                    <div class="card p-4 text-center border-accent">
                        <p class="text-xs text-text-muted">Rata-rata</p>
                        <p class="font-display text-lg font-semibold mt-1 text-accent">{{ number_format($avg['overall'] ?? 0, 1) }}</p>
                    </div>
                </div>
            @endif

            <div class="card overflow-hidden mb-4">
                <div class="px-4 py-3 bg-surface border-b border-border">
                    <h3 class="text-sm font-medium text-text">Presensi 7 Hari Terakhir</h3>
                </div>
                @php $childAttendances = $recentAttendance->where('student_id', $child->id); @endphp
                @if($childAttendances->isEmpty())
                    <div class="px-4 py-6 text-center text-sm text-text-muted">Tidak ada presensi tercatat.</div>
                @else
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
                            @foreach($childAttendances as $a)
                                <tr>
                                    <td>{{ $a->date?->translatedFormat('d M Y') }}</td>
                                    <td class="text-text-muted">{{ $a->time }}</td>
                                    <td>
                                        <span class="badge">
                                            {{ match($a->type) { 'gate_in' => 'Masuk', 'gate_out' => 'Pulang', 'lesson' => 'Pelajaran', default => '-' } }}
                                        </span>
                                    </td>
                                    <td>{{ ucfirst($a->status) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
        @unless($loop->last)
            <hr class="border-border mb-8">
        @endunless
    @empty
        <div class="card p-12 text-center">
            <p class="text-text-muted">Anda belum terhubung dengan siswa mana pun. Hubungi admin sekolah untuk menghubungkan akun orang tua Anda.</p>
        </div>
    @endforelse
</div>
@endsection