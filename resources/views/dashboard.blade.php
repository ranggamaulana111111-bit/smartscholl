@extends('layouts.app')

@section('title', 'Dashboard')
@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between mb-2">
        <div>
            <p class="eyebrow">{{ $user->tenant?->name ?? 'Smart School Enterprise' }}</p>
            <h1 class="mt-3 display-title text-3xl lg:text-[40px]">
                Selamat {{ now()->format('H') < 12 ? 'Pagi' : (now()->format('H') < 15 ? 'Siang' : 'Sore') }}, {{ $user->name }}.
            </h1>
            <p class="mt-3 text-[15px] text-text-muted">
                {{ now()->translatedFormat('l, d F Y') }} &middot; ringkasan untuk peran Anda hari ini.
            </p>
        </div>

        @if($user->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
            <div class="flex flex-wrap gap-2 shrink-0">
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