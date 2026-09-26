<p align="center">
  <h1 align="center">Smart School Enterprise SaaS</h1>
</p>

<p align="center">
  Sistem Informasi &amp; Manajemen Sekolah Terpadu — <em>Multi-Tenant School Operating System</em>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12-red?style=flat-square&logo=laravel" alt="Laravel 12">
  <img src="https://img.shields.io/badge/PHP-8.2%2B-blueviolet?style=flat-square&logo=php" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Tailwind%20CSS-4-blue?style=flat-square&logo=tailwindcss" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/Vite-6-purple?style=flat-square&logo=vite" alt="Vite 6">
  <img src="https://img.shields.io/badge/MySQL-8-orange?style=flat-square&logo=mysql" alt="MySQL 8">
  <img src="https://img.shields.io/badge/SQLite-tests-003B57?style=flat-square&logo=sqlite" alt="SQLite for tests">
</p>

## Tentang Proyek

Smart School adalah platform SaaS multi-tenant untuk operasional sekolah yang menghubungkan manajemen sekolah, tenaga pendidik, siswa, dan orang tua secara **real-time**.

Fitur utama:

- **Dashboard Sekolah** — ringkasan real-time kehadiran siswa/guru, statistik mengajar, dan aktivitas terbaru.
- **Absensi QR / RFID** — scan QR via kamera atau tap kartu pelajar (NFC/RFID), mode gerbang & presensi mapel, serta absen manual/override.
- **Input Nilai & Penilaian** — grid nilai per kelas, bulk entry, import/export templat (.xlsx/.csv), bobot otomatis (Tugas, Formatif, UTS, UAS).
- **Jadwal Mengajar & Jurnal KBM** — timetable interaktif, jurnal kelas wajib diisi guru, tugas & deadline pengumpulan.
- **Early Warning System (EWS)** — trigger otomatis ke Guru BK/Wali Kelas saat absensi < 80%, alpa > 3 hari, atau nilai di bawah KKM.
- **Portal Orang Tua** — pantau kehadiran, nilai, rapor digital, dan pengumuman sekolah.
- **Master Data PTK & Siswa** — manajemen guru, rombel, mata pelajaran, tahun ajaran, dan generator kartu QR siswa.
- **RBAC & Multi-Tenancy** — isolasi data per tenant (`tenant_id` otomatis di semua query) dengan lima peran: `super_admin`, `admin_sekolah`, `guru`, `siswa`, dan `orang_tua`. Wali kelas bukan role melainkan relasi `rombels.homeroom_teacher_id`; Guru BK memakai peran `guru`.
- **Audit Trail** — log aktivitas untuk melacak perubahan data nilai/absensi.

Persyaratan lengkap tersedia di [PRD.md](./PRD.md).

## Stack Teknologi

| Layer       | Teknologi                              |
| ----------- | -------------------------------------- |
| Framework   | Laravel 12                             |
| Bahasa      | PHP 8.2+                               |
| Frontend    | Blade + Tailwind CSS 4                 |
| Build      | Vite 6 (`@tailwindcss/vite`)           |
| Database    | MySQL 8 (app), SQLite in-memory (test) |
| Testing     | PHPUnit 11 (Unit & Feature)            |
| Linting     | Laravel Pint                           |
| QR Code     | `endroid/qr-code` (kartu pelajar QR)    |

## Persyaratan Sistem

- PHP **8.2+**
- Composer 2
- Node.js & npm
- MySQL 8 (atau SQLite, untuk development ringan)
- Laragon (Windows) / XAMPP / LEMP — tanpa Docker & Sail

## Instalasi

```bash
# 1. Pasang dependency PHP
composer install

# 2. Pasang dependency frontend
npm install

# 3. Siapkan environment
cp .env.example .env
php artisan key:generate

# 4. Atur koneksi database di .env
#    MySQL (default untuk development):
#      DB_CONNECTION=mysql
#      DB_HOST=127.0.0.1
#      DB_PORT=3306
#      DB_DATABASE=smart_school
#      DB_USERNAME=root
#      DB_PASSWORD=
#    SQLite (alternatif, tanpa server DB):
#      DB_CONNECTION=sqlite
#      DB_DATABASE=/path/absolut/database/database.sqlite

# 5. Siapkan database
php artisan migrate --seed
```

> Untuk SQLite, buat dulu berkasnya: `touch database/database.sqlite`.

### Akun Demo (dari `database/seeders/UserSeeder.php`)

| Peran            | Email                        | Password   |
| ---------------- | ---------------------------- | ---------- |
| Super Admin      | `superadmin@smartschool.id`  | `password` |
| Admin Sekolah    | `admin@sman1.smartschool.id` | `password` |
| Guru             | `guru@sman1.smartschool.id`  | `password` |
| Siswa            | `andi@sman1.smartschool.id`  | `password` |

> Seed ini hanya untuk development. Ganti atau hapus sebelum deploy.

## Menjalankan Development Server

```bash
composer dev
```

Perintah di atas otomatis menjalankan 4 proses secara paralel: `artisan serve`, `queue:listen`, `pail` (log real-time), dan Vite.

Atau jalankan masing-masing secara terpisah:

```bash
php artisan serve      # server utama
npm run dev            # Vite (HMR frontend)
php artisan queue:listen --tries=1
php artisan pail
```

Akses aplikasi di: <http://localhost:8000>

## Build Produksi

```bash
npm run build   # bundle aset ke public/build
```

## Testing

Suite saat ini: **212 test / 570 assertions**, memakai SQLite in-memory sehingga tidak menyentuh database development.

```bash
php artisan test                              # semua test
php artisan test --filter=TenantIsolationTest  # satu test tertentu
```

## Code Style

```bash
./vendor/bin/pint        # auto-fix style PHP
./vendor/bin/pint --test # cek saja (dry-run)
```

## Struktur Modul (Route & RBAC)

Peran yang tersedia hanya lima: `super_admin`, `admin_sekolah`, `guru`, `siswa`, `orang_tua`.

| Modul                              | Akses                                                                  |
| ---------------------------------- | ---------------------------------------------------------------------- |
| Master Data (siswa, PTK, rombel, tahun ajaran, mapel) | Super Admin & Admin Sekolah                    |
| Absensi — riwayat, scan, manual    | Super Admin, Admin Sekolah, Guru                                       |
| Absensi — cetak kartu QR           | Super Admin & Admin Sekolah                                            |
| Jadwal — lihat                     | Super Admin, Admin Sekolah, Guru                                       |
| Jadwal — buat / edit / hapus       | Super Admin & Admin Sekolah                                            |
| Jurnal KBM — buat / edit            | Guru                                                                   |
| Jurnal KBM — lihat                  | Super Admin, Admin Sekolah, Guru                                       |
| Penilaian — input nilai, impor, ekspor | Super Admin, Admin Sekolah, Guru                                    |
| Penilaian — lihat                   | + Siswa                                                                 |
| Tugas — buat / hapus               | Super Admin, Admin Sekolah, Guru                                       |
| Tugas — lihat                       | + Siswa, Orang Tua                                                      |
| Tugas — kumpulkan                  | Siswa                                                                  |
| Monitoring & Rapor (/progress, /rapor) | Semua peran, akses disaring di controller                          |
| Parent Portal                      | Orang Tua                                                              |
| EWS — lihat                        | Super Admin, Admin Sekolah, Guru                                       |
| EWS — resolve & run-check          | Super Admin & Admin Sekolah                                            |
| Audit Trail                        | Super Admin & Admin Sekolah                                            |
| Pengaturan                         | Super Admin & Admin Sekolah (tab Sistem khusus Super Admin)            |

Seluruh query tenant-scoped memakai trait `BelongsToTenant` untuk menjamin isolasi antar sekolah.

> **Batasan yang diketahui:** `BelongsToTenant::creating` mengisi `tenant_id` dari `Auth::user()->tenant_id`. Karena Super Admin tidak punya tenant, record yang ia buat tersimpan dengan `tenant_id = null`. Perilaku ini belum ditetapkan.

## Entity & Relasi Data

```text
Tenant/Sekolah ──> Tahun Ajaran & Semester
     ├──> PTK/Guru ──> Jadwal ──> Jurnal KBM
     ├──> Rombel ──> Siswa ──> Nilai (Assessment)
     │                  ├──[QR/Kartu]──> Log Absensi ──> Dashboard & EWS
     └──> Orang Tua <────────────── Portal
```

## Lisensi

MIT, dideklarasikan di `composer.json`. Berkas `LICENSE` belum ditambahkan ke repositori.