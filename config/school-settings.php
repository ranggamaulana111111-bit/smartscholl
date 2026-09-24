<?php

return [
    'sekolah' => [
        'label' => 'Profil Sekolah',
        'description' => 'Identitas yang dicetak pada rapor resmi. Disimpan per sekolah.',
        'tenant_scoped' => true,
        'roles' => ['super_admin', 'admin_sekolah'],
        'fields' => [
            'npsn' => ['label' => 'NPSN', 'type' => 'text', 'rules' => ['nullable', 'string', 'size:8'], 'default' => '', 'placeholder' => '8 digit'],
            'nss' => ['label' => 'NSS', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:18'], 'default' => '', 'placeholder' => 'contoh: 30.01.02.01.020'],
            'kepala_sekolah' => ['label' => 'Kepala Sekolah', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100'], 'default' => '', 'placeholder' => 'Nama lengkap dan gelar'],
            'nip_kepala_sekolah' => ['label' => 'NIP Kepala Sekolah', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:30'], 'default' => '', 'placeholder' => 'NIP'],
            'alamat' => ['label' => 'Alamat', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:300'], 'default' => ''],
            'kelurahan' => ['label' => 'Kelurahan / Desa', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100'], 'default' => ''],
            'kecamatan' => ['label' => 'Kecamatan', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100'], 'default' => ''],
            'kota' => ['label' => 'Kabupaten / Kota', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100'], 'default' => ''],
            'provinsi' => ['label' => 'Provinsi', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:100'], 'default' => ''],
            'kode_pos' => ['label' => 'Kode Pos', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:10'], 'default' => ''],
            'telepon' => ['label' => 'Telepon', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:20'], 'default' => ''],
            'email' => ['label' => 'Email', 'type' => 'text', 'rules' => ['nullable', 'email', 'max:100'], 'default' => ''],
            'website' => ['label' => 'Website', 'type' => 'text', 'rules' => ['nullable', 'url', 'max:150'], 'default' => ''],
        ],
    ],
    'penilaian' => [
        'label' => 'Penilaian',
        'description' => 'Pengaturan KKM dan bobot komponen nilai. Diterapkan pada rapor dan peringatan dini.',
        'tenant_scoped' => true,
        'roles' => ['super_admin', 'admin_sekolah'],
        'fields' => [
            'kkm' => ['label' => 'KKM (Batas Tuntas)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:100'], 'default' => 75, 'hint' => 'Nilai di bawah KKM dihitung tidak tuntas.'],
            'bobot_tugas' => ['label' => 'Bobot Tugas (%)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:100'], 'default' => 20],
            'bobot_formatif' => ['label' => 'Bobot Formatif (%)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:100'], 'default' => 30],
            'bobot_uts' => ['label' => 'Bobot UTS (%)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:100'], 'default' => 25],
            'bobot_uas' => ['label' => 'Bobot UAS (%)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:100'], 'default' => 25, 'hint' => 'Total keempat bobot harus tepat 100.'],
        ],
    ],
    'peringatan' => [
        'label' => 'Peringatan Dini',
        'description' => 'Ambang pemicu sistem peringatan dini (Early Warning System).',
        'tenant_scoped' => true,
        'roles' => ['super_admin', 'admin_sekolah'],
        'fields' => [
            'absence_streak_days' => ['label' => 'Alpha Berturut-turut (hari)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:1', 'max:30'], 'default' => 3, 'hint' => 'Jumlah hari sekolah tanpa keterangan yang memicu peringatan.'],
            'attendance_rate_threshold' => ['label' => 'Ambang Presensi Kumulatif (%)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:1', 'max:100'], 'default' => 80, 'hint' => 'Peringatan muncul jika presensi kumulatif di bawah ambang ini.'],
        ],
    ],
    'kehadiran' => [
        'label' => 'Kehadiran',
        'description' => 'Perilaku pencatatan absensi lewat ubah cara scan dan entri manual.',
        'tenant_scoped' => true,
        'roles' => ['super_admin', 'admin_sekolah'],
        'fields' => [
            'lesson_scan_early_minutes' => ['label' => 'Toleransi Scan Pelajaran (menit awal)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0', 'max:120'], 'default' => 15, 'hint' => 'Scan hadir pelajaran diterima berapa menit sebelum jam mulai.'],
            'scan_default_status' => ['label' => 'Status Default Hasil Scan', 'type' => 'select', 'options' => ['hadir' => 'Hadir', 'sakit' => 'Sakit', 'izin' => 'Izin'], 'rules' => ['required', 'in:hadir,sakit,izin'], 'default' => 'hadir'],
            'gate_out_without_gate_in_note' => ['label' => 'Catatan Pulang Tanpa Scan Masuk', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'default' => 'Pulang tanpa catatan masuk hari ini'],
        ],
    ],
    'sistem' => [
        'label' => 'Sistem / SaaS',
        'description' => 'Konfigurasi global aplikasi, hanya untuk Super Admin.',
        'tenant_scoped' => false,
        'roles' => ['super_admin'],
        'fields' => [
            'app_name' => ['label' => 'Nama Aplikasi', 'type' => 'text', 'rules' => ['required', 'string', 'max:100'], 'default' => 'Smart School', 'hint' => 'Ditampilkan di judul halaman dan sidebar.'],
            'brand_glyph' => ['label' => 'Huruf Logo', 'type' => 'text', 'rules' => ['required', 'string', 'max:2'], 'default' => 'S', 'hint' => 'Satu huruf untuk lingkaran logo di sidebar.'],
        ],
    ],
];
