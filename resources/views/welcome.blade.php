<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Smart School') }} Enterprise</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|inter:400,500,600,700|fira-code:400" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    @php
        $appName = config('app.name', 'Smart School');
        $brandGlyph = mb_strtoupper(mb_substr($appName, 0, 1));
    @endphp
</head>
<body class="bg-background text-text font-sans antialiased">

    <header class="site-header border-b border-border sticky top-0 z-10">
        <div class="max-w-[1280px] mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 min-h-[44px]">
                <span class="brand-mark" aria-hidden="true">{{ $brandGlyph }}</span>
                <span class="font-display text-xl font-semibold text-text tracking-tight">{{ $appName }}</span>
            </a>
            <nav class="flex items-center gap-4 text-sm" aria-label="Navigasi publik">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Masuk</a>
                    <a href="{{ route('login') }}" class="btn btn-primary btn-sm">Coba Sekarang</a>
                @endauth
            </nav>
        </div>
    </header>

    <main class="page-enter">
        {{-- Hero --}}
        <section class="border-b border-border relative overflow-hidden">
            <div class="max-w-[1280px] mx-auto px-6 py-16 lg:py-24 grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center">
                <div class="reveal">
                    <p class="eyebrow">Sistem Operasi Sekolah</p>
                    <h1 class="mt-5 font-display font-semibold text-text"
                        style="font-size:clamp(34px,5vw,58px);line-height:1.08;letter-spacing:-0.02em">
                        Satu platform untuk seluruh kegiatan sekolah
                    </h1>
                    <div class="mt-6 rule-accent"></div>
                    <p class="mt-6 text-[16px] text-text-muted max-w-xl leading-relaxed">
                        Presensi real-time, pengelolaan nilai akademik, jurnal kelas, sistem peringatan dini,
                        dan portal orang tua untuk sekolah modern.
                    </p>
                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <a href="{{ route('login') }}" class="btn btn-accent btn-lg">Masuk ke Portal Sekolah</a>
                        <a href="#fitur" class="link text-base inline-flex items-center min-h-[44px]">Lihat fitur</a>
                    </div>
                </div>

                {{-- Product snapshot --}}
                <div class="reveal" style="transition-delay:120ms">
                    <div class="bento-card bento-card--inverse p-7 lg:p-9" aria-hidden="true">
                        <div class="flex items-center gap-3">
                            <span class="brand-mark brand-mark--sm">{{ $brandGlyph }}</span>
                            <div class="min-w-0">
                                <p class="font-display font-semibold text-white">{{ $appName }} Enterprise</p>
                                <p class="text-sm text-white/60">Ringkasan kampus hari ini</p>
                            </div>
                        </div>
                        <div class="mt-8">
                            <p class="font-display text-lg font-semibold text-white">Satu sistem untuk sekolah</p>
                            <p class="mt-2 text-sm text-white/70 leading-relaxed">
                                Presensi, nilai, jurnal, dan komunikasi orang tua berjalan di satu data yang aman per sekolah.
                            </p>
                        </div>
                        <ul class="mt-7 space-y-3">
                            <li class="flex items-center gap-3 text-sm text-white/80">
                                <span class="w-7 h-7 rounded-full bg-white/10 text-accent flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"/></svg>
                                </span>
                                Absensi QR/RFID di gerbang dan kelas
                            </li>
                            <li class="flex items-center gap-3 text-sm text-white/80">
                                <span class="w-7 h-7 rounded-full bg-white/10 text-accent flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-6-8h6M5 4h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/></svg>
                                </span>
                                Nilai kategori dengan rapor otomatis
                            </li>
                            <li class="flex items-center gap-3 text-sm text-white/80">
                                <span class="w-7 h-7 rounded-full bg-white/10 text-accent flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                </span>
                                Peringatan dini di bawah ambang batas
                            </li>
<li class="flex items-center gap-3 text-sm text-white/80">
                                <span class="w-7 h-7 rounded-full bg-white/10 text-accent flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                </span>
                                Peringatan dini di bawah ambang batas
                            </li>
                            <li class="flex items-center gap-3 text-sm text-white/80">
                                <span class="w-7 h-7 rounded-full bg-white/10 text-accent flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6a2 2 0 0 1 2 2v12H7V7a2 2 0 0 1 2-2zm2 6h2m-2 3h2m-2 3h2"/></svg>
                                </span>
                                Jurnal KBM dan audit trail terpusat
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        {{-- Fitur --}}
        <section id="fitur" class="border-b border-border">
            <div class="max-w-[1280px] mx-auto px-6 py-16 lg:py-20">
                <div class="mb-12 max-w-2xl reveal">
                    <p class="eyebrow">Modul Utama</p>
                    <h2 class="mt-4 display-title text-3xl lg:text-4xl">
                        Jantung sistem operasi sekolah
                    </h2>
                    <p class="mt-4 text-[15px] text-text-muted leading-relaxed">
                        Modul yang saling terhubung, diisolasi aman per sekolah, dan dirancang untuk dipakai guru sehari-hari.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 lg:gap-6">
                    @foreach([
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h.01M12 12v4m0-4v-3M12 4l-2 3m2-3l2 3m-6 8h-2m6 0v4m0-4z"/>', 'title' => 'Presensi Real-Time', 'desc' => 'Pemindaian QR Code dan kartu RFID di gerbang maupun kelas. Mode manual tetap tersedia untuk kasus khusus.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m-6-8h6M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z"/>', 'title' => 'Nilai Akademik', 'desc' => 'Kelola mata pelajaran, kategori nilai, bobot persentase, dan catatan kompetensi untuk setiap siswa.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 4h.01M5 19l-2-4 7-10a1.5 1.5 0 014 0l7 10-2 4H5z"/>', 'title' => 'Sistem Peringatan Dini', 'desc' => 'Pantau pelanggaran ambang batas kehadiran dan nilai. Notifikasi otomatis dikirim ke pihak terkait.'],
                        ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.36-1.86M9 20v-2a3 3 0 016 0v2m6 0h-6m-12 0v-2a3 3 0 015.36-1.86M12 6a3 3 0 11-6 0 3 3 0 016 0zm6 0a3 3 0 11-6 0 3 3 0 016 0z"/>', 'title' => 'Portal Orang Tua', 'desc' => 'Orang tua memantau kehadiran dan perkembangan akademik anak secara transparan.'],
                    ] as $feature)
                        <article class="reveal" style="transition-delay:{{ $loop->index * 60 }}ms">
                            <div class="bento-card p-6 h-full flex flex-col">
                                <span class="bento-card__icon">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! $feature['icon'] !!}</svg>
                                </span>
                                <h3 class="mt-5 font-display text-lg font-semibold text-text">{{ $feature['title'] }}</h3>
                                <p class="mt-2 text-sm text-text-muted leading-relaxed">{{ $feature['desc'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Statistik cara kerja --}}
        <section class="border-b border-border bg-surface">
            <div class="max-w-[1280px] mx-auto px-6 py-12 lg:py-16 grid grid-cols-1 sm:grid-cols-3 gap-5">
                @foreach([
                    ['value' => 'Real-Time', 'label' => 'Absensi QR & RFID'],
                    ['value' => 'Terbobot', 'label' => 'Kategori nilai akademik'],
                    ['value' => 'Otomatis', 'label' => 'Rapor & audit trail'],
                ] as $stat)
                    <div class="reveal" style="transition-delay:{{ $loop->index * 60 }}ms">
                        <div class="bento-card bento-card--surface p-6 text-center h-full">
                            <p class="metric__value metric__value--accent">{{ $stat['value'] }}</p>
                            <p class="mt-2 text-sm text-text-muted">{{ $stat['label'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Call to action --}}
        <section class="relative overflow-hidden">
            <div class="max-w-[1280px] mx-auto px-6 py-16 lg:py-20">
                <div class="bento-card bento-card--inverse p-10 lg:p-14 text-center reveal">
                    <p class="eyebrow !text-accent">Mulai Hari Ini</p>
                    <h2 class="mt-4 font-display text-3xl lg:text-4xl font-semibold text-white" style="letter-spacing:-0.02em">
                        Mulai digitalisasi administrasi sekolah
                    </h2>
                    <p class="mt-5 text-[15px] text-white/70 max-w-xl mx-auto leading-relaxed">
                        Presensi, nilai, jurnal, dan komunikasi orang tua dalam satu platform yang terisolasi per sekolah.
                    </p>
                    <div class="mt-8">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn btn-accent btn-lg">Buka Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-accent btn-lg">Masuk ke Portal</a>
                        @endauth
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-border">
        <div class="max-w-[1280px] mx-auto px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-text-muted">
            <p>&copy; {{ date('Y') }} {{ $appName }} Enterprise</p>
            <p class="flex items-center gap-2">
                <span class="inline-block w-1.5 h-1.5 rounded-full bg-accent" aria-hidden="true"></span>
                Platform digital untuk sekolah modern.
            </p>
        </div>
    </footer>

    <script>
        (() => {
            const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

            const reveals = [...document.querySelectorAll(".reveal")];
            const revealOnScroll = () => {
                const viewportBottom = window.innerHeight * 0.98;
                for (const el of reveals) {
                    if (el.classList.contains("is-visible")) continue;
                    if (el.getBoundingClientRect().top < viewportBottom) {
                        el.classList.add("is-visible");
                    }
                }
            };
            if (reduceMotion) {
                document.querySelectorAll(".reveal").forEach((el) => el.classList.add("is-visible"));
            } else {
                let ticking = false;
                const onScroll = () => {
                    if (ticking) return;
                    ticking = true;
                    requestAnimationFrame(() => {
                        revealOnScroll();
                        ticking = false;
                    });
                };
                window.addEventListener("scroll", onScroll, { passive: true });
                revealOnScroll();
            }
        })();
    </script>

</body>
</html>