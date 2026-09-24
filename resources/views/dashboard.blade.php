@extends('layouts.app')

@section('title', 'Dashboard')
@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-widest text-accent">
                {{ $stats['active_academic_year'] ?? ($user->tenant?->name ?? 'Smart School') }}
            </p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-text">
                Selamat {{ now()->format('H') < 12 ? 'Pagi' : (now()->format('H') < 15 ? 'Siang' : 'Sore') }}, {{ $user->name }}.
            </h1>
            <div class="mt-3 w-16 h-0.5 bg-accent"></div>
            <p class="mt-4 text-sm text-text-muted">
                {{ now()->translatedFormat('l, d F Y') }}
            </p>
        </div>

        @if($user->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('attendance.manual') }}" class="btn btn-primary btn-sm">Absensi Manual</a>
                <a href="{{ route('attendance.scan') }}" class="btn btn-accent btn-sm">Mode Scan</a>
                <a href="{{ route('attendance.index') }}" class="btn btn-ghost btn-sm">Riwayat</a>
            </div>
        @endif
    </div>

    @if($user->role === 'super_admin' || $user->role === 'admin_sekolah')
        @include('dashboard.admin')
    @elseif($user->isGuru())
        @include('dashboard.guru')
    @elseif($user->isSiswa())
        @include('dashboard.siswa')
    @elseif($user->isOrangTua())
        @include('dashboard.orang-tua')
    @endif
</div>
@endsection