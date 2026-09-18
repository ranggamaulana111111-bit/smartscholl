<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Smart School')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|fira-code:400" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-background text-text font-sans antialiased">
    <a href="#main-content" class="skip-link">Langsung ke konten</a>

    @auth
        <div class="min-h-screen flex">
            <aside id="sidebar" class="hidden lg:flex w-[280px] shrink-0 bg-surface border-r border-border flex-col fixed inset-y-0 left-0 z-30 overflow-y-auto" role="navigation" aria-label="Navigasi Utama">
                <div class="p-5 border-b border-border">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-lg bg-primary text-accent flex items-center justify-center font-bold text-lg">S</span>
                        <div class="min-w-0">
                            <p class="font-bold text-sm tracking-tight text-text">Smart School</p>
                            <p class="text-xs text-text-muted truncate">{{ auth()->user()->tenant->name ?? 'Super Admin' }}</p>
                        </div>
                    </div>
                </div>

                @php
                    $activeClass = 'bg-background font-semibold text-text border-l-[3px] border-accent';
                    $inactiveClass = 'text-text-muted hover:bg-background hover:text-text border-l-[3px] border-transparent';
                @endphp

                <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-r-lg text-sm min-h-[44px] transition-colors duration-fast {{ request()->routeIs('dashboard') ? $activeClass : $inactiveClass }}"
                       @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 13h6V4H4v9zm0 7h6v-4H4v4zm10 0h6V11h-6v9zm0-16v4h6V4h-6z"/></svg>
                        Dashboard
                    </a>

                    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']))
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted px-3 pt-4 pb-1">Master Data</p>

                        @php $masterRoutes = ['students.*','teachers.*','rombels.*','academic-years.*','subjects.*']; @endphp
                        @foreach([
                            ['route' => 'students.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1m8-7a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm6-3v6m3-3h-6"/>', 'label' => 'Siswa'],
                            ['route' => 'teachers.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4a3 3 0 1 0 0 6 3 3 0 0 0 0-6zM5 20c.6-3.6 3.4-6 7-6s6.4 2.4 7 6m-5-12h.01"/>', 'label' => 'Guru'],
                            ['route' => 'rombels.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm3 7h8m-8 4h8m-8 4h5"/>', 'label' => 'Rombel'],
                            ['route' => 'academic-years.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M3 7h18v13H3V7z"/>', 'label' => 'Tahun Ajaran'],
                            ['route' => 'subjects.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>', 'label' => 'Mapel'],
                        ] as $item)
                            <a href="{{ route(str_replace('.*', '.index', $item['route'])) }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-r-lg text-sm min-h-[44px] transition-colors duration-fast {{ request()->routeIs($item['route']) ? $activeClass : $inactiveClass }}"
                               @if(request()->routeIs($item['route'])) aria-current="page" @endif>
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    @endif

                    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah', 'guru']))
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted px-3 pt-4 pb-1">Akademik</p>

                        @foreach([
                            ['route' => 'attendance.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2 4-4"/>', 'label' => 'Absensi'],
                            ['route' => 'schedules.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M3 10h18M4 4h16v14H4V4z"/>', 'label' => 'Jadwal'],
                            ['route' => 'journals.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>', 'label' => 'Jurnal KBM'],
                            ['route' => 'assessments.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>', 'label' => 'Nilai'],
                            ['route' => 'assignments.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2m-1.17-1.5A2.49 2.49 0 0115 5H14a2 2 0 00-2 2v1a2 2 0 01-2 2h-2a2 2 0 01-2-2V5m-1.17-1.5A2.49 2.49 0 0111 3h2a2.49 2.49 0 012.17 1.5m-6.34 0H5a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>', 'label' => 'Tugas & PR'],
                            ['route' => 'ews.*', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>', 'label' => 'Peringatan Dini'],
                        ] as $item)
                            <a href="{{ route(str_replace('.*', '.index', $item['route'])) }}"
                               class="flex items-center gap-3 px-3 py-2.5 rounded-r-lg text-sm min-h-[44px] transition-colors duration-fast {{ request()->routeIs($item['route']) ? $activeClass : $inactiveClass }}"
                               @if(request()->routeIs($item['route'])) aria-current="page" @endif>
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">{!! $item['icon'] !!}</svg>
                                {{ $item['label'] }}
                            </a>
                        @endforeach
                    @endif

                    @if(auth()->user()->hasAnyRole(['super_admin', 'admin_sekolah']))
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-text-muted px-3 pt-4 pb-1">Sistem</p>

                        <a href="{{ route('audit-logs.index') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-r-lg text-sm min-h-[44px] transition-colors duration-fast {{ request()->routeIs('audit-logs.*') ? $activeClass : $inactiveClass }}"
                           @if(request()->routeIs('audit-logs.*')) aria-current="page" @endif>
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Audit Trail
                        </a>
                    @endif

                    @if(auth()->user()->isOrangTua())
                        <a href="{{ route('parent.dashboard') }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-r-lg text-sm min-h-[44px] transition-colors duration-fast {{ request()->routeIs('parent.*') ? $activeClass : $inactiveClass }}"
                           @if(request()->routeIs('parent.*')) aria-current="page" @endif>
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/></svg>
                            Portal Anak
                        </a>
                    @endif
                </nav>

                <div class="p-4 border-t border-border">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-full bg-primary text-accent flex items-center justify-center text-sm font-bold shrink-0">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-text truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-text-muted truncate">{{ str_replace('_', ' ', ucfirst(auth()->user()->role)) }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full text-left text-sm text-text-muted hover:text-danger px-3 py-2 rounded-lg hover:bg-red-50 min-h-[44px] transition-colors duration-fast">
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex-1 lg:ml-[280px]">
                <header class="sticky top-0 z-20 bg-background border-b border-border h-16 flex items-center px-4 lg:px-8">
                    <button id="sidebar-toggle" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-surface transition-colors min-h-[44px] min-w-[44px] items-center justify-center" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="sidebar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="flex-1"></div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-text">{{ auth()->user()->name }}</span>
                    </div>
                </header>

                <main id="main-content" class="p-4 lg:p-8 max-w-[1280px] mx-auto">
                    @if(session('success'))
                        <div class="mb-6 p-4 rounded-lg border border-success bg-green-50 text-sm text-green-800 flex items-center gap-2" role="alert" aria-live="polite">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if(session('info'))
                        <div class="mb-6 p-4 rounded-lg border border-accent bg-yellow-50 text-sm text-yellow-800 flex items-center gap-2" role="status" aria-live="polite">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ session('info') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="mb-6 p-4 rounded-lg border border-danger bg-red-50 text-sm text-red-800 flex items-center gap-2" role="alert" aria-live="polite">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ session('error') }}
                        </div>
                    @endif
                    @yield('content')
                </main>
            </div>

            <div id="sidebar-overlay" class="hidden fixed inset-0 bg-black/50 z-20 lg:hidden" aria-hidden="true"></div>
        </div>
    @else
        @yield('content')
    @endauth

    @auth
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const toggle = document.getElementById('sidebar-toggle');

            if (!sidebar || !overlay || !toggle) return;

            function openSidebar() {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('flex');
                overlay.classList.remove('hidden');
                toggle.setAttribute('aria-expanded', 'true');
            }

            function closeSidebar() {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('flex');
                overlay.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', function () {
                const expanded = toggle.getAttribute('aria-expanded') === 'true';
                expanded ? closeSidebar() : openSidebar();
            });

            overlay.addEventListener('click', closeSidebar);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
                    closeSidebar();
                    toggle.focus();
                }
            });
        });
    </script>
    @endauth
</body>
</html>
