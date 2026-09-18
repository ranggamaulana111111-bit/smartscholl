@extends('layouts.app')

@section('content')
<div class="min-h-screen grid grid-cols-1 lg:grid-cols-2 bg-background">
    <div class="hidden lg:flex flex-col justify-between bg-primary p-12 text-on-primary">
        <div class="flex items-center gap-3">
            <span class="w-10 h-10 rounded-lg bg-primary-hover text-accent flex items-center justify-center font-bold text-xl">S</span>
            <span class="text-2xl font-bold">Smart School</span>
        </div>
        <div class="max-w-sm">
            <h2 class="text-3xl font-bold leading-snug" style="letter-spacing:-0.02em">
                Sistem operasi sekolah dalam satu tempat.
            </h2>
            <div class="mt-5 w-16 h-0.5 bg-accent"></div>
            <p class="mt-5 text-sm leading-relaxed text-white/70">
                Presensi, nilai akademik, jurnal kelas, peringatan dini, dan portal orang tua.
            </p>
        </div>
        <p class="text-xs text-white/40">&copy; {{ date('Y') }} Smart School Enterprise</p>
    </div>

    <div class="flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md">
            <div class="lg:hidden mb-8 text-center">
                <span class="inline-flex w-10 h-10 rounded-lg bg-primary text-accent items-center justify-center font-bold text-xl">S</span>
                <h1 class="mt-3 text-2xl font-bold text-text">Smart School</h1>
            </div>

            <div class="hidden lg:block mb-8">
                <h1 class="text-3xl font-bold text-text" style="letter-spacing:-0.02em">Selamat datang</h1>
                <div class="mt-3 w-16 h-0.5 bg-accent"></div>
            </div>

            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-danger rounded-lg text-sm text-danger flex items-center gap-2" role="alert" aria-live="assertive">
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
                        class="w-full px-4 py-3 bg-white border border-border rounded-lg text-sm text-text placeholder:text-forest-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-primary transition-all duration-fast"
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
                        class="w-full px-4 py-3 bg-white border border-border rounded-lg text-sm text-text placeholder:text-forest-400 focus:outline-none focus:ring-2 focus:ring-accent focus:border-primary transition-all duration-fast"
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
