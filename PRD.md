Berikut adalah **Product Requirement Document (PRD)** komprehensif yang dirancang untuk arsitektur aplikasi sekolah berbasis **SaaS & Enterprise**. Dokumen ini telah disesuaikan dengan alur relasi data antar-modul serta peta jalan (*roadmap*) pengembangan Fase 1 hingga Fase 4 yang kamu lampirkan.

---

# PRODUCT REQUIREMENT DOCUMENT (PRD)

## **Sistem Informasi & Manajemen Sekolah Terpadu (Smart School Enterprise SaaS)**

---

### 1. RINGKASAN EKSEKUTIF & KONSEP ARSITEKTUR

#### 1.1 Visi Produk

Platform ini adalah **Centralized School Operating System** berbasis *Multi-Tenant SaaS* yang dapat digabungkan dengan deployment skala *Enterprise* (multi-cabang/yayasan). Sistem ini menghubungkan manajemen sekolah, tenaga pendidik, siswa, dan orang tua secara *real-time*.

#### 1.2 Peta Relasi Data (Database Entity Relationship Concept)

Seluruh modul terhubung secara terstruktur melalui skema relasi berikut:

```text
[Tenant / Sekolah] ──> [Tahun Ajaran & Semester]
        │
        ├──> [Tenaga Pendidik / PTK] ──> [Jadwal Mengajar] ──> [Jurnal KBM]
        │                                                           │
        ├──> [Kelas / Rombel] ──> [Data Siswa] ───────────────> [Input Nilai]
        │                              │                            │
        │                        [QR / Kartu Tap]                   │
        │                              │                            │
        │                        [Log Absensi] ─────────────> [Dashboard & EWS]
        │                                                           │
        └──> [Orang Tua / Wali] <────────────────────────────────────┘

```

---

### 2. DETIL SPESIFIKASI MODUL BERDASARKAN FASE

---

### FASE 1: CORE OPERATIONS & DAILY ACTIVITY

#### 1.1 Modul Dashboard Sekolah (SaaS & Enterprise Level)

* **Tujuan**: Menyediakan visibilitas *real-time* kepada Kepala Sekolah, Admin, dan Yayasan mengenai operasional harian sekolah.
* **Fitur & Spesifikasi**:
* **Ringkasan Hari Ini**:
* Metrik *real-time* persentase kehadiran siswa & guru hari ini.
* Statistik guru yang sedang mengajar vs guru absen/izin.
* Jumlah pelanggaran / catatan khusus harian.


* **Pintasan Cepat (*Quick Shortcuts*)**:
* Cetak Kartu QR Siswa, Broadcast Notifikasi, Input Nilai Darurat, Tambah Siswa Baru.


* **Aktivitas Terbaru**:
* *Live Log Feed* presensi gerbang (pindai QR/Kartu).
* Notifikasi jurnal mengajar yang belum diisi guru.


* **Enterprise Switcher** (Khusus Multi-Cabang/Yayasan):
* *Dropdown* filter untuk berpindah antar-unit sekolah/kampus secara instan.





#### 1.2 Modul Absensi QR Code & Tap Kartu (NFC/RFID)

* **Tujuan**: Otomatisasi pencatatan kehadiran gerbang & kelas dengan kecepatan scan < 1 detik per siswa.
* **Fitur & Spesifikasi**:
* **Scan QR Siswa**: Scan via kamera HP/Tablet Kios/Webcam di pintu gerbang atau ruang kelas.
* **Tap Kartu Pelajar (RFID/NFC)**: Integrasi dengan *Card Reader* USB/Serial/NFC tanpa memerlukan koneksi rumit.
* **Mode Absen**:
* *Masuk & Pulang Gerbang* (Kiosk Mode).
* *Presensi Pelajaran/Mapel* (Dioperasikan oleh Guru di Kelas).


* **Absen Manual & Override**: Penyesuaian status (Hadir, Sakit, Izin, Alpha) oleh Wali Kelas/Admin jika kartu/QR tertinggal.
* **Fitur Offline-Sync (Fallback)**: Jika jaringan internet sekolah terputus, data tersimpan lokal di peranti *scanner* dan sinkron otomatis saat *online*.



#### 1.3 Modul Input Nilai & Penilaian (*Assessment*)

* **Tujuan**: Pengolahan nilai akademis fleksibel dengan fleksibilitas bobot dan format rapor.
* **Fitur & Spesifikasi**:
* **Daftar Nilai per Kelas**: Tampilan tabel interaktif (*Excel-like grid*) per Mata Pelajaran & Rombel.
* **Input Cepat (*Bulk Entry & Import*)**:
* Navigasi input menggunakan tombol `Enter` / `Tab`.
* Import/Export templat nilai via Excel (.xlsx / .csv).


* **Bobot & Kategori Penilaian**:
* Pengaturan bobot otomatis: Tugas (20%), Formatif/UH (30%), UTS (25%), UAS (25%).
* Konversi nilai otomatis ke Capaian Kompetensi (Deskripsi Rapor Kurikulum Merdeka / K13).





---

### FASE 2: ACADEMIC & STUDENT MONITORING

#### 2.1 Modul Kelola Pengajaran & Jurnal KBM

* **Tujuan**: Memetakan alur mengajar guru dan memastikan akuntabilitas jam pelajaran.
* **Fitur & Spesifikasi**:
* **Jadwal Mengajar**: Timetable interaktif berdasarkan hari, jam pelajaran, dan ruang kelas.
* **Jurnal Kelas (Jurnal KBM)**:
* Guru wajib mengisi topik/materi pembelajaraan, absensi kelas, dan catatan kejadian sebelum menutup sesi jam pelajaran.


* **Tugas & PR**:
* Pembuatan tugas harian, batas waktu pengumpulan (*deadline*), dan upload berkas acuan.





#### 2.2 Modul Monitoring Siswa & Early Warning System (EWS)

* **Tujuan**: Mengidentifikasi potensi penurunan akademik atau kedisiplinan siswa sejak dini.
* **Fitur & Spesifikasi**:
* **Profil Perkembangan**: Grafik tren nilai siswa dari semester ke semester dan statistik kehadiran.
* **Peringatan Dini (*Early Warning System*)**:
* *Trigger Otomatis*: Notifikasi ke Guru BK/Wali Kelas jika siswa:
* Absen tanpa keterangan > 3 hari berturut-turut atau presensi akumulatif < 80%.
* Nilai rata-rata mapel di bawah batas KKM/Kriterian Ketuntasan.




* **Riwayat Belajar**: Rekam jejak portofolio siswa, tugas yang belum dikumpulkan, dan catatan kebiasaan/pelanggaran.



---

### FASE 3: SCHOOL MANAGEMENT & PARENT ENGAGEMENT

#### 3.1 Modul Portal Orang Tua (*Parent Dashboard / Web App*)

* **Tujuan**: Transparansi penuh bagi orang tua untuk memantau perkembangan anak secara *real-time*.
* **Fitur & Spesifikasi**:
* **Pantau Kehadiran Anak**:
* Status jam masuk & pulang anak (waktu presensi QR/Kartu tercatat *real-time*).


* **Lihat Nilai Anak**:
* Akses rincian nilai tugas, ujian harian, serta Rapor Digital (.PDF).


* **Pemberitahuan & Notifikasi**:
* Push Notification / WhatsApp Gateway saat anak berhasil scan masuk gerbang.
* Pengumuman kegiatan sekolah dan pengingat tugas sekolah.





#### 3.2 Modul Kelola Data Sekolah (Master Data PTK & Siswa)

* **Tujuan**: Pusat manajemen data *Tenaga Pendidik dan Kependidikan (PTK)* serta siswa.
* **Fitur & Spesifikasi**:
* **Data Tenaga Pendidik (PTK)**:
* Manajemen data guru, NUPTK, status kepegawaian (GTY, PTT, ASN), dan alokasi jam mengajar.


* **Data Siswa & Rombel**:
* Pengelompokan siswa berdasarkan NISN, Kelas, Rombel, dan Tahun Ajaran.


* **Generator Kartu & QR Siswa**:
* *Automated Layout Generator*: Pembuatan desain Kartu Pelajar ber-QR Code secara kolektif yang siap dicetak.





---

### FASE 4: AUTHENTICATION, SECURITY & SAAS ENTERPRISE CORE

#### 4.1 Modul Masuk & Hak Akses (RBAC & Multi-Tenancy)

* **Tujuan**: Keamanan data tingkat tinggi serta pengelolaan akses bertingkat.
* **Fitur & Spesifikasi**:
* **Login Sekolah & Multi-Tenancy**:
* Subdomain khusus per sekolah (contoh: `sma1.platformsekolah.id`) atau *Custom Domain* (Enterprise).


* **Peran Pengguna (*Role-Based Access Control / RBAC*)**:
* Matriks otorisasi terpisah untuk: Super Admin SaaS, Admin Sekolah, Kepala Sekolah, Guru Mata Pelajaran, Wali Kelas, Guru BK, Siswa, dan Orang Tua.


* **Kelola Akun & Audit Trail**:
* Pengaturan integrasi Single Sign-On (SSO Google Workspace/Belajar.id).
* Log aktivitas pengguna (*Audit Log*) untuk melacak perubahan data nilai/absensi.





---

### 3. MATRIKS HAK AKSES PENGGUNA (RBAC MATRIX)

| Modul / Fitur | Admin Sekolah | Guru | Siswa | Orang Tua | Super Admin SaaS |
| --- | --- | --- | --- | --- | --- |
| **Kelola Master Data (PTK/Siswa)** | **Full** | Read | Read (Diri) | Read (Anak) | System Wide |
| **Absensi QR / Kartu (Gerbang)** | Manage | Scan | View | View | System Wide |
| **Input Nilai & Jurnal KBM** | Review | **Full** | Read (Diri) | Read (Anak) | - |
| **Monitoring EWS & BK** | View | Update | Read (Diri) | View | - |
| **Pengaturan SaaS / Subdomain** | Read | - | - | - | **Full** |

---

### 4. ALUR KERJA TERINTEGRASI (*WORKFLOW SCENARIO*)

```text
1. ABSENSI GERBANG (06:30 - 07:15)
   Siswa Tap Kartu/Scan QR ➔ Database Ter-update ➔ Push Notifikasi ke App/WA Orang Tua ("Anak Anda telah tiba di sekolah pukul 06:45").

2. KBM DI KELAS (07:30 - 12:00)
   Guru membuka Modul Kelola Pengajaran ➔ Mengisi Jurnal KBM ➔ Mengisi Absensi Kelas (Mapel) ➔ Input Nilai Tugas.

3. SINKRONISASI DATA AUTOMATIS
   Sistem menghitung akumulasi nilai & kehadiran ➔ Jika Nilai/Hadir < Threshold ➔ Trigger Notifikasi Early Warning ke Guru BK & Orang Tua.

4. LAPORAN DOKUMENTATIF
   Orang Tua & Siswa membuka Portal ➔ Melihat rekapitulasi kehadiran harian & progress capaian pembelajaran.

```

---

### 5. SPESIFIKASI TEKNIS & HARDWARE RECOMMENDATIONS

1. **Perangkat Absensi (Scan QR / Tap Kartu)**:
* **NFC Reader**: Desktop USB RFID Reader (Mifare 13.56MHz atau EM4100 125kHz).
* **QR Scanner**: USB 2D Barcode/QR Scanner Fixed-mount atau Peranti Tablet Android/iPad dengan Kamera Depan Auto-Focus.


2. **Konektivitas & Notifikasi**:
* **Realtime Protocol**: WebSockets (Pusher / Socket.io) untuk *feed* absensi instan di dashboard.
* **Message Broker**: Integration ke WhatsApp Official API (Fonnte/Waba) & Firebase Cloud Messaging (FCM).


3. **Penyimpanan Data & Keamanan**:
* Multitenancy berbasis *Row-Level Security* (RLS) atau *Database per Tenant* (untuk Klien Enterprise Skala Besar).
* Backup otomatis basis data harian (*Automated Daily Backup*).