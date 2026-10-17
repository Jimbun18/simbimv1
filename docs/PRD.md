# PRD: Transformasi Digital Sistem Informasi SDI Bina Insan Mandiri Purwokerto

|  |  |
| --- | --- |
| **Nama produk** | SIM-BIM (Sistem Informasi Manajemen SDI Bina Insan Mandiri) |
| **Versi dokumen** | 1.1 (Draft): field master data diperinci di bagian 11 |
| **Tanggal** | 1 Oktober 2026 |
| **Status** | Draft untuk review |
| **Pemilik produk** | Kepala Sekolah SDI Bina Insan Mandiri |

> Catatan: angka target, estimasi waktu, dan beberapa keputusan teknis di dokumen ini adalah **asumsi awal** dan perlu dikonfirmasi bersama pihak sekolah (lihat bagian 19).

---

## 1. Ringkasan Eksekutif

SDI Bina Insan Mandiri Purwokerto saat ini mengelola data sekolah menggunakan Excel dengan tabel masing-masing. Pendekatan ini rawan duplikasi, salah input, sulit direkap, tidak memiliki jejak audit, dan tidak memungkinkan wali murid memantau perkembangan anak secara langsung.

SIM-BIM adalah aplikasi berbasis web (PWA) dengan tiga peran pengguna (Admin, Guru, Wali Murid) yang mengintegrasikan data guru & siswa, absensi, administrasi, keuangan, sarana prasarana, serta portal pemantauan progres anak, dilengkapi fitur **import dari Excel** untuk migrasi data lama.

## 2. Latar Belakang & Masalah

| # | Masalah saat ini (Excel) | Dampak |
| --- | --- | --- |
| 1 | Data tersebar di banyak file/tabel | Data tidak konsisten, sulit dicari |
| 2 | Absensi guru dan murid dicatat manual | Rekap lambat, rawan manipulasi |
| 3 | Tidak ada bukti mengajar yang terverifikasi | Akuntabilitas kehadiran lemah |
| 4 | Keuangan dan inventaris dicatat terpisah | Laporan lambat, sulit diaudit |
| 5 | Wali murid tidak punya akses informasi anak | Komunikasi sekolah–orang tua terbatas |
| 6 | Tidak ada backup & log aktivitas terstruktur | Risiko kehilangan data, tidak ada jejak perubahan |

## 3. Tujuan & Indikator Keberhasilan

| Tujuan | Indikator (target awal, perlu disepakati) |
| --- | --- |
| Memusatkan data sekolah dalam satu sistem | 100% data siswa & guru aktif termigrasi |
| Mempercepat rekap absensi | Rekap bulanan dihasilkan \< 1 menit |
| Meningkatkan akuntabilitas mengajar | ≥ 95% sesi mengajar memiliki bukti foto |
| Meningkatkan keterlibatan wali murid | ≥ 70% wali murid aktif login per bulan |
| Menjamin keamanan data | Backup harian berhasil ≥ 99%; seluruh aksi penting tercatat di log |
| Adopsi oleh staf | ≥ 90% guru memakai sistem untuk absensi setelah 2 bulan |

## 4. Ruang Lingkup

### 4.1 Dalam lingkup (In Scope)

- Autentikasi dan manajemen akun berbasis role
- Manajemen data guru, siswa, kelas, tahun ajaran, relasi wali–siswa
- Absensi guru, absensi murid, bukti mengajar (upload foto)
- Pencatatan kegiatan harian dan progres pembelajaran siswa
- Portal wali murid (read-only)
- Administrasi sekolah, keuangan, sarana prasarana
- Import data dari Excel/CSV dan export ke Excel/PDF
- Backup/restore data dan log aktivitas
- Notifikasi (in-app; WhatsApp/email opsional)

### 4.2 Di luar lingkup (Out of Scope, versi 1.0)

- Pembayaran online (payment gateway)
- Aplikasi native Android/iOS (digantikan PWA)
- E-rapor resmi/integrasi Dapodik
- Ujian online / LMS
- Fitur chat dua arah guru–wali

## 5. Pengguna & Persona

| Role | Persona | Kebutuhan utama |
| --- | --- | --- |
| **Admin: Kepala Sekolah** | Pimpinan sekolah | Dashboard ringkasan, memantau kehadiran, laporan keuangan, melihat log |
| **Admin: Staf Administrasi** | Operator data harian | Input/ubah data, import Excel, kelola keuangan & sarpras, user, backup |
| **Guru** | Wali kelas/guru mapel | Absensi cepat dari HP, upload foto bukti mengajar, input progres siswa |
| **Wali Murid** | Orang tua siswa | Melihat kehadiran, kegiatan harian, dan progres anak dengan mudah di HP |

## 6. Kebutuhan Fungsional

Prioritas memakai MoSCoW: **M** = Must, **S** = Should, **C** = Could.

### 6.1 Autentikasi & Manajemen User (Admin)

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-AUTH-01 | Login dengan username/email/no. HP + password | M |
| FR-AUTH-02 | Logout dan sesi otomatis berakhir setelah tidak aktif (default 30 menit) | M |
| FR-AUTH-03 | Pembatasan percobaan login gagal (5x, kunci 15 menit) | M |
| FR-AUTH-04 | Admin membuat, mengubah, menonaktifkan, dan mereset password akun | M |
| FR-AUTH-05 | Penetapan role (Admin/Guru/Wali) per akun | M |
| FR-AUTH-06 | Pengguna wajib mengganti password pada login pertama | S |
| FR-AUTH-07 | Pembuatan akun wali murid massal dari data siswa | S |

### 6.2 Data Guru & Siswa (Admin)

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-DATA-01 | CRUD data guru lengkap (identitas, kepegawaian, pendidikan, sertifikasi, dokumen) sesuai bagian 11.1.f | M |
| FR-DATA-02 | CRUD data siswa lengkap (identitas, alamat, kesehatan, asal & masuk, status) sesuai bagian 11.1.c | M |
| FR-DATA-03 | CRUD kelas, tahun ajaran, mata pelajaran, ruang, jam pelajaran, jadwal, penugasan guru | M |
| FR-DATA-04 | Data ayah/ibu/wali dan relasi siswa ↔ wali (satu wali bisa memiliki lebih dari satu anak; satu kontak utama per siswa) | M |
| FR-DATA-05 | Kenaikan kelas / mutasi / kelulusan siswa | S |
| FR-DATA-06 | Pencarian, filter, dan paginasi | M |
| FR-DATA-07 | Foto profil guru/siswa | C |
| FR-DATA-08 | Soft delete (data tidak dihapus permanen, dapat dipulihkan) | S |
| FR-DATA-09 | Data staf administrasi/tenaga kependidikan dan profil sekolah | M |
| FR-DATA-10 | Riwayat kelas dan mutasi siswa (naik kelas, pindah, lulus) | S |
| FR-DATA-11 | Tabel referensi (agama, pendidikan, pekerjaan, dll.) yang dapat dikelola Admin | S |
| FR-DATA-12 | Pembatasan tampilan data sensitif (NIK, kesehatan, rekening) per role | M |
| FR-DATA-13 | Deteksi bentrok jadwal guru/kelas/ruang | S |

### 6.3 Import & Export Data

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-IMP-01 | Import `.xlsx`/`.csv` untuk siswa, guru, kelas, wali murid | M |
| FR-IMP-02 | Template Excel standar dapat diunduh per jenis data; kolom mengikuti bagian 11.5 | M |
| FR-IMP-03 | Validasi per baris (wajib isi, format, duplikat, referensi) | M |
| FR-IMP-04 | Preview hasil validasi sebelum disimpan (valid / error / duplikat) | M |
| FR-IMP-05 | Pilihan penanganan duplikat: lewati / perbarui / tolak | M |
| FR-IMP-06 | Laporan error dapat diunduh (nomor baris + alasan) | M |
| FR-IMP-07 | Import berjalan sebagai satu batch transaksi dan dapat di-rollback | S |
| FR-IMP-08 | Pemetaan kolom (column mapping) untuk Excel dengan header berbeda | S |
| FR-IMP-09 | Import data keuangan dan inventaris sarpras | S |
| FR-IMP-10 | Import riwayat absensi (arsip) | C |
| FR-EXP-01 | Export rekap ke Excel dan PDF | M |

### 6.4 Absensi

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-ABS-01 | Guru melakukan absen masuk dan pulang (waktu server otomatis) | M |
| FR-ABS-02 | Status guru: hadir, izin, sakit, dinas, alpa | M |
| FR-ABS-03 | Validasi lokasi (geofence sekolah) untuk absen guru, dapat diaktifkan/dinonaktifkan | S |
| FR-ABS-04 | Guru mengisi absensi murid per kelas/sesi (hadir, izin, sakit, alpa) | M |
| FR-ABS-05 | **Upload foto bukti mengajar** per sesi (wajib sebelum absensi murid dikirim, dapat dikonfigurasi) | M |
| FR-ABS-06 | Foto otomatis dikompres, dicap waktu, dan terhubung ke sesi/jadwal | M |
| FR-ABS-07 | Edit absensi murid oleh guru dibatasi (mis. 24 jam); setelahnya hanya Admin | S |
| FR-ABS-08 | Admin melihat rekap absensi guru dan murid (harian/mingguan/bulanan, per kelas) | M |
| FR-ABS-09 | Koreksi absensi oleh Admin dengan alasan, tercatat di log | M |
| FR-ABS-10 | Pengajuan izin/sakit guru dengan lampiran dan persetujuan Kepala Sekolah | C |
| FR-ABS-11 | Wali murid melihat kehadiran anak | M |

### 6.5 Kegiatan Harian & Progres Pembelajaran

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-PRG-01 | Guru mencatat kegiatan harian siswa/kelas (judul, deskripsi, tanggal) | M |
| FR-PRG-02 | Guru mencatat progres pembelajaran per siswa (capaian, catatan, nilai/kategori) | M |
| FR-PRG-03 | Guru melampirkan foto kegiatan (opsional) | C |
| FR-PRG-04 | Wali murid melihat kegiatan harian dan progres anak (timeline) | M |
| FR-PRG-05 | Wali hanya dapat melihat data anaknya sendiri | M |
| FR-PRG-06 | Ringkasan progres per periode (bulanan/semester) | S |

### 6.6 Administrasi

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-ADM-01 | Pengelolaan tahun ajaran dan semester aktif | M |
| FR-ADM-02 | Pengelolaan jadwal pelajaran | M |
| FR-ADM-03 | Arsip dokumen/surat (upload, kategori, pencarian) | S |
| FR-ADM-04 | Pengumuman sekolah yang tampil di dashboard guru dan wali | S |
| FR-ADM-05 | Dashboard Kepala Sekolah (kehadiran hari ini, ringkasan keuangan, sarpras) | M |

### 6.7 Keuangan

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-KEU-01 | Master jenis biaya (SPP, uang pangkal, kegiatan, dll.) | M |
| FR-KEU-02 | Pembuatan tagihan per siswa/kelas/periode | M |
| FR-KEU-03 | Pencatatan pembayaran (tunai/transfer), cicilan, bukti, dan kuitansi PDF | M |
| FR-KEU-04 | Pencatatan pemasukan dan pengeluaran lain (kas sekolah) | M |
| FR-KEU-05 | Status tagihan: belum bayar, sebagian, lunas, menunggak | M |
| FR-KEU-06 | Laporan keuangan harian/bulanan/tahunan, export Excel/PDF | M |
| FR-KEU-07 | Wali melihat tagihan dan riwayat pembayaran anak (read-only) | C |
| FR-KEU-08 | Pembatalan/koreksi transaksi hanya melalui jurnal pembalik, tercatat di log | S |

### 6.8 Sarana & Prasarana (Sarpras)

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-SAR-01 | CRUD aset (kode, nama, kategori, lokasi, tahun, nilai, kondisi) | M |
| FR-SAR-02 | Status kondisi: baik, rusak ringan, rusak berat, hilang | M |
| FR-SAR-03 | Pencatatan perawatan/perbaikan dan biayanya | S |
| FR-SAR-04 | Peminjaman dan pengembalian barang | C |
| FR-SAR-05 | Foto aset dan label kode (QR opsional) | C |
| FR-SAR-06 | Laporan inventaris dan export | M |

### 6.9 Backup, Log Aktivitas, Profil, dan Setting

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-BCK-01 | Backup otomatis terjadwal (harian) database dan file | M |
| FR-BCK-02 | Backup manual atas permintaan Admin | M |
| FR-BCK-03 | Daftar riwayat backup (waktu, ukuran, status) dan unduh | M |
| FR-BCK-04 | Restore dari backup oleh Admin dengan konfirmasi berlapis | S |
| FR-BCK-05 | Backup otomatis sebelum import besar | S |
| FR-LOG-01 | Pencatatan aktivitas: login, CRUD, import, export, koreksi, backup/restore | M |
| FR-LOG-02 | Log memuat pengguna, waktu, modul, aksi, IP, dan nilai sebelum/sesudah (untuk data penting) | M |
| FR-LOG-03 | Filter dan pencarian log; log tidak dapat diubah/dihapus dari UI | M |
| FR-PRO-01 | Semua role dapat melihat dan mengubah profil (nama, kontak, foto) | M |
| FR-PRO-02 | Ganti password | M |
| FR-SET-01 | Pengaturan notifikasi, tampilan (tema/bahasa) per pengguna | S |
| FR-SET-02 | Pengaturan sistem oleh Admin (jam masuk, geofence, batas edit absensi, wajib foto) | S |

### 6.10 Notifikasi

| ID | Kebutuhan | Prioritas |
| --- | --- | --- |
| FR-NTF-01 | Notifikasi in-app (pengumuman, absensi, progres baru) | S |
| FR-NTF-02 | Notifikasi WhatsApp/email ke wali (anak tidak hadir, tagihan jatuh tempo) | C |
| FR-NTF-03 | Pengingat absen untuk guru yang belum check-in | C |

## 7. Matriks Hak Akses (RBAC)

C = Create, R = Read, U = Update, D = Delete. Kolom Admin dibagi dua sub-role.

| Fitur | Kepala Sekolah | Staf Administrasi | Guru | Wali Murid |
| --- | --- | --- | --- | --- |
| Data guru | R | CRUD | R (diri sendiri) | – |
| Data siswa | R | CRUD | R (kelas diampu) | R (anak sendiri) |
| Import/Export data | R (hasil) | CRUD | – | – |
| Absensi guru | R (rekap) | R, U (koreksi) | C, R (diri sendiri) | – |
| Absensi murid | R | R, U (koreksi) | C, R, U (terbatas) | R (anak sendiri) |
| Bukti mengajar | R | R | C, R | – |
| Kegiatan & progres | R | R | C, R, U (kelasnya) | R (anak sendiri) |
| Administrasi | R, approve | CRUD | R (pengumuman) | R (pengumuman) |
| Keuangan | R (laporan) | CRUD | – | R (tagihan anak, opsional) |
| Sarpras | R | CRUD | – | – |
| Manajemen user | R | CRUD | – | – |
| Backup data | R | CRUD | – | – |
| Log aktivitas | R | R | – | – |
| Profile & Setting | Ya | Ya | Ya | Ya |

> Hak akses ditegakkan di **backend** (middleware/policy), bukan hanya dengan menyembunyikan menu.

## 8. User Stories & Acceptance Criteria (Fitur Inti)

**US-01: Absensi murid dengan bukti foto (Guru)** *Sebagai guru, saya ingin mengisi absensi murid dan mengunggah foto bukti mengajar agar kehadiran dan kegiatan mengajar tercatat.*

- Given guru login dan memiliki jadwal hari ini, When membuka menu Absensi Murid, Then daftar kelas/sesi hari itu tampil.
- Given guru memilih sesi, When mengunggah foto (JPG/PNG ≤ 5 MB), Then foto dikompres, diberi cap waktu, dan tersimpan terhubung ke sesi.
- Given foto bukti wajib diaktifkan, When guru mengirim absensi tanpa foto, Then sistem menolak dengan pesan jelas.
- Given absensi terkirim, Then admin dapat melihatnya di rekap dan wali dapat melihat status anaknya.

**US-02: Import data siswa dari Excel (Staf Administrasi)**

- Given staf mengunggah file `.xlsx`, When sistem selesai memvalidasi, Then preview menampilkan jumlah baris valid, error, dan duplikat.
- Given ada baris error, When staf mengunduh laporan error, Then file memuat nomor baris, kolom, dan alasan.
- Given staf mengonfirmasi import, Then data valid tersimpan sebagai satu batch dan tercatat di log aktivitas.
- Given import salah, When staf memilih rollback batch, Then seluruh data batch tersebut dihapus/dipulihkan.

**US-03: Memantau progres anak (Wali Murid)**

- Given wali login, When membuka beranda, Then tampil ringkasan anak (kehadiran, kegiatan terakhir, progres).
- Given wali memiliki lebih dari satu anak, Then wali dapat berganti anak lewat pemilih anak.
- Given wali mencoba membuka data siswa lain via URL/API, Then sistem menolak (403).

**US-04: Dashboard Kepala Sekolah**

- Given Kepala Sekolah login, Then tampil jumlah guru hadir/tidak hadir hari ini, persentase kehadiran siswa, ringkasan pemasukan bulan ini, dan jumlah aset rusak.

**US-05: Log aktivitas (Admin)**

- Given aksi penting dilakukan (ubah data, koreksi absensi, restore), Then log mencatat pengguna, waktu, aksi, dan data sebelum/sesudah, serta tidak dapat diubah dari UI.

## 9. Kebutuhan Non-Fungsional

| Kategori | Spesifikasi |
| --- | --- |
| **Kinerja** | Halaman utama dimuat ≤ 3 detik pada koneksi 4G; respons API median ≤ 500 ms; rekap bulanan ≤ 5 detik |
| **Kapasitas (asumsi)** | Mendukung ±1.000 pengguna terdaftar, ±200 pengguna bersamaan; skala dapat dinaikkan |
| **Ketersediaan** | Target uptime ≥ 99% pada jam sekolah |
| **Skalabilitas** | Database dan storage dapat dipisah/ditingkatkan tanpa mengubah aplikasi |
| **Keamanan** | HTTPS/TLS, hash password (argon2/bcrypt), RBAC, proteksi CSRF/XSS/SQL injection, rate limiting |
| **Privasi** | Sesuai UU PDP; data anak diakses minimal sesuai kebutuhan; persetujuan penggunaan data oleh wali |
| **Usability** | Responsif (HP & desktop), bahasa Indonesia, alur absensi ≤ 3 langkah, ramah pengguna non-teknis |
| **Kompatibilitas** | Chrome, Edge, Firefox, Safari versi terbaru; Android 8+ dan iOS 14+ via PWA |
| **Aksesibilitas** | Kontras warna memadai, ukuran font dapat dibaca, target sentuh ≥ 44 px |
| **Keandalan data** | Backup harian (retensi 30 hari) + salinan off-site; RPO ≤ 24 jam; RTO ≤ 4 jam |
| **Auditabilitas** | Seluruh aksi penting tercatat dan tidak dapat dimodifikasi |
| **Maintainability** | Kode terdokumentasi, versi terkontrol (Git), migrasi database terversi, uji otomatis pada logika kritis |
| **Penyimpanan foto** | Maks. 5 MB/foto sebelum kompres; target ≤ 500 KB setelah kompres; tipe JPG/PNG/WebP |

## 10. Arsitektur Sistem

### 10.1 Diagram Logis

```
┌────────────────────────────────────────────────────────┐
│ PRESENTATION: Web App (Admin) · PWA (Guru) · PWA (Wali)│
└──────────────────────────┬─────────────────────────────┘
                           │ HTTPS · REST/JSON · JWT
┌──────────────────────────▼─────────────────────────────┐
│ BACKEND API: Auth+RBAC · Validasi · Audit Logger       │
│ Modul: Data · Absensi · Progres · Keuangan · Sarpras · │
│        User · Import/Export · Backup · Notifikasi      │
└───────┬───────────────┬────────────────┬───────────────┘
        │               │                │
   ┌────▼────┐    ┌─────▼─────┐    ┌─────▼──────┐
   │Database │    │File Storage│   │Queue/Worker│→ Notifikasi
   │ (RDBMS) │    │(foto, dok) │   │(import,PDF)│
   └────┬────┘    └─────┬─────┘    └────────────┘
        └──────┬────────┘
        ┌──────▼───────┐
        │Backup (lokal │
        │ + off-site)  │
        └──────────────┘
```

### 10.2 Rekomendasi Tech Stack

| Komponen | Rekomendasi | Alternatif |
| --- | --- | --- |
| Frontend | Vue 3 / React + Tailwind CSS, PWA | Next.js |
| Backend | Laravel 11 (PHP 8.3) | NestJS (Node.js) |
| Database | MySQL 8 / PostgreSQL 15 | MariaDB |
| Cache/Queue | Redis | Database queue |
| Storage | Disk server (terenkripsi) / S3-compatible (MinIO) | Cloud storage |
| Excel | Laravel Excel (Maatwebsite) | SheetJS/ExcelJS |
| PDF | DomPDF / Snappy | Puppeteer |
| Web server | Nginx + PHP-FPM + SSL (Let's Encrypt) | Apache |
| Deployment | VPS Linux (Ubuntu LTS), Docker opsional | Hosting terkelola |
| Notifikasi | WhatsApp gateway resmi, SMTP email | FCM push |
| Monitoring | Log aplikasi + uptime monitor | Sentry |

### 10.3 Lingkungan

- **Development**: lokal developer
- **Staging**: salinan produksi untuk UAT
- **Production**: VPS dengan firewall, SSH key-only, update keamanan berkala

## 11. Model Data

### 11.1 Master Data (Field Lengkap)

Keterangan kolom **Wajib**: ✔ = wajib diisi, – = opsional. Semua tabel memiliki `id` (PK), `created_at`, `updated_at`, `deleted_at` (soft delete), `created_by`, `updated_by` kecuali disebut lain.

#### a. Profil Sekolah: `school_profile`

| Field | Tipe | Wajib | Keterangan / Validasi |
| --- | --- | --- | --- |
| nama_sekolah | varchar(150) | ✔ |  |
| npsn | char(8) | – | Angka 8 digit |
| nss | varchar(20) | – |  |
| jenjang | enum | ✔ | SD/MI (default SD) |
| status_sekolah | enum | – | Negeri / Swasta |
| yayasan | varchar(150) | – |  |
| alamat_jalan, rt, rw, desa_kelurahan, kecamatan, kab_kota, provinsi, kode_pos | varchar | ✔ (jalan, kab_kota, provinsi) | Kode pos 5 digit |
| telepon, email, website | varchar | – | Format email/URL valid |
| kepala_sekolah_id | FK → teachers | – |  |
| akreditasi, tahun_akreditasi | varchar / year | – |  |
| tahun_berdiri | year | – |  |
| logo_path | varchar | – | JPG/PNG ≤ 2 MB |
| latitude, longitude, radius_geofence_m | decimal / int | – | Untuk validasi lokasi absen guru |
| jam_masuk_default, jam_pulang_default, toleransi_terlambat_menit | time / int | – | Aturan absensi |

#### b. Tahun Ajaran: `academic_years`

| Field | Tipe | Wajib | Keterangan / Validasi |
| --- | --- | --- | --- |
| nama | varchar(9) | ✔ | Contoh: 2026/2027 |
| semester | enum | ✔ | Ganjil / Genap |
| tgl_mulai, tgl_selesai | date | ✔ | tgl_selesai > tgl_mulai; tidak tumpang tindih |
| is_aktif | boolean | ✔ | Hanya satu yang aktif |

#### c. Siswa: `students`

| Kelompok | Field | Tipe | Wajib | Keterangan / Validasi |
| --- | --- | --- | --- | --- |
| Identitas | nis | varchar(20) | ✔ | Unik |
|  | nisn | char(10) | – | Unik, 10 digit |
|  | nik | char(16) | – | Unik, 16 digit |
|  | no_kk | char(16) | – | 16 digit |
|  | no_akta_lahir | varchar(40) | – |  |
|  | nama_lengkap | varchar(150) | ✔ |  |
|  | nama_panggilan | varchar(50) | – |  |
|  | jenis_kelamin | enum | ✔ | L / P |
|  | tempat_lahir | varchar(100) | ✔ |  |
|  | tgl_lahir | date | ✔ | Tidak di masa depan; usia wajar 5–15 th (peringatan) |
|  | agama | FK → ref_religions | ✔ |  |
|  | kewarganegaraan | enum | – | WNI / WNA (default WNI) |
|  | anak_ke, jumlah_saudara | tinyint | – | anak_ke ≤ jumlah_saudara + 1 |
|  | bahasa_sehari_hari | varchar(50) | – |  |
| Alamat | alamat_jalan | varchar(255) | ✔ |  |
|  | rt, rw | varchar(5) | – |  |
|  | desa_kelurahan, kecamatan, kab_kota, provinsi | varchar(100) | ✔ (kab_kota, provinsi) |  |
|  | kode_pos | char(5) | – |  |
|  | latitude, longitude | decimal | – |  |
|  | tinggal_bersama | enum | – | Orang tua / Wali / Asrama / Lainnya |
|  | transportasi | FK → ref_transport | – | Jalan kaki, motor, antar-jemput, dll. |
|  | jarak_ke_sekolah_km | decimal(4,1) | – |  |
|  | waktu_tempuh_menit | smallint | – |  |
| Kontak | no_hp_siswa, email | varchar | – | Format valid |
| Fisik & kesehatan | tinggi_cm, berat_kg | decimal | – | Rentang wajar |
|  | golongan_darah | enum | – | A/B/AB/O/Tidak tahu |
|  | riwayat_penyakit, alergi | text | – | Hanya terlihat oleh Admin & wali kelas |
|  | kebutuhan_khusus | text | – | Hanya terlihat oleh Admin & wali kelas |
| Asal & masuk | asal_sekolah_tk | varchar(150) | – | TK/PAUD/sekolah asal |
|  | jenis_pendaftaran | enum | – | Siswa baru / Pindahan |
|  | tgl_masuk | date | ✔ |  |
|  | tahun_masuk | year | ✔ |  |
|  | kelas_saat_masuk | tinyint | – |  |
| Akademik | class_id | FK → classes | – | Kelas aktif saat ini (riwayat di `student_enrollments`) |
|  | status | enum | ✔ | Aktif / Pindah / Lulus / Keluar / Nonaktif |
| Lainnya | foto_path | varchar | – | JPG/PNG ≤ 2 MB |
|  | catatan | text | – |  |
|  | import_batch_id | FK | – | Diisi bila berasal dari import |

#### d. Orang Tua / Wali: `guardians` dan `student_guardian`

`guardians`

| Field | Tipe | Wajib | Keterangan / Validasi |
| --- | --- | --- | --- |
| user_id | FK → users | – | Terisi bila punya akun portal |
| hubungan | enum | ✔ | Ayah / Ibu / Wali |
| nama_lengkap | varchar(150) | ✔ |  |
| nik | char(16) | – | 16 digit |
| tempat_lahir, tgl_lahir | varchar / date | – |  |
| status_hidup | enum | – | Hidup / Meninggal |
| agama | FK → ref_religions | – |  |
| pendidikan_terakhir | FK → ref_education | – |  |
| pekerjaan | FK → ref_occupations | – |  |
| penghasilan_bulanan | FK → ref_income_ranges | – | Berupa rentang, bukan angka pasti |
| no_hp | varchar(16) | ✔ (minimal satu wali) | Format 08xx / +62; unik per akun |
| no_whatsapp | varchar(16) | – | Dipakai untuk notifikasi |
| email | varchar(150) | – |  |
| alamat_jalan, rt, rw, desa_kelurahan, kecamatan, kab_kota, provinsi, kode_pos | varchar | – | Salin dari alamat siswa bila sama |
| foto_path | varchar | – |  |

`student_guardian` (relasi siswa ↔ wali)

| Field | Tipe | Wajib | Keterangan |
| --- | --- | --- | --- |
| student_id, guardian_id | FK | ✔ | Kombinasi unik |
| hubungan | enum | ✔ | Ayah / Ibu / Wali |
| is_kontak_utama | boolean | ✔ | Tepat satu kontak utama per siswa |
| boleh_lihat_keuangan | boolean | – | Default true untuk kontak utama |
| boleh_jemput | boolean | – | Opsional untuk keamanan penjemputan |

#### e. Riwayat Kelas & Mutasi Siswa: `student_enrollments`, `student_status_history`

`student_enrollments`: student_id ✔, class_id ✔, academic_year_id ✔, tgl_masuk_kelas ✔, tgl_keluar_kelas –, status_kelas (Aktif / Naik / Tinggal kelas / Pindah) ✔, nomor_absen –.

`student_status_history`: student_id ✔, jenis (Masuk / Naik kelas / Pindah keluar / Lulus / Keluar / Aktif kembali) ✔, tanggal ✔, sekolah_asal_tujuan –, alasan –, no_surat –, dokumen_path –, dicatat_oleh ✔.

#### f. Guru / Tenaga Pendidik: `teachers`

| Kelompok | Field | Tipe | Wajib | Keterangan / Validasi |
| --- | --- | --- | --- | --- |
| Identitas | user_id | FK → users | – |  |
|  | nip | varchar(18) | – | Unik bila diisi |
|  | nuptk | char(16) | – | Unik, 16 digit |
|  | nik | char(16) | ✔ | Unik, 16 digit |
|  | nama_lengkap | varchar(150) | ✔ |  |
|  | gelar_depan, gelar_belakang | varchar(30) | – |  |
|  | jenis_kelamin | enum | ✔ | L / P |
|  | tempat_lahir, tgl_lahir | varchar / date | ✔ | Usia ≥ 18 th |
|  | agama | FK → ref_religions | – |  |
|  | status_pernikahan | enum | – |  |
| Alamat & kontak | alamat_jalan, rt, rw, desa_kelurahan, kecamatan, kab_kota, provinsi, kode_pos | varchar | ✔ (jalan, kab_kota) |  |
|  | no_hp | varchar(16) | ✔ |  |
|  | email | varchar(150) | – |  |
|  | kontak_darurat_nama, kontak_darurat_hp, kontak_darurat_hubungan | varchar | – |  |
| Kepegawaian | jenis_ptk | enum | ✔ | Guru kelas / Guru mapel / Guru agama / Guru olahraga / Kepala sekolah |
|  | status_kepegawaian | enum | ✔ | PNS / PPPK / GTY (tetap yayasan) / GTT (tidak tetap) / Honorer |
|  | tgl_mulai_tugas | date | ✔ |  |
|  | tgl_akhir_tugas | date | – |  |
|  | jabatan | varchar(100) | – |  |
|  | tugas_tambahan | varchar(150) | – | Wali kelas, pembina ekskul, dll. |
|  | jam_mengajar_per_minggu | smallint | – |  |
|  | status | enum | ✔ | Aktif / Cuti / Nonaktif / Pensiun |
| Pendidikan | pendidikan_terakhir | FK → ref_education | ✔ |  |
|  | jurusan_prodi | varchar(100) | – |  |
|  | institusi | varchar(150) | – |  |
|  | tahun_lulus | year | – |  |
| Sertifikasi | status_sertifikasi | enum | – | Sudah / Belum |
|  | no_sertifikat_pendidik, tahun_sertifikasi | varchar / year | – |  |
| Administratif | npwp | varchar(20) | – | Akses terbatas Admin |
|  | nama_bank, no_rekening, atas_nama | varchar | – | Untuk honor; akses terbatas Staf Administrasi |
| Lainnya | foto_path | varchar | – |  |
|  | tanda_tangan_path | varchar | – | Untuk dokumen resmi (opsional) |
|  | import_batch_id | FK | – |  |

`teacher_documents`: teacher_id ✔, jenis (Ijazah / SK / Sertifikat / KTP / Lainnya) ✔, nama_dokumen ✔, file_path ✔, tgl_terbit –, tgl_kedaluwarsa –.

#### g. Staf Administrasi / Tenaga Kependidikan: `staff`

| Field | Tipe | Wajib | Keterangan |
| --- | --- | --- | --- |
| user_id | FK → users | – |  |
| nik | char(16) | ✔ | Unik |
| nama_lengkap | varchar(150) | ✔ |  |
| jenis_kelamin, tempat_lahir, tgl_lahir, agama | enum / varchar / date / FK | ✔ (kecuali agama) |  |
| alamat lengkap, no_hp, email | varchar | ✔ (alamat jalan, no_hp) |  |
| jabatan | varchar(100) | ✔ | Operator, Bendahara, Tata usaha, Petugas sarpras, dll. |
| status_kepegawaian, tgl_mulai_tugas, status | enum / date | ✔ |  |
| pendidikan_terakhir | FK | – |  |
| foto_path | varchar | – |  |

#### h. Kelas / Rombel: `classes`

| Field | Tipe | Wajib | Keterangan / Validasi |
| --- | --- | --- | --- |
| academic_year_id | FK | ✔ |  |
| kode | varchar(10) | ✔ | Contoh: 3A; unik per tahun ajaran |
| nama | varchar(50) | ✔ | Contoh: Kelas 3 A |
| tingkat | tinyint | ✔ | 1–6 |
| paralel | varchar(5) | – | A, B, C |
| wali_kelas_id | FK → teachers | – | Satu guru maksimal satu kelas per tahun ajaran |
| room_id | FK → rooms | – | Ruang utama |
| kapasitas | smallint | – | Jumlah siswa tidak boleh melebihi kapasitas (peringatan) |
| status | enum | ✔ | Aktif / Nonaktif |

#### i. Mata Pelajaran: `subjects`

| Field | Tipe | Wajib | Keterangan |
| --- | --- | --- | --- |
| kode | varchar(15) | ✔ | Unik |
| nama | varchar(100) | ✔ |  |
| kelompok | enum | ✔ | Umum / Agama / Muatan lokal / Ekstrakurikuler |
| tingkat_berlaku | varchar(20) | – | Contoh: 1–6 |
| jam_per_minggu | tinyint | – |  |
| kkm | tinyint | – | 0–100 |
| status | enum | ✔ | Aktif / Nonaktif |

#### j. Ruang: `rooms`

| Field | Tipe | Wajib | Keterangan |
| --- | --- | --- | --- |
| kode | varchar(15) | ✔ | Unik |
| nama | varchar(100) | ✔ |  |
| jenis | enum | ✔ | Ruang kelas / Perpustakaan / Lab / Aula / Mushola / Lainnya |
| gedung_lantai | varchar(50) | – |  |
| kapasitas | smallint | – |  |
| kondisi | enum | – | Baik / Rusak ringan / Rusak berat |
| asset_id | FK → assets | – | Terhubung ke modul sarpras |

#### k. Jam Pelajaran & Jadwal: `time_slots`, `schedules`, `teacher_assignments`

`time_slots`: nama (Jam 1, Istirahat) ✔, jenis (Belajar / Istirahat) ✔, jam_mulai ✔, jam_selesai ✔ (> jam_mulai), urutan ✔, hari_berlaku (Senin–Sabtu) ✔.

`schedules`: academic_year_id ✔, class_id ✔, subject_id ✔, teacher_id ✔, room_id –, hari ✔, time_slot_id ✔, status ✔. **Validasi:** guru, kelas, dan ruang tidak boleh bentrok pada hari dan jam yang sama.

`teacher_assignments` (pengampu): teacher_id ✔, subject_id ✔, class_id ✔, academic_year_id ✔, jam_per_minggu –.

#### l. Tabel Referensi (Lookup)

| Tabel | Isi contoh |
| --- | --- |
| `ref_religions` | Islam, Kristen, Katolik, Hindu, Buddha, Konghucu, Lainnya |
| `ref_education` | Tidak sekolah, SD, SMP, SMA/SMK, D1–D3, S1, S2, S3 |
| `ref_occupations` | PNS, TNI/Polri, Guru/Dosen, Karyawan swasta, Wiraswasta, Petani, Buruh, Pedagang, Ibu rumah tangga, Lainnya |
| `ref_income_ranges` | \< 1 jt, 1–2 jt, 2–5 jt, 5–10 jt, > 10 jt (rentang bulanan) |
| `ref_transport` | Jalan kaki, Sepeda, Motor, Antar-jemput sekolah, Angkutan umum, Mobil pribadi |
| `ref_regions` (opsional) | Provinsi, kab/kota, kecamatan, desa untuk dropdown berjenjang |

#### m. Aturan Validasi Umum Master Data

- NIK dan No. KK harus 16 digit angka; NISN 10 digit angka; nomor HP diawali `08` atau `+62` (disimpan dalam format +62)
- NIS, NISN, NIK, NUPTK, dan NIP unik di tabelnya masing-masing
- Tanggal lahir tidak boleh di masa depan; tanggal akhir ≥ tanggal mulai
- Data sensitif (NIK, no. KK, penghasilan, rekening, kesehatan) hanya tampil pada role berwenang, dan setiap perubahan dicatat di log aktivitas
- Penghapusan memakai soft delete; data yang masih dipakai (siswa aktif di kelas, guru pada jadwal) tidak boleh dihapus, hanya dinonaktifkan

### 11.2 Entitas Transaksional (Field Kunci)

| Tabel | Field utama |
| --- | --- |
| `users` | id, nama, username, email, no_hp, password_hash, role_id, status, last_login_at |
| `roles` | id, nama (kepala_sekolah, staf_admin, guru, wali) |
| `teacher_attendance` | id, teacher_id, tanggal, jam_masuk, jam_keluar, status, lat, lng, keterangan |
| `student_attendance` | id, student_id, schedule_id/class_id, tanggal, status, dicatat_oleh |
| `teaching_evidence` | id, schedule_id, teacher_id, tanggal, photo_path, captured_at |
| `daily_activities` | id, class_id, teacher_id, tanggal, judul, deskripsi, photo_path |
| `learning_progress` | id, student_id, teacher_id, subject_id, tanggal, capaian, catatan, nilai |
| `fee_types` | id, nama, nominal, periode |
| `invoices` | id, student_id, fee_type_id, periode, jumlah, status, jatuh_tempo |
| `payments` | id, invoice_id, tanggal, jumlah, metode, bukti_path, dicatat_oleh |
| `finance_transactions` | id, tipe (masuk/keluar), kategori, jumlah, tanggal, keterangan |
| `assets` | id, kode, nama, kategori, lokasi, tahun, nilai, kondisi, foto_path |
| `asset_maintenance` | id, asset_id, tanggal, deskripsi, biaya |
| `documents` | id, judul, kategori, file_path, diunggah_oleh |
| `announcements` | id, judul, isi, target_role, tgl_terbit |
| `import_batches` | id, user_id, jenis_data, nama_file, total, berhasil, gagal, status, created_at |
| `import_errors` | id, batch_id, nomor_baris, kolom, pesan |
| `column_mappings` | id, jenis_data, kolom_excel, field_sistem |
| `activity_logs` | id, user_id, aksi, modul, target_id, data_sebelum, data_sesudah, ip, user_agent, created_at |
| `backups` | id, waktu, tipe (otomatis/manual), ukuran, lokasi, status |
| `notifications` | id, user_id, judul, isi, dibaca, created_at |
| `settings` | key, value |

### 11.3 Relasi Penting

- `users` 1—1 `teachers` / `staff` / `guardians`
- `students` N—N `guardians` melalui `student_guardian`
- `students` 1—N `student_enrollments` N—1 `classes` (riwayat kelas per tahun ajaran)
- `classes` N—1 `academic_years`, N—1 `teachers` (wali kelas), N—1 `rooms`
- `schedules` menghubungkan `classes`, `subjects`, `teachers`, `rooms`, `time_slots`
- `schedules` 1—N `student_attendance` dan 1—N `teaching_evidence`
- `students` 1—N `invoices`, `invoices` 1—N `payments`
- Seluruh tabel penting memiliki `created_at`, `updated_at`, `deleted_at` (soft delete)

### 11.4 Aturan Integritas

- Satu siswa hanya boleh punya satu enrollment aktif per tahun ajaran
- Satu siswa tidak boleh punya dua absensi pada jadwal dan tanggal yang sama
- Pembayaran tidak boleh melebihi sisa tagihan
- Wali hanya terhubung ke siswa lewat `student_guardian`; tepat satu kontak utama per siswa
- Jadwal tidak boleh bentrok untuk guru, kelas, maupun ruang

### 11.5 Kolom Template Import (Mengikuti Field Wajib)

| Template | Kolom (urutan di file Excel) |
| --- | --- |
| **Siswa** | nis, nisn, nik, no_kk, nama_lengkap, jenis_kelamin (L/P), tempat_lahir, tgl_lahir (YYYY-MM-DD), agama, anak_ke, alamat_jalan, rt, rw, desa_kelurahan, kecamatan, kab_kota, provinsi, kode_pos, tinggal_bersama, transportasi, asal_sekolah_tk, tgl_masuk, kode_kelas, status, nama_ayah, nik_ayah, pekerjaan_ayah, nama_ibu, nik_ibu, pekerjaan_ibu, no_hp_wali, no_whatsapp_wali |
| **Guru** | nip, nuptk, nik, nama_lengkap, gelar_depan, gelar_belakang, jenis_kelamin, tempat_lahir, tgl_lahir, agama, alamat_jalan, kab_kota, provinsi, no_hp, email, jenis_ptk, status_kepegawaian, tgl_mulai_tugas, jabatan, tugas_tambahan, pendidikan_terakhir, jurusan_prodi, institusi, tahun_lulus, status_sertifikasi |
| **Kelas** | kode, nama, tingkat, paralel, tahun_ajaran, nip_wali_kelas, kode_ruang, kapasitas |
| **Mata pelajaran** | kode, nama, kelompok, tingkat_berlaku, jam_per_minggu, kkm |
| **Wali murid** | nis_siswa, hubungan, nama_lengkap, nik, pekerjaan, pendidikan_terakhir, no_hp, no_whatsapp, email, is_kontak_utama |

Catatan: data referensi (agama, pekerjaan, dll.) dicocokkan berdasarkan nama; nilai yang tidak dikenali ditandai error di tahap preview. Siswa dan wali dihubungkan lewat `nis_siswa`.

## 12. Spesifikasi API (Ringkas)

Base URL: `/api/v1` · Format JSON · Auth: `Authorization: Bearer <JWT>`

| Metode | Endpoint | Deskripsi | Role |
| --- | --- | --- | --- |
| POST | `/auth/login` | Login, menghasilkan token | Semua |
| POST | `/auth/logout` | Logout | Semua |
| GET/PUT | `/me` | Lihat/ubah profil | Semua |
| PUT | `/me/password` | Ganti password | Semua |
| GET/POST | `/users` | Daftar/buat user | Admin |
| PUT/DELETE | `/users/{id}` | Ubah/nonaktifkan user | Admin |
| GET/POST/PUT/DELETE | `/students`, `/teachers`, `/classes` | CRUD data master | Admin (R: Guru terbatas) |
| POST | `/imports/{jenis}/validate` | Upload & validasi file import | Staf Admin |
| POST | `/imports/{batch}/commit` | Simpan hasil import | Staf Admin |
| POST | `/imports/{batch}/rollback` | Batalkan import | Staf Admin |
| GET | `/imports/templates/{jenis}` | Unduh template | Staf Admin |
| POST | `/attendance/teachers/check-in` | Absen masuk guru | Guru |
| POST | `/attendance/teachers/check-out` | Absen pulang guru | Guru |
| GET | `/attendance/teachers` | Rekap absensi guru | Admin |
| POST | `/attendance/students` | Kirim absensi murid | Guru |
| POST | `/attendance/evidence` | Upload foto bukti mengajar (multipart) | Guru |
| GET | `/attendance/students` | Rekap absensi murid | Admin, Guru (terbatas) |
| POST/GET | `/progress`, `/activities` | Input/lihat progres & kegiatan | Guru (C/R), Admin (R) |
| GET | `/parent/children` | Daftar anak milik wali | Wali |
| GET | `/parent/children/{id}/summary` | Ringkasan kehadiran, kegiatan, progres | Wali |
| GET/POST | `/invoices`, `/payments` | Tagihan & pembayaran | Staf Admin |
| GET | `/reports/finance` | Laporan keuangan | Admin |
| GET/POST/PUT | `/assets` | Inventaris sarpras | Staf Admin |
| GET/POST | `/backups` | Riwayat/jalankan backup | Staf Admin |
| POST | `/backups/{id}/restore` | Restore | Staf Admin |
| GET | `/activity-logs` | Daftar log | Admin |

Standar respons: `200/201` sukses, `400/422` validasi, `401` tidak terautentikasi, `403` tidak berhak, `404` tidak ditemukan, `429` terlalu banyak permintaan, `500` kesalahan server. Format error: `{ "message": "...", "errors": { "field": ["..."] } }`.

## 13. Alur Proses Utama

### 13.1 Absensi Murid + Bukti Mengajar

1. Guru login → membuka **Absensi Murid** → memilih kelas/sesi hari ini
2. Mengambil/mengunggah foto bukti mengajar
3. Menandai status tiap siswa (default: hadir)
4. Kirim → server memvalidasi (jadwal, hak akses, duplikasi, foto)
5. Data tersimpan, log tercatat, notifikasi opsional dikirim ke wali untuk siswa yang tidak hadir

### 13.2 Import Excel

`Upload → Parsing → (Pemetaan kolom) → Validasi → Preview → Konfirmasi → Simpan (batch) → Laporan hasil`

### 13.3 Pembayaran

`Buat tagihan → Wali/sekolah menerima pembayaran → Staf catat pembayaran → Status tagihan diperbarui → Kuitansi PDF → Masuk laporan keuangan`

### 13.4 Backup

`Jadwal harian → Dump database + arsip file → Enkripsi → Simpan lokal + off-site → Catat di tabel backups → Hapus yang melewati retensi`

## 14. Spesifikasi Antarmuka (UI/UX)

### 14.1 Daftar Halaman per Role

**Admin**

- Login · Dashboard · Data Guru · Data Siswa · Kelas & Tahun Ajaran · Import Data
- Rekap Absensi (Guru/Murid) · Bukti Mengajar · Administrasi (Pengumuman, Arsip, Jadwal)
- Keuangan (Tagihan, Pembayaran, Kas, Laporan) · Sarpras · Manajemen User
- Backup Data · Log Aktivitas · Profil & Setting

**Guru**

- Beranda (jadwal hari ini) · Absensi Guru · Absensi Murid + Upload Foto
- Kegiatan Harian & Progres Siswa · Riwayat · Profil · Setting

**Wali Murid**

- Beranda (ringkasan anak) · Kehadiran · Kegiatan Harian · Progres Belajar
- Pengumuman · Profil · Setting

### 14.2 Prinsip Desain

- **Mobile-first** untuk Guru dan Wali; layout dashboard lebar untuk Admin
- Navigasi bawah (bottom tab) di HP; sidebar di desktop
- Warna konsisten dengan identitas sekolah; status absensi memakai warna + ikon (tidak mengandalkan warna saja)
- Setiap formulir memiliki validasi langsung dan pesan error berbahasa Indonesia
- Konfirmasi untuk aksi berisiko (hapus, restore, koreksi)

## 15. Keamanan & Privasi

| Area | Kontrol |
| --- | --- |
| Autentikasi | Hash argon2/bcrypt, kebijakan password minimal 8 karakter, lockout, token berumur pendek + refresh |
| Otorisasi | RBAC di backend, policy per resource, pemeriksaan kepemilikan data (wali–anak, guru–kelas) |
| Transport | HTTPS wajib, HSTS |
| Aplikasi | Validasi input, parameterized query/ORM, proteksi CSRF/XSS, header keamanan, rate limiting |
| File | Validasi MIME & ekstensi, nama file diacak, disimpan di luar web root, akses lewat URL bertanda tangan/sementara |
| Data | Enkripsi backup; data sensitif (NIK/kontak) hanya ditampilkan sesuai hak |
| Audit | Log tidak dapat dihapus dari UI; retensi minimal 1 tahun |
| Operasional | Update OS/dependensi berkala, firewall, akses SSH berbasis key, pemisahan kredensial per lingkungan |
| Privasi anak | Wali hanya melihat anaknya; foto anak hanya untuk pengguna berwenang; kebijakan privasi dan persetujuan wali |

## 16. Rencana Rilis (Roadmap)

Estimasi di bawah mengasumsikan tim kecil (1–3 pengembang) dan perlu divalidasi.

| Fase | Cakupan | Estimasi |
| --- | --- | --- |
| **0. Persiapan** | Pengumpulan kebutuhan, analisis file Excel sekolah, pembersihan data, desain UI | 2–3 minggu |
| **1. Fondasi (MVP)** | Auth, RBAC, manajemen user, data guru/siswa/kelas, **import Excel**, absensi guru & murid + foto, log aktivitas dasar | 6–8 minggu |
| **2. Portal & Progres** | Kegiatan harian, progres belajar, portal wali, pengumuman, notifikasi in-app | 4–5 minggu |
| **3. Keuangan & Sarpras** | Tagihan, pembayaran, kas, laporan, inventaris, administrasi/arsip | 5–6 minggu |
| **4. Penyempurnaan** | Dashboard Kepala Sekolah, backup otomatis & restore, log lengkap, WhatsApp, optimasi | 3–4 minggu |
| **5. UAT & Go-live** | Uji penerimaan, pelatihan, migrasi final, masa pendampingan | 3–4 minggu |

**Strategi go-live:** paralel (Excel dan sistem berjalan bersamaan) selama 1 bulan sebelum Excel dihentikan.

## 17. Pengujian

| Jenis | Cakupan |
| --- | --- |
| Unit test | Logika validasi import, perhitungan tagihan/saldo, aturan absensi |
| Integration test | API per modul, otorisasi per role |
| Uji keamanan | Akses lintas role/lintas anak, upload file berbahaya, brute force, SQL injection |
| Uji kinerja | Rekap bulanan, import ribuan baris, upload foto bersamaan |
| UAT | Skenario nyata bersama Kepala Sekolah, staf, 3–5 guru, 5–10 wali murid |
| Uji backup | Restore ke staging secara berkala |

**Kriteria go-live:** seluruh fitur *Must* lulus UAT, tidak ada bug kritis/tinggi terbuka, restore backup teruji, pelatihan selesai.

## 18. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Data Excel tidak konsisten/kotor | Import gagal, data salah | Template standar, validasi + preview, pembersihan awal, column mapping |
| Resistensi pengguna (kebiasaan Excel) | Adopsi rendah | Pelatihan, mode paralel, UI sederhana, pendamping |
| Koneksi internet sekolah/guru tidak stabil | Absensi terhambat | Kompres foto, retry upload, pertimbangkan mode offline sederhana (antrean) |
| Kebocoran data anak | Risiko hukum & reputasi | RBAC ketat, HTTPS, enkripsi backup, log audit, kebijakan privasi |
| Kehilangan data | Operasional terhenti | Backup harian + off-site, uji restore |
| Ketergantungan pada satu pengembang | Sulit pemeliharaan | Dokumentasi, Git, serah terima teknis |
| Biaya hosting/layanan WA | Anggaran | Mulai dari VPS ringan, WA sebagai fitur opsional |
| Penyalahgunaan absen (titip absen) | Data tidak valid | Foto bukti + cap waktu, geofence opsional, audit oleh Kepala Sekolah |

## 19. Asumsi, Dependensi & Pertanyaan Terbuka

**Asumsi**

- Guru dan wali memiliki smartphone dengan akses internet
- Sekolah menyediakan staf yang ditugaskan sebagai operator sistem
- Aplikasi di-host di VPS dan domain milik/disewa sekolah

**Dependensi**

- Ketersediaan file Excel lama beserta contoh kolomnya
- Domain, hosting, dan sertifikat SSL
- Penyedia WhatsApp gateway (jika fitur diaktifkan)

**Pertanyaan terbuka untuk sekolah**

1. Jumlah guru, siswa, dan kelas saat ini (untuk menentukan kapasitas)?
2. Apakah wali murid boleh melihat tagihan/keuangan anak?
3. Apakah foto bukti mengajar wajib untuk setiap sesi atau satu foto per hari?
4. Apakah absensi guru perlu validasi lokasi (geofence)?
5. Bentuk penilaian progres: angka, predikat (A/B/C), atau narasi?
6. Apakah ada format laporan/kuitansi/kop surat resmi yang harus diikuti?
7. Apakah ada kebutuhan integrasi dengan Dapodik atau e-rapor di masa depan?
8. Siapa pemegang kendali akhir (data owner) dan penanggung jawab backup?

## 20. Glosarium

| Istilah | Arti |
| --- | --- |
| PRD | Product Requirements Document |
| RBAC | Role-Based Access Control, hak akses berdasarkan peran |
| PWA | Progressive Web App, aplikasi web yang dapat dipasang seperti aplikasi HP |
| JWT | JSON Web Token, token autentikasi |
| MoSCoW | Metode prioritas: Must, Should, Could, Won't |
| UAT | User Acceptance Testing |
| RPO / RTO | Batas kehilangan data / batas waktu pemulihan |
| Geofence | Batas area lokasi virtual |
| NIS / NISN | Nomor Induk Siswa / Nomor Induk Siswa Nasional |
| NUPTK | Nomor Unik Pendidik dan Tenaga Kependidikan |
| UU PDP | Undang-Undang Pelindungan Data Pribadi |