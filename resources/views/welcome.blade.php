<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart School Enterprise</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @endif
</head>
<body class="bg-background text-text font-sans antialiased" style="line-height:1.65">

    <header class="border-b border-border bg-background sticky top-0 z-10">
        <div class="max-w-[1280px] mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 min-h-[44px]">
                <span class="w-9 h-9 rounded-lg bg-primary text-accent flex items-center justify-center font-bold text-lg">S</span>
                <span class="text-xl font-bold text-text">Smart School</span>
            </a>
            <nav class="flex items-center gap-5 text-sm" aria-label="Navigasi publik">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Masuk</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        <section class="border-b border-border">
            <div class="max-w-[1280px] mx-auto px-6 py-20 lg:py-28 text-center">
                <p class="text-xs font-semibold tracking-wide text-accent uppercase">Sistem Operasi Sekolah</p>
                <h1 class="mt-4 max-w-3xl mx-auto" style="font-size:clamp(32px,5vw,56px);font-weight:700;line-height:1.05;letter-spacing:-0.03em">
                    Satu platform untuk seluruh kegiatan sekolah
                </h1>
                <div class="mt-6 mx-auto w-24 h-0.5 bg-accent"></div>
                <p class="mt-6 text-base max-w-2xl mx-auto" style="line-height:1.65">
                    Presensi real-time, pengelolaan nilai akademik, jurnal kelas, sistem peringatan dini,
                    dan portal orang tua untuk sekolah modern.
                </p>
                <div class="mt-10">
                    <a href="{{ route('login') }}" class="btn btn-accent btn-lg">
                        Masuk ke Portal Sekolah
                    </a>
                </div>
            </div>
        </section>

        <section>
            <div class="max-w-[1280px] mx-auto px-6 py-16 lg:py-20">
                <div class="mb-12">
                    <h2 class="text-2xl font-bold text-text" style="letter-spacing:-0.02em">
                        Jantung sistem operasi sekolah
                    </h2>
                    <div class="mt-3 w-16 h-0.5 bg-accent"></div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 lg:gap-8">
                    @foreach([
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h.01M12 12v4m0-4v-3M12 4l-2 3m2-3l2 3m-6 8h-2m6 0v4m0-4z"/>', 'title' => 'Presensi Real-Time', 'desc' => 'Pemindaian QR Code dan kartu RFID di gerbang maupun kelas. Mode manual tetap tersedia untuk kasus khusus.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-6-8h6M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z"/>', 'title' => 'Nilai Akademik', 'desc' => 'Kelola mata pelajaran, kategori nilai, bobot persentase, dan catatan kompetensi untuk setiap siswa.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M5 19l-2-4 7-10a1.5 1.5 0 014 0l7 10-2 4H5z"/>', 'title' => 'Sistem Peringatan Dini', 'desc' => 'Pantau pelanggaran ambang batas kehadiran dan nilai. Notifikasi otomatis dikirim ke pihak terkait.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.36-1.86M9 20v-2a3 3 0 016 0v2m6 0h-6m-12 0v-2a3 3 0 015.36-1.86M12 6a3 3 0 11-6 0 3 3 0 016 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/>', 'title' => 'Portal Orang Tua', 'desc' => 'Orang tua memantau kehadiran dan perkembangan akademik anak secara transparan.'],
                    ] as $feature)
                        <article class="card p-6">
                            <div class="w-11 h-11 rounded-lg bg-surface border border-border flex items-center justify-center text-text">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">{!! $feature['icon'] !!}</svg>
                            </div>
                            <h3 class="mt-4 text-lg font-bold text-text">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm text-text-muted leading-relaxed">{{ $feature['desc'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-border">
        <div class="max-w-[1280px] mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-text-muted">
            <p>&copy; {{ date('Y') }} Smart School Enterprise</p>
            <p>Platform digital untuk sekolah modern.</p>
        </div>
    </footer>

</body>
</html>
