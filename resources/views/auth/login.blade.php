@extends('layouts.app')

@section('content')
@php
    $appName = setting('sistem.app_name', 'Smart School');
    $brandGlyph = mb_strtoupper(mb_substr(setting('sistem.brand_glyph', 'S'), 0, 1));
@endphp

<div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">

    {{-- Panel kiri: identitas dan isi sistem, hanya di layar lebar --}}
    <div class="hidden lg:flex flex-col justify-between bg-primary p-12 xl:p-16 text-on-primary overflow-hidden relative">
        <div class="absolute inset-x-0 top-0 h-1 bg-accent" aria-hidden="true"></div>

        <div class="flex items-center gap-3">
            <span class="brand-mark brand-mark--lg" aria-hidden="true">{{ $brandGlyph }}</span>
            <div class="min-w-0">
                <p class="font-display text-2xl font-semibold text-white">{{ $appName }}</p>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-white/60">Enterprise</p>
            </div>
        </div>

        <div class="max-w-md">
            <p class="eyebrow !text-accent">Sistem Operasi Sekolah</p>
            <h2 class="mt-4 font-display text-[clamp(2rem,2.6vw,2.75rem)] font-semibold leading-[1.12] tracking-[-0.015em] text-white">
                Ruang kerja yang sama untuk kepala sekolah, guru, dan orang tua.
            </h2>
            <div class="mt-6 rule-accent"></div>
            <ul class="mt-7 space-y-4 text-[15px] leading-relaxed text-white/75">
                <li class="flex items-start gap-3">
                    <span class="mt-[9px] w-1.5 h-1.5 rounded-full bg-accent shrink-0" aria-hidden="true"></span>
                    Presensi QR dan RFID tercatat di gerbang maupun di kelas.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-[9px] w-1.5 h-1.5 rounded-full bg-accent shrink-0" aria-hidden="true"></span>
                    Nilai, jurnal KBM, dan rapor tersusun dari satu data yang sama.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-[9px] w-1.5 h-1.5 rounded-full bg-accent shrink-0" aria-hidden="true"></span>
                    Peringatan dini dan portal orang tua berjalan di atas data itu juga.
                </li>
            </ul>
        </div>

        <div>
            <div class="rule-accent mb-5"></div>
            <p class="text-xs text-white/60 leading-relaxed max-w-sm">
                Setiap sekolah berjalan di atas data sendiri yang terisolasi dari sekolah lain.
            </p>
            <p class="mt-4 text-xs text-white/60">&copy; {{ date('Y') }} {{ $appName }} Enterprise</p>
        </div>
    </div>

    {{-- Panel kanan: formulir masuk --}}
    <div class="flex items-center justify-center px-4 sm:px-6 py-10 lg:p-12 bg-background">
        <div class="w-full max-w-md">

            <div class="lg:hidden mb-8 flex items-center gap-3">
                <span class="brand-mark" aria-hidden="true">{{ $brandGlyph }}</span>
                <div class="min-w-0">
                    <p class="font-display text-lg font-semibold text-text leading-tight">{{ $appName }}</p>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-text-muted">Enterprise</p>
                </div>
            </div>

            <div class="panel p-6 sm:p-8 lg:p-10">
                <p class="eyebrow">Selamat datang</p>
                <h1 class="mt-3 display-title text-[clamp(1.625rem,3vw,2.125rem)] leading-[1.15]">Masuk ke portal</h1>
                <p class="mt-3 text-sm text-text-muted leading-relaxed">
                    Gunakan email dan kata sandi yang diberikan admin sekolah.
                </p>

                @if($errors->any())
                    <div class="mt-6 flex items-start gap-2.5 rounded-lg border border-danger/30 border-l-4 border-l-danger bg-danger/10 p-3.5 text-sm text-danger" role="alert" aria-live="assertive">
                        <svg class="w-5 h-5 shrink-0 mt-px" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5" data-busy>
                    @csrf

                    <div>
                        <label for="email" class="label">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            inputmode="email"
                            class="input @error('email') border-danger @enderror"
                            placeholder="nama@sekolah.ac.id"
                            @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                        >
                        @error('email')
                            <p id="email-error" class="error-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="label">Kata Sandi</label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="input pr-14 @error('password') border-danger @enderror"
                                placeholder="Masukkan kata sandi"
                                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                            >
                            <button
                                type="button"
                                data-password-toggle="password"
                                class="absolute right-1 top-1/2 -translate-y-1/2 flex h-11 w-11 items-center justify-center rounded-md text-text-muted hover:text-text hover:bg-surface transition-colors duration-fast"
                                aria-label="Tampilkan kata sandi"
                                aria-controls="password"
                            >
                                <svg data-password-icon="show" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <svg data-password-icon="hide" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.477 10.477a3 3 0 00-.8 3.2m5.846 1.046A9.98 9.98 0 0112 15a3 3 0 01-1.523-.16M6.229 6.229C4.1 7.6 2.7 9.8 2.036 11.683a1.012 1.012 0 000 .639C3.423 16.49 7.36 19.5 12 19.5c1.605 0 3.12-.375 4.478-1.045M9.88 9.88a3 3 0 104.24 4.24"/></svg>
                            </button>
                        </div>
                        @error('password')
                            <p id="password-error" class="error-text">{{ $message }}</p>
                        @enderror
                    </div>

                    <label for="remember" class="label--plain min-h-[44px]">
                        <input type="checkbox" id="remember" name="remember" value="1">
                        <span>Ingat saya di perangkat ini</span>
                    </label>

                    <button type="submit" class="btn btn-primary w-full">
                        Masuk ke Portal
                    </button>
                </form>

                <p class="mt-4 pt-5 border-t border-border text-sm text-text-muted text-center">
                    <a href="{{ route('home') }}" class="link inline-flex items-center min-h-[44px] px-2">Kembali ke beranda</a>
                </p>
            </div>

            <p class="mt-6 text-center text-xs text-text-muted leading-relaxed px-4">
                Akun dibuat oleh admin sekolah. Belum punya akun? Hubungi admin sekolah Anda.
            </p>
        </div>
    </div>
</div>
@endsection
