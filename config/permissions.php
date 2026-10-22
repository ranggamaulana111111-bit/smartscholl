<?php

/**
 * Katalog izin aplikasi. Sumber kebenaran tunggal untuk matriks hak akses.
 *
 * Setiap daftar `roles` di sini mencerminkan gate `role:` yang sebenarnya di
 * routes/web.php, jadi hasil seed tidak pernah menyimpang dari perilaku yang
 * sudah berjalan. Saat rute berubah, ubah juga daftar di sini.
 *
 * `super_admin` tidak pernah dicantumkan. Peran itu memegang seluruh izin secara
 * hardcoded (App\Models\User::hasPermission) supaya matriks tidak dapat
 * mengunci administrator platform keluar dari sistem.
 */
return [

    'roles' => [
        'super_admin' => 'Super Admin',
        'admin_sekolah' => 'Admin Sekolah',
        'guru' => 'Guru',
        'siswa' => 'Siswa',
        'orang_tua' => 'Orang Tua',
    ],

    'groups' => [

        'dashboard' => [
            'label' => 'Dashboard',
            'description' => 'Ringkasan operasional sekolah pada halaman depan.',
            'permissions' => [
                [
                    'name' => 'dashboard.view',
                    'label' => 'Lihat dashboard',
                    'roles' => ['admin_sekolah', 'guru', 'siswa', 'orang_tua'],
                ],
            ],
        ],

        'master_data' => [
            'label' => 'Master Data',
            'description' => 'Data induk yang dipakai seluruh modul akademik.',
            'permissions' => [
                [
                    'name' => 'students.view',
                    'label' => 'Lihat monitoring & rapor siswa',
                    'roles' => ['admin_sekolah', 'guru', 'siswa', 'orang_tua'],
                ],
                [
                    'name' => 'students.manage',
                    'label' => 'Kelola data siswa',
                    'roles' => ['admin_sekolah'],
                ],
                [
                    'name' => 'teachers.manage',
                    'label' => 'Kelola data guru',
                    'roles' => ['admin_sekolah'],
                ],
                [
                    'name' => 'rombels.manage',
                    'label' => 'Kelola rombel & wali kelas',
                    'roles' => ['admin_sekolah'],
                ],
                [
                    'name' => 'academic_years.manage',
                    'label' => 'Kelola tahun ajaran',
                    'roles' => ['admin_sekolah'],
                ],
                [
                    'name' => 'subjects.manage',
                    'label' => 'Kelola mata pelajaran',
                    'roles' => ['admin_sekolah'],
                ],
            ],
        ],

        'attendance' => [
            'label' => 'Absensi',
            'description' => 'Pemindaian QR, pencatatan manual, dan pengelolaan kode QR.',
            'permissions' => [
                [
                    'name' => 'attendance.view',
                    'label' => 'Lihat riwayat absensi',
                    'roles' => ['admin_sekolah', 'guru'],
                ],
                [
                    'name' => 'attendance.scan',
                    'label' => 'Jalankan mode pemindaian',
                    'roles' => ['admin_sekolah', 'guru'],
                ],
                [
                    'name' => 'attendance.manual',
                    'label' => 'Catat absensi manual',
                    'roles' => ['admin_sekolah', 'guru'],
                ],
                [
                    'name' => 'attendance.qrcodes',
                    'label' => 'Lihat & cetak kartu QR siswa',
                    'roles' => ['admin_sekolah', 'guru'],
                ],
                [
                    'name' => 'attendance.audit',
                    'label' => 'Review jejak pemindaian',
                    'roles' => ['admin_sekolah'],
                ],
            ],
        ],

        'academic' => [
            'label' => 'Akademik',
            'description' => 'Jadwal, jurnal KBM, tugas, dan penilaian.',
            'permissions' => [
                [
                    'name' => 'schedules.view',
                    'label' => 'Lihat jadwal pelajaran',
                    'roles' => ['admin_sekolah', 'guru'],
                ],
                [
                    'name' => 'schedules.manage',
                    'label' => 'Kelola jadwal pelajaran',
                    'roles' => ['admin_sekolah'],
                ],
                [
                    'name' => 'journals.view',
                    'label' => 'Lihat jurnal KBM',
                    'roles' => ['admin_sekolah', 'guru'],
                ],
                [
                    'name' => 'journals.manage',
                    'label' => 'Tulis & ubah jurnal KBM',
                    'roles' => ['guru'],
                ],
                [
                    'name' => 'assignments.view',
                    'label' => 'Lihat tugas & pekerjaan rumah',
                    'roles' => ['admin_sekolah', 'guru', 'siswa', 'orang_tua'],
                ],
                [
                    'name' => 'assignments.manage',
                    'label' => 'Buat & hapus tugas',
                    'roles' => ['admin_sekolah', 'guru'],
                ],
                [
                    'name' => 'assignments.submit',
                    'label' => 'Kumpulkan jawaban tugas',
                    'roles' => ['siswa'],
                ],
                [
                    'name' => 'assessments.view',
                    'label' => 'Lihat nilai',
                    'roles' => ['admin_sekolah', 'guru', 'siswa'],
                ],
                [
                    'name' => 'assessments.manage',
                    'label' => 'Input & ubah nilai',
                    'roles' => ['admin_sekolah', 'guru'],
                ],
            ],
        ],

        'ews' => [
            'label' => 'Peringatan Dini',
            'description' => 'Ambang batas ketercapaian dan tindak lanjut konseling.',
            'permissions' => [
                [
                    'name' => 'ews.view',
                    'label' => 'Lihat peringatan dini',
                    'roles' => ['admin_sekolah', 'guru'],
                ],
                [
                    'name' => 'ews.manage',
                    'label' => 'Tindak lanjuti peringatan & jalankan pemeriksaan',
                    'roles' => ['admin_sekolah'],
                ],
            ],
        ],

        'users' => [
            'label' => 'Pengguna & Hak Akses',
            'description' => 'Akun yang masuk ke aplikasi beserta kewenangannya.',
            'permissions' => [
                [
                    'name' => 'users.manage',
                    'label' => 'Kelola pengguna',
                    'roles' => ['admin_sekolah'],
                ],
                [
                    'name' => 'permissions.view',
                    'label' => 'Lihat matriks hak akses',
                    'roles' => ['admin_sekolah'],
                ],
                [
                    'name' => 'permissions.manage',
                    'label' => 'Ubah matriks hak akses',
                    'roles' => [],
                ],
                [
                    'name' => 'audit.view',
                    'label' => 'Lihat audit trail',
                    'roles' => ['admin_sekolah'],
                ],
            ],
        ],

        'settings' => [
            'label' => 'Konfigurasi',
            'description' => 'Identitas sekolah, aturan penilaian, dan konfigurasi global.',
            'permissions' => [
                [
                    'name' => 'settings.view',
                    'label' => 'Buka halaman pengaturan',
                    'roles' => ['admin_sekolah'],
                ],
                [
                    'name' => 'settings.school',
                    'label' => 'Ubah pengaturan sekolah & penilaian',
                    'roles' => ['admin_sekolah'],
                ],
                [
                    'name' => 'settings.system',
                    'label' => 'Ubah konfigurasi global aplikasi',
                    'roles' => [],
                ],
            ],
        ],

        'self_service' => [
            'label' => 'Layanan Mandiri',
            'description' => 'Halaman khusus untuk peran tertentu.',
            'permissions' => [
                [
                    'name' => 'parent.dashboard',
                    'label' => 'Buka portal anak (orang tua)',
                    'roles' => ['orang_tua'],
                ],
            ],
        ],

    ],

];
