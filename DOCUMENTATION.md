# DOCUMENTATION.md

Dokumentasi teknis Aplikasi **Smart School (schollsaya)**, Sistem Informasi dan Manajemen Sekolah Berbasis SaaS.

---

## 1. Ringkasan Proyek

Sistem operasi sekolah yang menghubungkan manajemen sekolah, guru, siswa, dan orang tua dalam satu platform:

- Pencatatan kehadiran real-time (QR / kartu / manual).
- Pengelolaan nilai akademik dengan bobot per kategori dan cetak rapor.
- Jurnal KBM (Kegiatan Belajar Mengajar) dan jadwal mengajar.
- Tugas & PR dengan batas waktu dan pengumpulan daring.
- Pemantauan perkembangan siswa dan *Early Warning System* (EWS).
- Portal orang tua untuk melihat nilai dan kehadiran anak.
- Audit trail untuk melacak perubahan data sensitif.

Arsitektur mendukung **multi-tenant** (banyak sekolah), namun saat ini dioperasikan untuk **1 sekolah saja**. Fitur SaaS tingkat enterprise (subdomain per sekolah, domin switch antar cabang, SSO) tidak diimplementasikan.

---

## 2. Tech Stack

| Komponen | Teknologi |
| --- | --- |
| Framework | Laravel 12 (PHP 8.2+) |
| Rendering | Blade + Tailwind CSS 4 |
| Bundler | Vite 6 (plugin `@tailwindcss/vite`, bukan PostCSS) |
| Database runtime | MySQL (`smart_school`) |
| Database tes | SQLite in-memory (`:memory:`) |
| QR Code | `endroid/qr-code` ^6.0 |
| Testing | PHPUnit 11 (75 tes) |
| Style PHP | Laravel Pint |
| Lingkungan dev | Laragon (Windows), tanpa Docker/Sail |

Font: Inter (display/body) + Fira Code (mono), dimuat via `https://fonts.bunny.net`.

---

## 3. Struktur Direktori

```text
app/
  Helpers.php                  # Helper global (currentTenantId, log_audit, deskripsiCapaian)
  Models/                      # 18 model Eloquent
  Services/
    EarlyWarningService.php    # Logika trigger EWS
  Traits/
    BelongsToTenant.php        # Trait isolasi multi-tenant
  Http/
    Controllers/               # 16 controller
    Requests/                  # 13 FormRequest (validasi di gerbang)
    Middleware/
      RoleMiddleware.php       # Middleware alias "role"
      SetTenant.php            # Middleware alias "tenant"
bootstrap/app.php              # Konfigurasi middleware + alias
database/
  factories/                   # 17 factory untuk tes
  migrations/                  # 19 migrasi
resources/views/               # Blade (layouts, per modul)
routes/web.php                 # Semua route web
tests/
  Feature/                     # 10 file tes fitur
  Unit/
```

---

## 4. Arsitektur & Multi-Tenancy

### 4.1 Trait `BelongsToTenant`

Semua model bisnis memakai trait `app/Traits/BelongsToTenant`:

- Saat **membuat** data, `tenant_id` diisi otomatis dari user yang login.
- Global scope memfilter semua kueri dengan `tenant_id` user saat ini, kecuali role `super_admin`.
- `isSuperAdmin()` melihat semua data lintas tenant.

### 4.2 Middleware

| Middleware | Alias | Fungsi |
| --- | --- | --- |
| `SetTenant` | `tenant` | Mengikat instance `currentTenant` pada request; logout otomatis jika status tenant bukan `active`. |
| `RoleMiddleware` | `role` | Memeriksa kolom `role` user kepada daftar role yang diizinkan (variadic), selain itu `abort(403)` dan juga memeriksa login. |

Daftar role: `super_admin`, `admin_sekolah`, `guru`, `siswa`, `orang_tua`.

### 4.3 Helper Global (`app/Helpers.php`)

- `currentTenantId(): ?string` mengembalikan ID tenant aktif (dari `app('currentTenant')` lalu user).
- `log_audit(string $action, Model $entity, ?array $old, ?array $new)` membuat baris `AuditLog`.
- `deskripsiCapaian(float $score): string` konversi nilai ke deskripsi capaian:
  - `>= 90`: Sangat Baik (A)
  - `>= 75`: Baik (B)
  - `>= 60`: Cukup (C)
  - selain itu: Perlu Bimbingan (D)

---

## 5. Model Data & Relasi

### 5.1 Daftar Model dan Kolom Kunci

| Model | Tabel | Kolom penting |
| --- | --- | --- |
| `Tenant` | `tenants` | `id` (uuid, PK), `name`, `domain` (unique), `status` (`active/inactive/suspended`) |
| `User` | `users` | `tenant_id`, `name`, `email` (unique), `role`, `password` |
| `AcademicYear` | `academic_years` | `name`, `semester` (ganjil/genap), `start_date`, `end_date`, `is_active` |
| `Rombel` | `rombels` | `academic_year_id`, `homeroom_teacher_id`, `name`, `grade_level` (unique per tenant+tahun+nama) |
| `Student` | `students` | `nisn` (unique), `nis`, `qr_token` (unique), `rfid_uid` (unique per tenant), `name`, `gender`, `birth_date`, `user_id`, `rombel_id` |
| `Teacher` | `teachers` | `user_id` (unique), `nuptk` (unique), `nip`, `subject`, `employment_status` (`gty/ptt/asn`) |
| `Subject` | `subjects` | `name` (unique per tenant), `code`, `is_active` |
| `Schedule` | `schedules` | `academic_year_id`, `user_id`, `subject_id`, `rombel_id`, `day_of_week`, `start_time`, `end_time` |
| `Journal` | `journals` | `user_id`, `subject_id`, `rombel_id`, `schedule_id`, `date`, `topic`, `notes`, `attendance_filled`, `status` (`draft/closed`); unique (`user_id`, `schedule_id`, `date`) |
| `Assessment` | `assessments` | `academic_year_id`, `subject_id`, `rombel_id`, `teacher_id`, `category` (`tugas/formatif/uts/uas`), `title`, `date`, `max_score`, `weight_percentage` |
| `AssessmentGrade` | `assessment_grades` | `assessment_id`, `student_id`, `score`, `note`; unique (`assessment_id`, `student_id`) |
| `Attendance` | `attendances` | `student_id`, `recorded_by`, `schedule_id` (nullable, FK untuk hadir pelajaran), `type` (`gate_in/gate_out/lesson`), `status` (`hadir/sakit/izin/alpha`), `date`, `time`, `source`, `note`; unique (`student_id`, `type`, `date`) |
| `Assignment` | `assignments` | `academic_year_id`, `teacher_id`, `rombel_id`, `subject_id`, `title`, `description`, `deadline_at`, `attachment_path` |
| `AssignmentSubmission` | `assignment_submissions` | `assignment_id`, `student_id`, `note`, `attachment_path`, `submitted_at`; unique (`assignment_id`, `student_id`) |
| `EarlyWarningLog` | `early_warning_logs` | `student_id`, `type` (`absence_streak/attendance_rate/low_score`), `description`, `trigger_date`, `is_resolved`, `resolved_by`, `resolved_at` |
| `StudentParent` | `student_parents` | `user_id`, `student_id`, `relationship` (`ayah/ibu/wali`), `is_primary`; unique (`user_id`, `student_id`) |
| `AuditLog` | `audit_logs` | `user_id`, `action`, `entity_type`, `entity_id`, `old_values` (json), `new_values` (json), `ip_address` |
| `AuditTrail` | (legacy) | Model lama, tidak digunakan aktif |

### 5.2 Relasi utama

```text
Tenant ──> AcademicYear ──> Rombel ──> Student
                          └──> Schedule
User (guru) ──> Schedule ──> Journal
User (guru) ──> Assessment ──> AssessmentGrade ──> Student
User (guru) ──> Assignment ──> AssignmentSubmission ──> Student
Student ──> Attendance
Student ──> EarlyWarningLog
User (orang tua) ──> StudentParent ──> Student
AuditLog ──> User
```

---

## 6. RBAC & Hak Akses (Route)

Semua route dilindungi middleware `auth`, `tenant`, dan `role`. Ringkasannya:

| Area | URL | Role yang diizinkan |
| --- | --- | --- |
| Dashboard | `/dashboard` | Semua user login |
| Master data (students, teachers, rombels, academic-years, subjects) | `/students`, `/teachers`, `/rombels`, `/academic-years`, `/subjects` | `super_admin`, `admin_sekolah` |
| Progress & Rapor siswa | `/students/{id}/progress`, `/students/{id}/rapor` | Semua role (**penyaringan tambahan di controller**) |
| Absensi | `/attendance/*` | index/scan: admin+guru; qrcodes/manual: admin |
| Jadwal | `/schedules/*` | index: admin+guru; CRUD: admin |
| Jurnal KBM | `/journals/*` | index/show: admin+guru; create/store/edit/update: guru |
| Nilai | `/assessments/*` | index/show: admin+guru+siswa; export/import/input: admin+guru |
| Tugas & PR | `/assignments/*` | index: semua; create/store: admin+guru; show/download: admin+guru+siswa; submit: siswa; destroy: admin+guru |
| Portal orang tua | `/parent/dashboard` | `orang_tua` |
| EWS | `/ews/*` | index: admin+guru; resolve/run-check: admin |
| Audit Log | `/audit-logs` | `super_admin`, `admin_sekolah` |

### 6.1 Akses data per objek (penyaringan di controller)

- **Guru** hanya melihat jadwal, jurnal, dan penilaian miliknya sendiri (`user_id`/`teacher_id`).
- **Siswa** hanya melihat assignment/penilaian rombelnya (rombel tempat `user_id` siswa terdaftar), dan progress/rapor miliknya.
- **Orang tua** hanya melihat anak yang terhubung melalui `StudentParent`.
- Akses lintas rombel ditolak (`abort(403)`), contohnya di `AssignmentController::authorizeAccess` dan `StudentController::authorizeView`.

---

## 7. Fitur / Modul

### 7.1 Dashboard Aktif Berdasarkan Role

- **Super Admin**: total tenant, pengguna, siswa, guru, ringkasan tenant.
- **Admin Sekolah**: total user/siswa/guru/rombel, tahun aktif, kehadiran hari ini, jurnal draft, EWS belum selesai, feed presensi terbaru, pengguna terbaru.
- **Guru**: jadwal hari ini, jumlah jurnal, jurnal draft, jumlah siswa wali kelas.
- **Siswa**: rata-rata nilai, status hadir hari ini, jumlah alpha 30 hari.
- **Orang tua**: jumlah anak, jumlah peringatan EWS.

### 7.2 Absensi (QR / Kartu RFID / Manual)

- Identitas siswa: token QR unik (`qr_token`) dan UID kartu RFID (`rfid_uid`, unik per tenant). QR kartu berisi `SS:{qr_token}`.
- Resolver scan menerima 4 format: `SS:{qr_token}` (baru), `SS:{NISN}` dan NISN polos (legacy), serta UID RFID (tidak peduli huruf besar/kecil).
- Pencatatan `gate_in` / `gate_out` / `lesson`; duplikasi dalam satu hari per tipe dicegah.
- **Hadir pelajaran terikat jadwal**: mode `lesson` diwajibkan memilih jadwal (`schedule_id`). Validasi: jadwal harus ada, bila pengisi guru maka jadwal miliknya, siswa harus satu rombel dengan jadwal, dan `day_of_week` jadwal harus sesuai tanggal. `schedule_id` tersimpan pada baris kehadiran.
- **Scan JSON**: endpoint yang sama mengembalikan JSON bila request meminta `Accept: application/json` (`ok`, `message`, `info`); kode tak dikenal `422 {ok:false}`.
- **Scan massal**: POST `attendance/scan/bulk` menerima banyak kode (baris baru, koma, atau array JSON), melaporkan jumlah tercatat, duplikat, dan kode gagal.
- **Anomali gate_out**: pulang tanpa catatan masuk hari ini tetap dicatat, diberi catatan `"Pulang tanpa catatan masuk hari ini"`.
- Absen manual dan override status (`hadir/sakit/izin/alpha`) oleh admin, dan oleh guru hanya untuk siswa di rombel binaannya (wali kelas).
- Halaman produksi kartu QR per siswa (token) + halaman detail siswa menampilkan UID RFID dan token QR.

### 7.3 Nilai & Penilaian

- Kategori: `tugas`, `formatif`, `uts`, `uas`, bobot default 20/30/25/25.
- Input nilai massal satu halaman per assessment (`input`).
- **Export CSV** nilai per assessment (NISN, Nama, Nilai, Catatan, Capaian).
- **Import CSV** dengan validasi baris (NISN tidak ditemukan atau nilai melebihi `max_score` dilewati), lalu `updateOrCreate` grade.
- Konversi nilai ke capaian via `deskripsiCapaian()`.
- Rapor digital: per mapel menampilkan rata-rata per kategori dan nilai akhir terbobot.

### 7.4 Jadwal & Jurnal KBM

- Jadwal per hari, jam pelajaran, guru, mata pelajaran, rombel.
- Deteksi bentrok jadwal (guru/rombel yang sama pada hari dan rentang jam sama) ditolak.
- Jurnal diisi guru untuk jadwal hari tersebut; cegah duplikat (unique user+schedule+date) dan jurnal untuk jadwal di masa lalu.
- Status `draft` / `closed`.

### 7.5 Tugas & PR

- Guru membuat tugas dengan deadline dan lampiran (upload ke disk `public`).
- Siswa mengumpulkan (submit) dengan catatan dan lampiran; satu submission per siswa per tugas (`updateOrCreate`).
- Pengumpulan setelah deadline ditolak.
- Siswa/orang tua hanya melihat tugas rombel terkait.

### 7.6 Monitoring Siswa & EWS

Halaman `students/{id}/progress` menampilkan:

- Rata-rata nilai per mapel dikelompokkan per kategori (badge hijau/merah berdasarkan ambang 75).
- Ringkasan kehadiran (hadir/sakit/izin/alpha/total).
- Status pengumpulan tugas per rombel.
- Riwayat presensi terbaru dan log EWS.

`EarlyWarningService` memicu log ketika:

- **absence_streak**: alpha tanpa keterangan min 3 hari sekolah berturut-turut (melewati akhir pekan, jendela 30 hari).
- **attendance_rate**: presensi kumulatif di bawah 80%.
- **low_score**: nilai formatif/UTS/UAS di bawah 75 (2 bulan terakhir).

Log dapat ditandai selesai (resolve) oleh admin.

### 7.7 Portal Orang Tua

- Daftar anak (melalui `StudentParent`).
- Rata-rata nilai per kategori + rata-rata keseluruhan per anak.
- Presensi 7 hari terakhir.
- Peringatan EWS yang belum selesai.
- Tautan Monitor (progress) dan Cetak Rapor per anak.

### 7.8 Audit Trail

- Dicatat via `log_audit()` pada aksi: create/update/delete student, schedule, subject, journal, assignment, assessment, submission, serta export/import/update_grades nilai.
- Halaman `audit-logs` untuk admin menampilkan statistik (total, ubah nilai, import/export) dan daftar log dengan detail JSON.

---

## 8. Validasi (FormRequest)

13 FormRequest: `AcademicYearRequest`, `AssessmentGradeRequest`, `AssessmentRequest`, `AssignmentRequest`, `ImportGradesRequest`, `JournalRequest`, `ManualAttendanceRequest`, `RombelRequest`, `ScheduleRequest`, `StudentRequest`, `SubjectRequest`, `SubmissionRequest`, `TeacherRequest`.

Aturan penting:

- `AssignmentRequest.deadline_at` harus setelah sekarang (`after:now`); lampiran maks 5 MB, mime PDF/Office/ZIP/txt.
- `ImportGradesRequest.file` wajib, mime `csv,txt`, maks 2 MB.
- `JournalRequest` mencegah jurnal hari di masa lalu dan duplikat.
- `ScheduleRequest` membawa logika cek bentrok (server-side).
- `AssessmentRequest` memvalidasi `category` ke whitelist dan `max_score` 1-1000.
- `AssessmentGradeRequest` untuk input nilai massal (`scores` array per siswa).

---

## 9. Desain UI

Berdasarkan **DESIGN.md** (arstektur mirip Ubisoft: hitam + emas + Inter):

| Token | Nilai |
| --- | --- |
| Primary | `#000000` (akar emas `#ffc906`) |
| Background | `#ffffff`, Surface `#f5f5f5`, Border `#e0e0e0` |
| Text | `#1a1a1a`, Muted `#666666` |
| Success / Warning / Danger | `#2ecc71` / `#f39c12` / `#e74c3c` |
| Body | Inter 15px/1.65, display 56px/700, heading 32px/600 |
| Radius | 4-12px (tidak semua pill) |
| Grid | 8px base |

Komponen CSS di `resources/css/app.css` (`@layer components`): `.btn` (primary/accent/ghost), `.card`, `.table`, `.badge`, `.input`, `.label`, `.error-text`, spinner.

Layout: sidebar 280px (light, aksen emas, `aria-current`, tombol hamburger mobile), top bar, skip link, flash messages (`success/info/error`), konten lebar maks 1280px. Aksesibilitas: label terhubung, fokus keyboard terlihat, kontras mencecah WCAG AA.

Rapor dicetak via halaman standalone (`students/rapor.blade.php`) dengan CSS `@media print` khusus, tanpa sidebar.

---

## 10. Database

- Runtime: MySQL `smart_school` (`.env`). Gunakan `php artisan migrate` untuk migrasi.
- Tes: SQLite in-memory (`phpunit.xml`), dengan cache/mail/session/queue memakai `array`.
- 19 migrasi: infrastruktur Laravel + 2 tabel core base + tabel bisnis.

Skema aplikasi memakai foreign key + index. Wajib perhatikan: kueri apa pun terhadap model bertrait `BelongsToTenant` otomatis terisolasi per tenant, kecuali untuk `super_admin`.

---

## 11. Setup & Perintah

Persyaratan: PHP 8.2+, Composer, Node, Laragon (Windows).

```bash
composer install
npm install
cp .env.example .env       # sesuaikan DB_DATABASE / DB_USERNAME
php artisan key:generate
php artisan migrate
php artisan serve          # atau: composer dev (server + queue + pail + vite)
npm run dev                # Vite saja (jika tidak pakai composer dev)
```

Perintah rutin:

```bash
composer dev                              # all-in-one dev server
php artisan test                          # semua tes
php artisan test --filter=TestName        # tes tunggal
vendor/bin/pint                           # auto-fix style PHP
vendor/bin/pint --test                    # cek style (dry-run)
npx vite build                            # build aset produksi
php artisan migrate                       # jalankan migrasi
```

Prefix CLI PHP di Laragon: gunakan `& "C:\laragon\bin\php\php-8.3.31-Win32-vs16-x64 (1)\php.exe"` bila `php` tidak terdeteksi global.

---

## 12. Testing

Status saat dokumentasi ini ditulis: **75 tes, 160 assertion, semuanya PASS**; Pint lulus; Vite build bersih.

File tes fitur:

| File | Cakupan |
| --- | --- |
| `AuthenticationTest` | Login, logout, dashboard, nonaktif tenant |
| `RoleMiddlewareTest` | Proteksi role, helper peran |
| `TenantIsolationTest` | Scope tenant, super admin melihat semua |
| `MasterDataTest` | CRUD tahun, rombel, siswa, guru; duplikat NISN; lintas tenant |
| `AttendanceTest` | Scan QR/RFID valid/invalid, JSON & bulk scan, anomali pulang, manual, unik per tipe tanggal, hadir pelajaran terikat jadwal, lintas tenant |
| `ScheduleJournalModuleTest` | Jadwal, bentrok, jurnal guru, duplikat jurnal, role |
| `AssessmentModuleTest` | CRUD penilaian, input massal, isolasi antar guru |
| `EarlyWarningServiceTest` | Trigger streak/rate/score, akses halaman EWS |
| `ParentPortalTest` | Akses portal, hanya anak sendiri |
| `NewModulesTest` | Export/import nilai, assignment (CRUD + submit), progress/rapor, audit trail |

`tests/Feature/NewModulesTest.php` menutup fitur yang baru ditambahkan (modul 1-5).

---

## 13. Fitur yang Tidak Diimplementasikan (Out of Scope)

| Fitur PRD | Alasan |
| --- | --- |
| Offline-sync absensi | Membutuhkan PWA/aplikasi scanner mobile |
| WhatsApp gateway / push notifikasi | Membutuhkan integrasi WhatsApp API + FCM |
| Real-time WebSocket feed | Membutuhkan Pusher/pusher laravel-echo |
| Single Sign-On | Di luar cakupan 1 sekolah |
| Subdomain per sekolah / domain kustom | Fitur SaaS multi-cabang |
| PDF generasi dinamis rapor | Rapor memakai halaman HTML + cetak browser |

---

## 14. Keamanan

- Semua input divalidasi FormRequest di gerbang.
- Password ter-hash (cast `hashed`).
- Isolasi tenant di level kueri (`BelongsToTenant`) + middleware `SetTenant`.
- Otorisasi berbasis role untuk semua route (`RoleMiddleware`).
- Otorisasi berbasis data di controller untuk objek sensitif (progress, rapor, assignment, penilaian) agar tidak terjadi kebocoran lintas rombel.
- Audit trail untuk perubahan data nilai, master data, dan absensi.
- Tidak ada secret/credential di repository; `.env` tidak ikut di-commit.