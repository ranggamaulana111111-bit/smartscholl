@php
    $appName = config('app.name', 'Smart School');

    try {
        $storedName = setting('sistem.app_name');

        if (is_string($storedName) && trim($storedName) !== '') {
            $appName = $storedName;
        }
    } catch (\Throwable $exception) {
        \Illuminate\Support\Facades\Log::debug('welcome: nama aplikasi tidak terbaca dari pengaturan, memakai config.', [
            'exception' => $exception->getMessage(),
        ]);
    }

    $wordmark = mb_strtoupper($appName);

    // Enam modul yang benar-benar ada di aplikasi. Satu sumber data dipakai dua
    // kali: sebagai indeks ringkas di hero dan sebagai rincian di bagian Modul.
    $modules = [
        [
            'no' => '01',
            'name' => 'Absensi & Kehadiran',
            'lede' => 'QR, RFID, dan catat manual',
            'desc' => 'Pencatatan kehadiran berjalan di gerbang maupun di kelas, dari pemindaian QR, tap kartu RFID/NFC, sampai pencatatan manual saat kartu tertinggal.',
            'points' => [
                'Mode gerbang untuk kehadiran masuk dan pulang, mode kelas untuk setiap pertemuan.',
                'Rekapitulasi harian per rombel, lengkap dengan hitungan keterlambatan.',
                'Jejak pemindaian: perangkat, waktu, dan siswa yang terpindai.',
            ],
        ],
        [
            'no' => '02',
            'name' => 'Nilai & Rapor',
            'lede' => 'Kategori berbobot dan KKM',
            'desc' => 'Nilai masuk per kategori yang berbobot, lalu dihitung menjadi capaian kompetensi dan disusun menjadi rapor.',
            'points' => [
                'Bobot kategori, KKM, dan ambang batas diatur per sekolah.',
                'Impor dan ekspor nilai lewat berkas Excel.',
                'Capaian kompetensi dihitung otomatis dari nilai akhir.',
            ],
        ],
        [
            'no' => '03',
            'name' => 'Jurnal KBM & Tugas',
            'lede' => 'Catatan mengajar per pertemuan',
            'desc' => 'Setiap pertemuan mengajar tercatat pada jadwal gurunya. Tugas dan pekerjaan rumah dibagikan lewat portal dan dikumpulkan kembali oleh siswa.',
            'points' => [
                'Jurnal KBM terikat pada jadwal dan rombel yang sama.',
                'Siswa mengunduh tugas dan mengumpulkan jawaban.',
                'Guru melihat tugas yang belum terkumpul.',
            ],
        ],
        [
            'no' => '04',
            'name' => 'Peringatan Dini',
            'lede' => 'Ambang batas kehadiran dan nilai',
            'desc' => 'Kehadiran dan nilai dipantau terhadap ambang batas yang ditetapkan sekolah, lalu hasilnya ditindaklanjuti orang yang berwenang.',
            'points' => [
                'Peringatan muncul saat presentasi kumulatif di bawah ambang batas, atau nilai di bawah KKM.',
                'Muncul juga dari catatan kehadiran berurutan tanpa hadir.',
                'Admin menjalankan pemeriksaan, guru menindaklanjuti, lalu ditandai selesai.',
            ],
        ],
        [
            'no' => '05',
            'name' => 'Portal Orang Tua',
            'lede' => 'Rekap kehadiran anak',
            'desc' => 'Orang tua masuk dengan akun sendiri untuk melihat kehadiran dan perkembangan belajar anak.',
            'points' => [
                'Rekap kehadiran harian anak.',
                'Nilai dan capaian belajar yang sudah diinput guru.',
                'Hanya data anak sendiri, tanpa akses ke kelas lain.',
            ],
        ],
        [
            'no' => '06',
            'name' => 'Master Data & Hak Akses',
            'lede' => 'Data induk sekolah',
            'desc' => 'Data induk sekolah, akun pengguna, dan aturan siapa boleh melakukan apa, dikelola dari satu tempat.',
            'points' => [
                'Siswa, guru, rombel, tahun ajaran, dan mata pelajaran dikelola admin sekolah.',
                'Pengguna sekolah dibuat admin sekolah, lengkap dengan pengecualian per orang.',
                'Konfigurasi global dan perubahan matriks hak akses dipegang Super Admin platform.',
            ],
        ],
    ];

    $workflow = [
        ['time' => '06:30', 'title' => 'Absensi gerbang', 'desc' => 'Siswa memindai QR atau menempelkan kartu RFID. Kehadiran tercatat dan langsung muncul di dashboard sekolah.'],
        ['time' => '07:30', 'title' => 'Pembelajaran di kelas', 'desc' => 'Guru membuka jadwal hari itu, mengisi jurnal KBM, mencatat absensi per pertemuan, lalu memasukkan nilai tugas.'],
        ['time' => '13:00', 'title' => 'Pemeriksaan ambang batas', 'desc' => 'Sistem menghitung akumulasi kehadiran dan nilai. Yang turun di bawah ambang batas dicatat sebagai peringatan dini.'],
        ['time' => '16:00', 'title' => 'Laporan untuk orang tua', 'desc' => 'Orang tua dan siswa membuka portal untuk membaca rekap kehadiran harian dan capaian belajar.'],
    ];

    $roles = [
        ['name' => 'Super Admin', 'desc' => 'Mengelola seluruh sekolah yang terdaftar di platform.'],
        ['name' => 'Admin Sekolah', 'desc' => 'Penanggung jawab data dan operasional sekolah.'],
        ['name' => 'Guru', 'desc' => 'Menjalankan kegiatan belajar mengajar.'],
        ['name' => 'Siswa', 'desc' => 'Mengikuti pembelajaran dan mengumpulkan tugas.'],
        ['name' => 'Orang Tua', 'desc' => 'Memantau anak dari portal mandiri.'],
    ];

    $safeguards = [
        [
            'title' => 'Data terpisah per sekolah',
            'desc' => 'Setiap sekolah berjalan di atas tenant sendiri. Seluruh query modul dibatasi ke tenant aktif, sehingga data sekolah satu tidak pernah tampil di sekolah lain.',
        ],
        [
            'title' => '31 hak akses, 5 peran',
            'desc' => 'Setiap modul punya daftar izin. Admin sekolah dapat mengunci atau mencabut izin per peran, lalu mengecualikan satu pengguna tanpa mengubah peran yang lain.',
        ],
        [
            'title' => 'Jejak audit',
            'desc' => 'Perubahan pada data master, nilai, dan hak akses dicatat lengkap dengan nilai lama, nilai baru, pelaku, dan waktunya.',
        ],
        [
            'title' => 'Login dibatasi',
            'desc' => 'Percobaan login dibatasi lima kali per menit per alamat IP. Sekolah yang dinonaktifkan langsung mengeluarkan sesi penggunanya.',
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $appName }}</title>
    <meta name="description" content="Platform operasional sekolah: presensi QR dan RFID, nilai akademik, jurnal KBM, peringatan dini, dan portal orang tua.">
    @include('partials.fonts')
    <script>document.documentElement.classList.add('js');</script>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="theme-oryzo bg-background text-text font-sans antialiased">

<a href="#konten" class="skip-link">Langsung ke konten</a>

<header class="site-header fixed inset-x-0 top-0 z-40 border-b border-dashed border-border">
    <div class="flex h-[68px] items-center justify-between gap-4 px-[18px] md:px-[45px]">
        <a href="{{ route('home') }}" class="t-label inline-flex min-h-[44px] items-center text-text">
            {{ $wordmark }}
        </a>

        <nav class="hidden items-center gap-[24px] md:flex" aria-label="Navigasi utama" data-nav-spy>
            <a href="#modul" class="nav-spy t-label inline-flex min-h-[44px] items-center text-text transition-colors" data-spy-target="#modul">Modul</a>
            <a href="#peran" class="nav-spy t-label inline-flex min-h-[44px] items-center text-text transition-colors" data-spy-target="#peran">Peran</a>
            <a href="#keamanan" class="nav-spy t-label inline-flex min-h-[44px] items-center text-text transition-colors" data-spy-target="#keamanan">Keamanan</a>
            <a href="{{ route('login') }}" class="t-label inline-flex min-h-[44px] items-center text-text underline decoration-driftwood decoration-1 underline-offset-[5px] transition-colors hover:decoration-warm-cream">Masuk</a>
        </nav>

        <details class="disclosure group relative md:hidden">
            <summary class="btn btn-outline list-none" aria-label="Buka daftar bagian halaman">
                <span class="group-open:hidden">Menu</span>
                <span class="hidden group-open:inline">Tutup</span>
            </summary>
            <nav class="absolute right-0 top-[calc(100%+12px)] w-[220px] rounded-[12px] border border-border bg-bark-brown p-[12px] flex flex-col" aria-label="Navigasi halaman">
                <a href="#modul" class="t-label flex min-h-[44px] items-center text-text">Modul</a>
                <a href="#peran" class="t-label flex min-h-[44px] items-center text-text">Peran</a>
                <a href="#keamanan" class="t-label flex min-h-[44px] items-center text-text">Keamanan</a>
                <a href="{{ route('login') }}" class="t-label flex min-h-[44px] items-center text-text underline decoration-driftwood underline-offset-[5px]">Masuk</a>
            </nav>
        </details>
    </div>
</header>

<main id="konten">

    <span class="t-micro fixed right-0 top-[120px] z-30 hidden border-y border-l border-dashed border-border bg-background px-[6px] py-[12px] text-text [writing-mode:vertical-rl] lg:block" aria-hidden="true">
        {{ $wordmark }} 1-PLATFORM
    </span>

    <section class="relative grid min-h-[100svh] lg:grid-cols-[1.02fr_0.98fr] lg:grid-rows-1" aria-labelledby="hero-title">
        <div class="hero-photo lg:order-last">
            <img
                src="{{ asset('images/interago.jpg') }}"
                alt="Suasana sekolah internasional"
                class="hero-photo__img"
                width="1600"
                height="2133"
                fetchpriority="high"
                decoding="async">
            <div class="hero-photo__veil" aria-hidden="true"></div>
            @include('partials.orb', ['variant' => 'sphere', 'spot' => 'hero', 'speed' => '30s'])
        </div>

        <div class="hero-content order-first relative flex flex-col justify-between px-[18px] pb-[45px] pt-[104px] md:px-[45px] md:pb-[45px] md:pt-[112px]">
            <div class="page-enter">
                <p class="t-micro text-text-muted">Sistem operasional sekolah</p>
                <h1 id="hero-title" class="t-display mt-[12px] max-w-[9ch]">{{ $wordmark }}</h1>
                <div class="mt-[31px] flex flex-wrap items-center gap-x-[31px] gap-y-[12px]">
                    <a href="{{ route('login') }}" class="btn btn-primary">Masuk ke portal</a>
                    <a href="#alur" class="link-underline">Lihat alur kerja</a>
                </div>
            </div>

            <div class="mt-[48px] max-w-[440px] rounded-[12px] border border-border bg-bark-brown/90 p-[24px]">
                <p class="t-label text-text">Satu sistem, lima peran, satu catatan.</p>
                <hr class="dash-rule my-[18px]">
                <p class="t-body text-text-muted">
                    Kehadiran, nilai, jurnal, dan peringatan dini berbagi satu sumber data.
                </p>
            </div>
        </div>
    </section>

    <section id="alur" class="photo-section photo-section--frame-a scroll-mt-[68px] flex min-h-[100svh] items-center px-[18px] py-[68px] md:px-[45px]" aria-labelledby="alur-title">
        <div class="photo-section__bg" style="background-image: url('{{ asset('images/interago.jpg') }}')" aria-hidden="true"></div>
        <div class="photo-section__scrim" aria-hidden="true"></div>
        @include('partials.orb', ['variant' => 'rings', 'spot' => 'alur', 'speed' => '38s'])

        <div class="grid w-full items-center gap-[41px] lg:grid-cols-[1fr_minmax(300px,40%)_1fr] lg:gap-[18px]">
            <h2 id="alur-title" class="t-heading reveal max-w-[16ch]">Bukan sekadar absensi.</h2>

            <div class="reveal order-first rounded-[12px] border border-border bg-bark-brown/92 p-[24px] lg:order-none">
                <p class="t-micro mb-[18px] text-text-muted">Satu hari di sekolah</p>
                <ol class="flex flex-col">
                    @foreach ($workflow as $index => $step)
                        <li class="flex flex-col gap-[6px] border-t border-dashed border-border py-[18px] first:border-t-0 first:pt-0 last:pb-0">
                            <span class="serial">{{ $step['time'] }}</span>
                            <span class="t-subheading">{{ $step['title'] }}</span>
                            <span class="t-copy text-text-muted">{{ $step['desc'] }}</span>
                        </li>
                    @endforeach
                </ol>
            </div>

            <p class="t-body reveal text-text-muted">
                Absensi hanya pintu masuk. Karena setiap pemindaian terikat pada siswa, kelas, dan jadwal yang sama, satu catatan pagi itu menjadi dasar nilai, jurnal, dan peringatan dini tanpa input ulang.
            </p>
        </div>
    </section>

    <section id="modul" class="photo-section photo-section--frame-b scroll-mt-[68px] px-[18px] py-[68px] md:px-[45px] md:py-[120px]" aria-labelledby="modul-title">
        <div class="photo-section__bg" style="background-image: url('{{ asset('images/interago.jpg') }}')" aria-hidden="true"></div>
        <div class="photo-section__scrim" aria-hidden="true"></div>
        @include('partials.orb', ['variant' => 'cube', 'spot' => 'modul', 'speed' => '26s'])
        <div class="grid gap-[41px] lg:grid-cols-[1fr_1fr]">
            <h2 id="modul-title" class="t-heading reveal">Enam modul.</h2>
            <p class="t-body reveal text-text-muted">
                Setiap modul berdiri sendiri, tetapi berbagi siswa, rombel, dan tahun ajaran yang sama. Tidak ada data yang diketik dua kali.
            </p>
        </div>

        <div class="mt-[68px] grid border-t border-dashed border-border sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($modules as $module)
                <article class="reveal flex flex-col gap-[18px] border-b border-dashed border-border p-[24px]">
                    <div class="flex items-baseline justify-between gap-[12px]">
                        <span class="serial">{{ $module['no'] }}</span>
                        <span class="t-micro text-text-muted">{{ $module['lede'] }}</span>
                    </div>
                    <h3 class="t-heading-sm">{{ $module['name'] }}</h3>
                    <p class="t-copy text-text-muted">{{ $module['desc'] }}</p>
                    <ul class="mt-auto flex flex-col gap-[8px] border-t border-dashed border-border pt-[18px]">
                        @foreach ($module['points'] as $point)
                            <li class="t-copy flex gap-[8px] text-text-muted">
                                <span aria-hidden="true">·</span>
                                <span>{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </section>

    <section id="peran" class="photo-section photo-section--heavier photo-section--frame-c scroll-mt-[68px] px-[18px] py-[68px] md:px-[45px] md:py-[120px]" aria-labelledby="peran-title">
        <div class="photo-section__bg" style="background-image: url('{{ asset('images/interago.jpg') }}')" aria-hidden="true"></div>
        <div class="photo-section__scrim" aria-hidden="true"></div>
        @include('partials.orb', ['variant' => 'sphere', 'spot' => 'peran', 'speed' => '32s'])
        <div class="grid gap-[41px] lg:grid-cols-[1fr_1fr]">
            <h2 id="peran-title" class="t-heading reveal">Lima peran,<br>lima sudut pandang.</h2>
            <p class="t-body reveal text-text-muted">
                Guru melihat kelasnya, wali kelas melihat binaannya, orang tua melihat anaknya. Batas itu bukan pengaturan tampilan, melainkan izin yang diperiksa di setiap rute.
            </p>
        </div>

        <ul class="mt-[68px] border-t border-dashed border-border">
            @foreach ($roles as $role)
                <li class="reveal grid gap-[12px] border-b border-dashed border-border py-[24px] sm:grid-cols-[41px_1fr_2fr] sm:items-baseline sm:gap-[24px]">
                    <span class="serial">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                    <span class="t-heading-sm">{{ $role['name'] }}</span>
                    <span class="t-copy text-text-muted">{{ $role['desc'] }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section id="keamanan" class="photo-section photo-section--frame-d scroll-mt-[68px] px-[18px] py-[68px] md:px-[45px] md:py-[120px]" aria-labelledby="keamanan-title">
        <div class="photo-section__bg" style="background-image: url('{{ asset('images/interago.jpg') }}')" aria-hidden="true"></div>
        <div class="photo-section__scrim" aria-hidden="true"></div>
        @include('partials.orb', ['variant' => 'nested', 'spot' => 'keamanan', 'speed' => '40s'])
        <div class="grid gap-[41px] lg:grid-cols-[1fr_1fr]">
            <h2 id="keamanan-title" class="t-heading reveal">Keamanan bukan<br>fitur tambahan.</h2>
            <p class="t-body reveal text-text-muted">
                Data sekolah adalah data anak. Isolasi tenant, izin per rute, dan jejak audit dipasang sejak awal, bukan ditambahkan setelah insiden.
            </p>
        </div>

        <div class="mt-[68px] grid gap-[18px] md:grid-cols-2">
            @foreach ($safeguards as $index => $item)
                <article class="reveal rounded-[12px] border border-border bg-background/55 p-[24px]">
                    <span class="serial">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="t-subheading mt-[18px]">{{ $item['title'] }}</h3>
                    <p class="t-copy mt-[12px] text-text-muted">{{ $item['desc'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="photo-section photo-section--lighter photo-section--frame-e px-[18px] py-[68px] md:px-[45px] md:py-[120px]" aria-labelledby="masuk-title">
        <div class="photo-section__bg" style="background-image: url('{{ asset('images/interago.jpg') }}')" aria-hidden="true"></div>
        <div class="photo-section__scrim" aria-hidden="true"></div>
        @include('partials.orb', ['variant' => 'sphere', 'spot' => 'cta', 'speed' => '24s'])
        <div class="grid gap-[41px] lg:grid-cols-[1fr_1fr]">
            <h2 id="masuk-title" class="t-heading reveal">Masuk dengan<br>akun sekolah.</h2>
            <div class="reveal">
                <p class="t-body max-w-[40ch] text-text-muted">
                    Akun dibuat oleh admin sekolah. Kalau belum punya, hubungi tata usaha sekolah Anda.
                </p>
                <a href="{{ route('login') }}" class="btn btn-outline mt-[24px]">Masuk ke portal</a>
            </div>
        </div>

        <div class="mt-[68px] flex flex-wrap items-center justify-between gap-[18px] border-t border-dashed border-border pt-[24px]">
            <p class="legal">&copy; {{ date('Y') }} {{ $wordmark }}. Seluruh hak dilindungi.</p>
            <p class="legal">Dirancang untuk <span class="ember">operasional sekolah</span></p>
        </div>
    </section>

</main>

</body>
</html>
