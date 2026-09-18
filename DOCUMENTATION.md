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
| Testing | PHPUnit 11 (181 tes) |
| Style PHP | Laravel Pint |
| Lingkungan dev | Laragon (Windows), tanpa Docker/Sail |

Font: Inter (display/body) + Fira Code (mono), dimuat via `https://fonts.bunny.net`.

---

## 3. Struktur Direktori

```text
app/
  Helpers.php                  # Helper global (currentTenantId, log_audit, deskripsiCapaian)
  Models/                      # 19 model Eloquent
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
    migrations/                  # 32 migrasi
resources/views/               # Blade (layouts, per modul)
routes/web.php                 # Semua route web
tests/
  Feature/                     # 14 file tes fitur
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
| `Student` | `students` | `nisn` (unique per tenant), `nis`, `qr_token` (unique), `rfid_uid` (unique per tenant), `name`, `gender`, `birth_date`, `user_id`, `rombel_id` |
| `Teacher` | `teachers` | `user_id` (unique), `nuptk` (unique), `nip`, `subject_id` (FK `subjects`, nullOnDelete, dipercayai), `subject_text` (teks legacy fallback), `employment_status` (`gty/ptt/asn`) |
| `Subject` | `subjects` | `name` (unique per tenant), `code`, `is_active` |
| `Schedule` | `schedules` | `academic_year_id`, `user_id`, `subject_id`, `rombel_id`, `day_of_week`, `start_time`, `end_time` |
| `Journal` | `journals` | `user_id`, `subject_id`, `rombel_id`, `schedule_id`, `date`, `topic`, `notes`, `attendance_filled`, `status` (`draft/closed`); unique (`user_id`, `schedule_id`, `date`) dan unique sesi (`user_id`, `date`, `rombel_id`, `schedule_id\|0`, `subject_id\|0`) |
| `Assessment` | `assessments` | `academic_year_id`, `subject_id`, `rombel_id`, `teacher_id`, `category` (`tugas/formatif/uts/uas`), `title`, `date`, `max_score`, `weight_percentage` |
| `AssessmentGrade` | `assessment_grades` | `assessment_id`, `student_id`, `score`, `note`; unique (`assessment_id`, `student_id`) |
| `Attendance` | `attendances` | `student_id`, `recorded_by`, `schedule_id` (nullable, FK untuk hadir pelajaran), `type` (`gate_in/gate_out/lesson`), `status` (`hadir/sakit/izin/alpha`), `date`, `time`, `source`, `note`; `dedupe_key` (not null, aplikasi mengisi `{student_id}:{type}:{schedule_id\|0}:{date}`); unique (`student_id`, `schedule_id`, `date`) untuk absensi pelajaran dan unique (`dedupe_key`) sebagai jaring pengaman lintas tipe termasuk gate yang `schedule_id`-nya kosong |
| `Assignment` | `assignments` | `academic_year_id`, `teacher_id`, `rombel_id`, `subject_id`, `title`, `description`, `deadline_at`, `attachment_path` |
| `AssignmentSubmission` | `assignment_submissions` | `assignment_id`, `student_id`, `note`, `attachment_path`, `submitted_at`; unique (`assignment_id`, `student_id`) |
| `EarlyWarningLog` | `early_warning_logs` | `student_id`, `type` (`absence_streak/attendance_rate/low_score`), `description`, `trigger_date`, `is_resolved`, `resolved_by`, `resolved_at` |
| `StudentParent` | `student_parents` | `user_id`, `student_id`, `relationship` (`ayah/ibu/wali`), `is_primary`; unique (`user_id`, `student_id`) |
| `StudentRombelHistory` | `student_rombel_histories` | `student_id`, `rombel_id` (nullOnDelete), `entered_at`, `left_at`; riwayat perpindahan rombel; kolom generated `open_student_id` + unique menjamin satu baris open per siswa |
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
- **Guru**: jadwal hari ini, jumlah jurnal, jurnal draft, jumlah siswa wali kelas. Feed presensi terbaru hanya menampilkan siswa rombel binaan (wali kelas).
- **Siswa**: rata-rata nilai, status hadir hari ini, jumlah alpha 30 hari.
- **Orang tua**: jumlah anak, jumlah peringatan EWS.

### 7.2 Absensi (QR / Kartu RFID / Manual)

- Identitas siswa: token QR unik (`qr_token`) dan UID kartu RFID (`rfid_uid`, unik per tenant). QR kartu berisi `SS:{qr_token}`.
- Resolver scan menerima 4 format: `SS:{qr_token}` (baru), `SS:{NISN}` dan NISN polos (legacy), serta UID RFID (tidak peduli huruf besar/kecil).
- Pencatatan `gate_in` / `gate_out` / `lesson`; duplikasi dalam satu hari per tipe dicegah. Selain cek aplikasi, unique `dedupe_key` di DB menjaring duplikat pada balapan (race) request; pelanggaran unique ditangani sebagai hasil `info` (idempotent) sehingga tidak pernah memunculkan HTTP 500.
- **Hadir pelajaran terikat jadwal**: mode `lesson` diwajibkan memilih jadwal (`schedule_id`). Validasi: jadwal harus ada, bila pengisi guru maka jadwal miliknya, siswa harus satu rombel dengan jadwal, dan `day_of_week` jadwal harus sesuai tanggal. `schedule_id` tersimpan pada baris kehadiran.
- **Scan JSON**: endpoint yang sama mengembalikan JSON bila request meminta `Accept: application/json` (`ok`, `message`, `info`); kode tak dikenal `422 {ok:false}`.
- **Scan massal**: POST `attendance/scan/bulk` menerima banyak kode (baris baru, koma, atau array JSON), melaporkan jumlah tercatat, duplikat, dan kode gagal.
- **Anomali gate_out**: pulang tanpa catatan masuk hari ini tetap dicatat, diberi catatan `"Pulang tanpa catatan masuk hari ini"`.
- Absen manual dan override status (`hadir/sakit/izin/alpha`) oleh admin, dan oleh guru hanya untuk siswa di rombel binaannya (wali kelas).
- Halaman produksi kartu QR per siswa (token) + halaman detail siswa menampilkan UID RFID dan token QR.

### 7.3 Nilai & Penilaian

- Kategori: `tugas`, `formatif`, `uts`, `uas`, bobot default 20/30/25/25.
- Input nilai massal langsung pada halaman detail assessment (grid per siswa rombel, nilai terisi otomatis, Enter pindah baris). Halaman input terpisah dihapus.
- Guru hanya dapat mengelola penilaian miliknya (`teacher_id`), admin dapat mengelola semua penilaian di tenant-nya.
- Saat menyimpan nilai: siswa di luar rombel assessment diabaikan dan nilai tidak boleh melebihi `max_score` (validasi gate + blok di controller).
- **Download Template CSV** nilai berisi daftar siswa rombel (NISN, Nama) untuk diisi.
- **Export CSV** nilai per assessment (NISN, Nama, Nilai, Catatan, Capaian).
- **Import CSV** dengan validasi baris (NISN tidak ditemukan atau nilai melebihi `max_score` dilewati), lalu `updateOrCreate` grade.
- Konversi nilai ke capaian via `deskripsiCapaian()`.
- Rapor digital: per mapel menampilkan rata-rata per kategori dan nilai akhir terbobot.

### 7.4 Jadwal & Jurnal KBM

- Jadwal per hari, jam pelajaran, guru, mata pelajaran, rombel.
- Deteksi bentrok jadwal (guru/rombel yang sama pada hari dan rentang jam sama) ditolak.
- Jurnal diisi guru untuk jadwal hari tersebut; satu sesi (guru+mapel+rombel+jadwal+tanggal) hanya boleh satu jurnal, dan jurnal tanpa jadwal juga dicegah ganda di gerbang validasi. Tanggal jurnal tidak boleh melebihi hari ini (isi lampau untuk backfill diizinkan); hari jadwal tidak dipaksa sama agar sesi susulan (make-up) tetap dapat dicatat.
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
- Rata-rata nilai per kategori + rata-rata keseluruhan per anak, di-scope ke tahun ajaran rombel anak.
- Presensi 7 hari terakhir (termasuk hari ini).
- Peringatan EWS yang belum selesai, di-scope ke tahun ajaran rombel anak.
- Tautan Monitor (progress) dan Cetak Rapor per anak.

### 7.8 Audit Trail

- Dicatat via `log_audit()` pada aksi: create/update/delete student, rombel, teacher, academic-year, schedule, subject, journal, assignment, assessment, submission, serta export/import/update_grades nilai.
- Halaman `audit-logs` untuk admin menampilkan statistik (total, ubah nilai, import/export) dan daftar log dengan detail JSON.

---

## 8. Validasi (FormRequest)

13 FormRequest: `AcademicYearRequest`, `AssessmentGradeRequest`, `AssessmentRequest`, `AssignmentRequest`, `ImportGradesRequest`, `JournalRequest`, `ManualAttendanceRequest`, `RombelRequest`, `ScheduleRequest`, `StudentRequest`, `SubjectRequest`, `SubmissionRequest`, `TeacherRequest`.

Aturan penting:

- `AssignmentRequest.deadline_at` harus setelah sekarang (`after:now`); lampiran maks 5 MB, mime PDF/Office/ZIP/txt.
- `ImportGradesRequest.file` wajib, mime `csv,txt`, maks 2 MB.
- `JournalRequest` mencegah jurnal hari di masa lalu dan duplikat; `subject_id`/`rombel_id` di-scope tenant dan bila `schedule_id` diisi, konteks jurnal (termasuk tahun ajaran) **diturunkan dari jadwal** oleh `JournalController` sehingga tidak bisa bertentangan.
- `ScheduleRequest` membawa logika cek bentrok (server-side). Keempat FK (`academic_year_id`, `user_id`, `subject_id`, `rombel_id`) memakai `Rule::exists` yang di-scope tenant; tahun ajaran wajib aktif, guru wajib role `guru`, mapel wajib aktif, dan rombel harus berasal dari tahun ajaran yang dipilih.
- `AssessmentRequest` memvalidasi `category` ke whitelist dan `max_score` 1-1000; `subject_id`/`rombel_id` di-scope tenant dan rombel wajib dari tahun ajaran aktif. Guru tetap wajib punya jadwal `(rombel, mapel)` pada tahun aktif.
- `AssignmentRequest` — `subject_id`/`rombel_id` di-scope tenant dan rombel wajib dari tahun ajaran aktif; guru wajib mengampu `(rombel, mapel)` tersebut.
- `ManualAttendanceRequest` — `student_id`/`schedule_id` di-scope tenant (guru tetap dibatasi rombel binaan di controller).
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
- 32 migrasi: infrastruktur Laravel + 2 tabel core base + tabel bisnis.

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

Status saat dokumentasi ini ditulis: **181 tes, 460 assertion, semuanya PASS**; Pint lulus; Vite build bersih.

File tes fitur:

| File | Cakupan |
| --- | --- |
| `AuthenticationTest` | Login, logout, dashboard, nonaktif tenant |
| `RoleMiddlewareTest` | Proteksi role, helper peran |
| `TenantIsolationTest` | Scope tenant, super admin melihat semua |
| `MasterDataTest` | CRUD tahun, rombel, siswa, guru; duplikat NISN; lintas tenant; proteksi hapus siswa (absensi/nilai); validasi rombel aktif & lintas tenant; riwayat perpindahan rombel tanpa baris ganda |
| `AttendanceTest` | Scan QR/RFID valid/invalid, JSON & bulk scan, anomali pulang, manual, unik per tipe tanggal, hadir pelajaran terikat jadwal, lintas tenant |
| `ScheduleJournalModuleTest` | Jadwal, bentrok (overlap guru/rombel, jam berdampingan, update diri sendiri), jurnal guru, duplikat jurnal, kepemilikan jurnal, role, proteksi hapus jadwal berjurnal/berabsensi |
| `AssessmentModuleTest` | CRUD penilaian, grid nilai (maks skor, rombel, hapus per siswa), kepemilikan guru, template CSV, siswa hanya lihat nilainya sendiri, isolasi antar guru/tenant |
| `EarlyWarningServiceTest` | Trigger streak/rate/score, akses halaman EWS, anti-duplikat warning belum terselesaikan, re-trigger setelah resolve |
| `ParentPortalTest` | Akses portal, hanya anak sendiri |
| `NewModulesTest` | Export/import nilai, assignment (CRUD + submit + download/destroy authorized), progress/rapor, audit trail + isolasi lintas tenant |
| `TenantContextIntegrityTest` | Validasi lintas tenant untuk jadwal/jurnal/nilai/tugas/absensi manual, rombel tahun non-aktif, turunan konteks jurnal dari jadwal, unique `dedupe_key` absensi, feed dashboard guru hanya rombel binaan |
| `BusinessCorrectnessTest` | P1: jendela presensi 7 hari & scope tahun ajaran portal orang tua, rekap absensi hanya `lesson`, daftar jurnal tertunda di dashboard, guard hapus guru + FK `schedules.user_id` restrict, unique riwayat rombel open, unique sesi jurnal + validasi jurnal non-jadwal, NISN unik per tenant |

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

---

## 15. Riwayat Perbaikan (Wave)

### Wave 0 — Security, Authorization & Data Integrity (P0 audit, 2026-09-18)

Hasil audit pertama (baseline resmi) dijadikan acuan; setiap perbaikan diverifikasi terhadap source aktual dan ditutup dengan tes.

| Temuan | Perbaikan |
| --- | --- |
| A1 Kebocoran nilai antar siswa | `AssessmentController@show` untuk role `siswa`: hanya melihat nilai miliknya (grade/stats difilter), akses penilaian di luar rombelnya **403** |
| A2 Kebocoran AuditLog lintas tenant | `AuditLog` kini memakai trait `BelongsToTenant` (scope global) + index `(tenant_id, created_at)`; admin sekolah hanya melihat log tenantnya |
| A3 Journal milik guru lain bisa diedit | `JournalController` menambah `authorizeJournal()` (guru hanya objek `user_id` sendiri) di `show`/`edit`/`update`; `JournalRequest` mewajibkan `schedule_id` milik guru sendiri |
| A4 Assignment milik guru lain bisa dihapus / file lain bisa diunduh | `authorizeAccess()` kini menolak guru untuk assignment `teacher_id` lain; `download()` & `destroy()` memanggil `authorizeAccess()`. Bonus: tipe kembalian `download()` diperbaiki `StreamedResponse` (sebelumnya deklarasi `Response` tidak pernah dipakai file benar → 500) |
| A5 Bentrok jadwal lolos | `ScheduleRequest::withValidator()` membandingkan overlap rentang jam (guru & rombel, scope tahun ajaran, exclude jadwal yang sama saat update). Input waktu dinormalisasi ke detik agar boundary `H:i` vs `H:i:s` benar. Jam berdampingan (09:00 setelah 07:30–09:00) tetap diizinkan |
| A6 EWS duplikat tak terkendali | `EarlyWarningService@createLog` memakai gate warning belum-resolved + `firstOrCreate` per `(student_id, type, trigger_date)`; tambah unique constraint di DB (migrasi dedupe data lama dulu) |

Catatan: `MasterDataTest`, `TenantIsolationTest`, dan tes lain tetap lulus tanpa perubahan — isolasi tenant dan RBAC lain sudah benar sejak awal.

### Wave 1 — Academic Correctness (P1 audit, 2026-09-18)

Prinsip: data boleh tersimpan, yang dihitung harus benar konteksnya (tahun ajaran aktif).

| Temuan | Perbaikan |
| --- | --- |
| B1 Rapor/progress menghitung semua tahun ajaran | Nilai di `show`/`progress`/`rapor` di-scope ke `academic_year_id` rombel siswa; absensi & rekap di `attendanceSummary` di-scope ke tahun ajaran rombel, begitu juga EWS log pada `progress` |
| B2 Skor akhir rapor tidak dinormalisasi | `rapor()` menghitung `final_score` = bobot tertimbang ÷ bobot yang tersedia × 100. Siswa yang hanya punya kategori `tugas` (bobot 20) kini tampil realistis (mis. 80, tidak lagi 16); tanpa kategori → `null` |
| B5 Attendance rate EWS seumur hidup | `checkAttendanceRate` di-scope tahun ajaran berjalan (bukan seumur hidup); `checkAbsenceStreak` juga ikut di-scope tahun |
| B6 N+1 & konteks EWS | `checkLowScore` eager-load `assessment.subject`; `progress` menghapus N+1 submission (eager-load `submissions` per siswa) |
| B8 Guru melihat semua siswa | `authorizeView` kini membatasi guru ke rombel binaan (`homeroom_teacher_id`) untuk `show`/`destroy`/`progress`/`rapor` |
| B9 Index guru belum di-scope | `ScheduleController@index` (hanya jadwal sendiri), `EarlyWarningController@index` (hanya log siswa binaan, termasuk statistik), `AttendanceController@index` (hanya siswa binaan + `totalStudents` binaan) |

Catatan konsistensi: semua scope absensi memakai atribut `academic_year_id` (bukan rentang tanggal) agar konsisten dengan nilai dan data scan/manual yang memang menyimpan tahun ajaran aktif.

### Wave 2 — MATA PELAJARAN (2026-09-18)

Fokus: integritas entitas mapel dan rantai data mapel → guru → jadwal.

| Temuan | Perbaikan |
| --- | --- |
| Hapus mapel menghapus semua data | FK `subject_id` di schedules/assessments (beserta nilai) dan assignments memakai `cascadeOnDelete`. `SubjectController::destroy` kini menolak hapus bila mapel masih dipakai jadwal/penilaian/jurnal/tugas, dengan arahan menonaktifkan lewat Edit. Hapus diizinkan hanya untuk mapel yang benar-benar belum terpakai |
| Guru bisa menilai di kelas/mapel bukan ampuannya | `AssessmentRequest` & `AssignmentRequest` kini mewajibkan guru punya jadwal `(rombel, subject)` pada tahun ajaran aktif (`schedules.user_id = guru`, `subject_id`, `rombel_id`, tahun aktif). Admin/super_admin tidak terikat aturan ini. Dropdown form `create` penilaian & tugas untuk guru juga dibatasi hanya ke (mapel, rombel) yang ada di jadwalnya — konsisten dengan aturan gate |
| Rombel (rombel_id) opsional untuk guru | Untuk guru `rombel_id` kini wajib (dibutuhkan untuk pengecekan jadwal) |

Keputusan rancangan: proteksi hapus dilakukan di controller (guard) — migrasi FK yang sudah berjalan di dev tetap `cascadeOnDelete`, tetapi jalur penghapusan aplikasi satu-satunya lewat controller, sehingga data historis tidak bisa hilang dari UI. Alur kerja baru: admin membuat jadwal dulu → guru baru boleh input nilai & tugas.

### Wave 3 — Rombel & Siswa (2026-09-18)

Fokus: integritas entitas rombel/siswa, relasi jadwal-absensi, dan riwayat perpindahan siswa.

| Temuan | Perbaikan |
| --- | --- |
| Hapus siswa menghilangkan data akademik | `StudentController::destroy` kini menolak hapus bila siswa masih punya absensi, nilai, pengumpulan tugas, log EWS, relasi orang tua, atau riwayat rombel (pesan menampilkan jumlah per jenis). Siswa tanpa data terkait tetap bisa dihapus. Model `Student` menambah relasi `assignmentSubmissions()` |
| Siswa bisa ditempatkan di rombel tenant lain | `StudentRequest.rombel_id` kini memakai `Rule::exists('rombels')` yang di-scope `tenant_id` (super admin tetap bebas). Sebelumnya `exists:rombels,id` membocorkan referensi lintas tenant |
| Siswa baru bisa masuk rombel tahun ajaran non-aktif | `StudentRequest::withValidator` mewajibkan rombel tujuan berasal dari tahun ajaran aktif. Rombel asal tidak dicek ulang saat edit (`rombel_id` tidak berubah), sehingga siswa angkatan lama tetap bisa diperbarui setelah tahun berganti |
| Wali kelas lintas tenant di level rule | `RombelRequest.homeroom_teacher_id` memakai `Rule::exists('users')` yang di-scope tenant; validasi peran guru/admin tetap dijaga `withValidator` (defense in depth) |
| Riwayat rombel bisa punya baris "open" ganda | `openRombelHistory` menutup lebih dulu baris open untuk rombel yang sama sebelum membuat baris baru; `closeRombelHistory` disederhanakan (hapus `latest()->limit(1)` yang tidak berpengaruh pada `update`) dan menstandarkan format tanggal `Y-m-d` (cast `date:Y-m-d`) |
| Jadwal dihapus menyilakan jurnal & absensi pelajaran | `ScheduleController::destroy` menolak hapus bila jadwal masih direferensikan jurnal atau absensi ber-type `lesson`. Model `Schedule` menambah relasi `journals()` dan `attendances()` |

Catatan: `assessment_grades`, `attendances`, `assignment_submissions`, `early_warning_logs`, `student_parents`, dan `student_rombel_histories` semuanya `cascadeOnDelete` terhadap `student_id`; guard controller mencegah kehilangan data historis lewat UI.

### Wave 4 — Absensi Core (2026-09-18)

Fokus: rantai identitas → jadwal, validasi jam, duplikasi, koreksi, dan audit pada modul absensi.

| Temuan | Perbaikan |
| --- | --- |
| Satu siswa hanya bisa punya satu absensi `lesson` per hari | Unique lama (`student_id`, `type`, `date`) menabrak absensi pelajaran ganda. Migrasi `2026_09_18_100002` menggantinya dengan unique (`student_id`, `schedule_id`, `date`) sehingga tiap jadwal pelajaran tercatat terpisah. Dedupe `gate_in`/`gate_out` tetap per (`student_id`, `type`, `date`) di aplikasi |
| Absensi pelajaran bisa discan di luar jam pelajaran | `guardLessonSchedule` kini (khusus scan) menolak pencatatan di luar `start_time`–`end_time`, dengan toleransi 15 menit lebih awal dan 1 menit setelah jam selesai. Koreksi manual dikecualikan agar admin/guru bisa memperbaiki data lampau |
| `academic_year_id` absensi pelajaran selalu tahun ajaran aktif | Untuk absensi ber-type `lesson`, tahun ajaran diambil dari jadwal (`schedule.academic_year_id`) sehingga koreksi data tahun lama tetap tersimpan pada tahun ajaran yang benar. Gate tetap memakai tahun ajaran aktif |
| Scan absensi tidak meninggalkan jejak audit | `recordOne` (jalur scan, termasuk scan massal) kini memanggil `log_audit('create', ...)`. Koreksi manual mencatat `old_values` → `new_values` pada `log_audit('update', ...)` |
| Dedupe koreksi manual masih per tipe/tanggal | `storeManual` mencocokkan baris berdasarkan jadwal untuk type `lesson`, dan berdasarkan tipe/tanggal untuk gate — sejalan dengan aturan scan |

### Wave 5 — Hardening Konteks Tenant (P0 audit, 2026-09-18)

Fokus: menutup celah referensi lintas tenant pada gerbang validasi dan menduplikasi jaring pengaman absensi di level DB.

| Temuan | Perbaikan |
| --- | --- |
| P0-1 `ScheduleRequest` memakai `exists` global | Keempat FK di-scope tenant (`Rule::exists` + `when(tenantId)`), ditambah tahun ajaran aktif, guru role `guru`, mapel aktif, dan `withValidator` memastikan rombel berasal dari tahun ajaran yang dipilih serta seluruh entitas satu tenant |
| P0-2 `JournalRequest` memakai `exists` global | `subject_id`/`rombel_id` di-scope tenant; `withValidator` memastikan `schedule_id` milik guru, mapel & rombel konsisten dengan jadwal, dan tidak duplikat; `JournalController` menurunkan `subject_id`/`rombel_id`/`academic_year_id` dari jadwal |
| P0-3 `AssessmentRequest` & `AssignmentRequest` memakai `exists` global | `subject_id`/`rombel_id` di-scope tenant; rombel wajib dari tahun ajaran aktif; aturan guru hanya mengampu `(rombel, mapel)` jadwal tetap berlaku |
| P0-4 `ManualAttendanceRequest` memakai `exists` global | `student_id`/`schedule_id` di-scope tenant (batasan guru ke rombel binaan tetap di controller agar alur pesan error tidak berubah) |
| P0-5 Duplikat gate hanya dicek di aplikasi | Migrasi `2026_09_18_100003` menambah kolom `dedupe_key` (diisi observer `Attendance::creating`) + unique `attendance_dedupe_key_unique`; `AttendanceController` menangkap `QueryException` unique (kode 1062 / `Duplicate entry` / `UNIQUE constraint failed`) dan mengembalikan hasil `info` idempotent, bukan HTTP 500 |
| P0-6 Guru melihat feed presensi seluruh sekolah | Feed `recentAttendances` pada dashboard guru di-scope ke siswa rombel binaan (`whereHas('student.rombel')`); admin sekolah tetap melihat seluruh tenant |

Catatan migrasi: index unique lama (`student_id`, `type`, `date`) di MySQL menopang foreign key `student_id`, sehingga `2026_09_18_100002` menambahkan index `attendances_student_id_index` lebih dulu sebelum menghapus unique lama — data lama tetap aman dan tidak ada constraint lama yang dibuang demi menghindari error.

### Wave 6 — Business Correctness (P1 audit lanjutan, 2026-09-18)

Fokus: konteks tahun ajaran pada portal orang tua, akurasi rekap absensi, kekonsistenan dashboard jurnal, integritas relasi guru–jadwal, dan jaminan keunikan di level DB.

| Temuan | Perbaikan |
| --- | --- |
| P1-1 Presensi portal orang tua memakai batas minggu kalender | `ParentDashboardController` menghitung `whereBetween(date, [now()->subDays(6), now()])` sehingga jendela benar-benar 7 hari terakhir termasuk hari ini |
| P1-2 Rata-rata nilai & EWS portal orang tua seumur hidup | Nilai di-scope tahun ajaran rombel anak (`whereHas('assessment', academic_year_id)`) dan peringatan EWS difilter `trigger_date` dalam rentang tahun ajaran; anak tanpa rombel → kosong (tanpa fallback seumur hidup) |
| P1-3 Rekap absensi siswa menghitung gate | `attendanceSummary` hanya menghitung `type='lesson'` (kunci hadir/sakit/izin/alpha/total tetap untuk kompatibilitas view) |
| P1-3b Riwayat rombel open ganda saat pindah | `openRombelHistory` menutup SEMUA baris open siswa (bukan hanya rombel yang sama); migrasi `100004` menambah generated `open_student_id` + unique di level DB |
| P1-4 Daftar jurnal tertunda dashboard salah/kosong | `pendingJournals()` ditulis ulang: jadwal tahun ajaran aktif, `day_of_week` hari ini, difilter ke jurnal milik guru (`isGuru`), mengecualikan jadwal yang jurnalnya sudah diisi hari ini, diurutkan jam mulai |
| P1-5 Validasi hari jurnal | Keputusan: hari jadwal TIDAK dipaksa sama (sesi susulan/make-up valid); hanya tanggal `before_or_equal:today` yang dipertahankan |
| P1-6 Guru pemilik jadwal bisa dihapus (jadwal yatim) | `TeacherController::destroy` menolak hapus bila masih punya jadwal; migrasi `100006` mengubah FK `schedules.user_id` menjadi `restrictOnDelete` |
| P1-8 Jurnal duplikat sesi sama lolos | Migrasi `100005` menambah generated `schedule_key`/`subject_key` + unique `journal_session_unique`; `JournalRequest` juga menolak jurnal non-jadwal ganda pada (guru, tanggal, rombel, mapel) |
| NISN unik global menghalangi multi-tenant | Migrasi `100007` mengganti unique `students.nisn` menjadi unique (`tenant_id`, `nisn`) |

Catatan: generated column memakai VIRTUAL (bukan STORED) karena MySQL 8 menolak STORED pada tabel ber-FK (`error 1215`); VIRTUAL mendukung unique index di MySQL maupun SQLite. Seluruh perubahan diverifikasi di dev MySQL (generated column ada, unique index aktif, FK `schedules_user_id_foreign` `delete_rule=RESTRICT`).

### Wave 7 — Data Integrity & Reporting (P2 audit, 2026-09-19)

Fokus: melindungi data saat hapus master, melengkapi audit trail pada master data, normalisasi mapel guru, audit indeks kueri tanggal, dan pelaporan dashboard gol P2.

| Temuan | Perbaikan |
| --- | --- |
| P2-1 Hapus tahun ajaran mengkaskade menghancurkan rombel/jadwal/jurnal/nilai | Migrasi `100008` mengubah FK `rombels/schedules/journals/assessments.academic_year_id` menjadi `restrictOnDelete`; `AcademicYearController::destroy` menolak hapus dan melaporkan jumlah data terkait per entitas |
| P2-2 Audit trail belum mencakup rombel, guru, tahun ajaran | `log_audit()` ditambahkan ke `RombelController`, `TeacherController`, `AcademicYearController` (create/update/delete; guru mencatat `subject_id`/`subject_text`) |
| P2-3 Mapel guru berupa teks bebas (tidak konsisten) | Migrasi `100009`: kolom `teachers.subject` di-rename menjadi `subject_text` (fallback legacy, tidak dihapus) + kolom baru `subject_id` (FK `subjects`, nullOnDelete) dengan backfill pencocokan nama; `TeacherRequest` memvalidasi `subject_id` eksis per tenant; form memakai select + input teks opsional; tampilan fallback `subject_id → subject_text → '-'` |
| P2-4 Kinerja kueri `whereDate('date', ...)` | Audit via EXPLAIN di dev MySQL: memakai index `(tenant_id, date)` ("Using index condition"); tidak ada penulisan ulang kueri. `whereDate` dipertahankan karena SQLite menyimpan `date` sebagai datetime (`where('date','Y-m-d')` gagal lintas DB). `terlambat` TIDAK ditambahkan (PRD hanya Hadir/Sakit/Izin/Alpha). |
| P2-5 Statistik dashboard guru + tren nilai antar semester | `DashboardController::adminSekolahStats` menambah `today_schedules`, `teachers_teaching_today` (jadwal hari ini, distinct guru), `teachers_idle_today`; halaman progres siswa menambah kartu "Tren Nilai per Tahun Ajaran" (rata-rata nilai per tahun ajaran dari seluruh riwayat, diurutkan nama tahun) |

Catatan: statistik guru P2-5 dihitung dari jadwal (`schedules.day_of_week`) karena belum ada model absensi guru; label ditampilkan apa adanya ("Guru Mengajar Hari Ini" = guru berjadwal hari ini), bukan klaim absen/izin aktual. Dua migrasi P2 diterapkan ke dev MySQL dan diverifikasi (`migrate:status` Ran).

## 16. Catatan Riwayat Perbaikan Lain

- `2026_09_18_100001_create_student_rombel_histories_table` menambah tabel riwayat perpindahan rombel siswa (model `StudentRombelHistory`); ditampilkan pada halaman detail siswa.
- `2026_09_18_100002_adjust_attendances_unique_per_schedule` mengganti unique absensi menjadi per (`student_id`, `schedule_id`, `date`) agar absensi pelajaran ganda dalam sehari dapat tercatat; menambah `attendances_student_id_index` sebagai penopang FK `student_id` di MySQL.
- `2026_09_18_100003_add_dedupe_key_to_attendances_table` menambah `attendances.dedupe_key` + unique `attendance_dedupe_key_unique` sebagai jaring pengaman duplikasi lintas tipe (termasuk gate `schedule_id` kosong).
- `2026_09_18_100004_add_unique_open_rombel_history_to_student_rombel_histories_table` menambah kolom generated VIRTUAL `open_student_id` + unique `student_rombel_histories_open_student_unique` agar satu siswa hanya punya satu baris riwayat rombel "open".
- `2026_09_18_100005_add_journal_session_uniqueness_to_journals_table` menambah generated VIRTUAL `schedule_key`/`subject_key` + unique `journal_session_unique` (`user_id`, `date`, `rombel_id`, `schedule_id|0`, `subject_id|0`).
- `2026_09_18_100006_restrict_schedule_teacher_deletion` mengubah FK `schedules.user_id` menjadi `restrictOnDelete` agar guru yang masih memiliki jadwal tidak dapat dihapus.
- `2026_09_18_100007_scope_students_nisn_unique_to_tenant` mengganti unique global `students.nisn` menjadi unique (`tenant_id`, `nisn`).
- `2026_09_18_100008_restrict_academic_year_deletion` mengubah FK `rombels/schedules/journals/assessments.academic_year_id` menjadi `restrictOnDelete` agar tahun ajaran dengan data terkait tidak dapat dihapus.
- `2026_09_18_100009_add_subject_id_to_teachers_table` menambah `teachers.subject_id` (FK `subjects`, nullOnDelete) dan mengganti nama `teachers.subject` menjadi `subject_text` sebagai fallback legacy.