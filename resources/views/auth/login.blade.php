@extends('layouts.app')

@section('content')
<div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">
    {{-- Editorial brand panel --}}
    <div class="hidden lg:flex flex-col justify-between bg-primary p-12 xl:p-16 text-on-primary overflow-hidden relative">
        <div class="absolute inset-x-0 top-0 h-1 bg-accent" aria-hidden="true"></div>
        <div class="flex items-center gap-3">
            <span class="brand-mark brand-mark--lg" aria-hidden="true">{{ setting('sistem.brand_glyph', 'S') }}</span>
            <div class="min-w-0">
                <p class="font-display text-2xl font-semibold text-white">{{ setting('sistem.app_name', 'Smart School') }}</p>
                <p class="text-sm text-white/60">{{ setting('sistem.app_name', 'Smart School') }} Enterprise</p>
            </div>
        </div>
        <div class="max-w-md flex flex-col gap-6">
            <p class="eyebrow !text-accent">Sistem Operasi Sekolah</p>
            <h2 class="font-display text-4xl xl:text-[44px] font-semibold leading-[1.12] text-white">
                Satu kampus digital, terkelola dari satu tempat.
            </h2>
            <div class="rule-accent"></div>
            <ul class="space-y-3 text-[15px] leading-relaxed text-white/75">
                <li class="flex items-start gap-3">
                    <span class="mt-2 w-1.5 h-1.5 rounded-full bg-accent shrink-0" aria-hidden="true"></span>
                    Presensi QR &amp; RFID yang tercatat real-time
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-2 w-1.5 h-1.5 rounded-full bg-accent shrink-0" aria-hidden="true"></span>
                    Akademik, jurnal kelas, dan rapor otomatis
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-2 w-1.5 h-1.5 rounded-full bg-accent shrink-0" aria-hidden="true"></span>
                    Peringatan dini dan portal orang tua yang transparan
                </li>
            </ul>
        </div>
        <p class="text-xs text-white/40">&copy; {{ date('Y') }} Smart School Enterprise</p>
    </div>

    {{-- Form --}}
    <div class="flex items-center justify-center p-6 lg:p-12 bg-background">
        <div class="w-full max-w-md">
            <div class="lg:hidden mb-8 flex flex-col items-center gap-4 text-center">
                <span class="brand-mark brand-mark--lg" aria-hidden="true">{{ setting('sistem.brand_glyph', 'S') }}</span>
                <div>
                    <p class="eyebrow">Sistem Operasi Sekolah</p>
                    <h1 class="mt-2 font-display text-2xl font-semibold text-text">{{ setting('sistem.app_name', 'Smart School') }}</h1>
                </div>
            </div>

            <div class="hidden lg:block mb-10">
                <p class="eyebrow">Selamat datang</p>
                <h1 class="mt-3 display-title text-4xl">Masuk ke portal</h1>
            </div>

            @if($errors->any())
                <div class="mb-6 p-4 bg-danger/10 border-l-4 border-danger rounded-lg text-sm text-danger flex items-center gap-2" role="alert" aria-live="assertive">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <div>
                    <label for="email" class="mb-1.5 block text-text">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="input"
                        placeholder="nama@sekolah.ac.id"
                        aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                    >
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-text">Kata Sandi</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="input"
                        placeholder="Masukkan kata sandi"
                    >
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-text-muted cursor-pointer min-h-[44px]">
                        <input type="checkbox" id="remember" name="remember" class="h-5 w-5 rounded border-border text-primary focus:ring-accent">
                        Ingat saya
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    Masuk
                </button>
            </form>
        </div>
    </div>
</div>
@endsection