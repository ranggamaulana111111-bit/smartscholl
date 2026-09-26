@extends('layouts.app')

@section('title', 'Portal Orang Tua')
@section('content')
<div class="max-w-5xl mx-auto">
    <x-page-head title="Portal Orang Tua"
        description="Pantau perkembangan anak Anda."
        eyebrow="Orang Tua">
    </x-page-head>

    @if($warnings->isNotEmpty())
        <div class="panel p-5 mb-8 border-danger/40" role="alert">
            <h2 class="text-sm font-semibold text-danger mb-3">Peringatan Dini (EWS)</h2>
            <div class="space-y-2">
                @foreach($warnings as $w)
                    <div class="flex items-start gap-2 text-sm">
                        <span class="w-2 h-2 rounded-full bg-danger mt-1.5 shrink-0" aria-hidden="true"></span>
                        <span class="text-text"><strong>{{ $w->student->name ?? '-' }}:</strong> {{ $w->description }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @forelse($children as $child)
        <section class="mb-10">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <h2 class="font-display text-xl font-semibold text-text">{{ $child->name }}</h2>
                @if($child->rombel)
                    <span class="badge">{{ $child->rombel->name }}</span>
                @endif
                <div class="ml-auto flex items-center gap-3 text-sm">
                    <a href="{{ route('students.progress', $child) }}" class="btn btn-ghost btn-sm">Monitor</a>
                    <a href="{{ route('students.rapor', $child) }}" class="btn btn-accent btn-sm" target="_blank">Cetak Rapor</a>
                </div>
            </div>

            @php $avg = $avgScores->get($child->id, []); @endphp
            @if($avg)
                <div class="bento mb-4">
                    @foreach([
                        ['Tugas', 'tugas'], ['Formatif', 'formatif'], ['UTS', 'uts'], ['UAS', 'uas'],
                    ] as [$label, $key])
                        <div class="bento-card bento-card--surface lg:col-span-2">
                            <div class="bento-card__body !py-5">
                                <p class="metric__label !text-center">{{ $label }}</p>
                                <p class="font-display text-2xl font-semibold mt-1 text-center {{ number_format($avg[$key] ?? 0, 1) >= 75 ? 'text-text' : 'text-danger' }}">{{ number_format($avg[$key] ?? 0, 1) }}</p>
                            </div>
                        </div>
                    @endforeach
                    <div class="bento-card bento-card--inverse lg:col-span-4">
                        <div class="bento-card__body !py-5">
                            <p class="metric__label !text-center">Rata-rata</p>
                            <p class="font-display text-2xl font-semibold mt-1 text-center text-accent">{{ number_format($avg['overall'] ?? 0, 1) }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="panel overflow-hidden">
                <div class="px-4 py-3 bg-surface border-b border-border">
                    <h3 class="text-sm font-medium text-text">Presensi 7 Hari Terakhir</h3>
                </div>
                @php $childAttendances = $recentAttendance->where('student_id', $child->id); @endphp
                @if($childAttendances->isEmpty())
                    <div class="px-4 py-6 text-center text-sm text-text-muted">Tidak ada presensi tercatat.</div>
                @else
                    <div class="table-wrap">
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
                                                <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                                {{ match($a->type) { 'gate_in' => 'Masuk', 'gate_out' => 'Pulang', 'lesson' => 'Pelajaran', default => '-' } }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge {{ $a->status === 'hadir' ? 'badge-success' : ($a->status === 'sakit' ? 'badge-warning' : ($a->status === 'alpha' ? 'badge-danger' : '')) }}">
                                                <span class="w-1.5 h-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                                {{ ucfirst($a->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @empty
        <div class="panel p-12 text-center">
            <span class="empty-state__icon" aria-hidden="true">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </span>
            <p class="empty-state__title">Belum terhubung dengan siswa</p>
            <p class="empty-state__hint">Hubungi admin sekolah untuk menghubungkan akun orang tua Anda.</p>
        </div>
    @endforelse
</div>
@endsection