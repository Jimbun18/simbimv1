# 📘 BUKU PANDUAN PENGGUNA RESMI (USER MANUAL)
## SIM-BIM Purwokerto — SDI Bina Insan Mandiri
**Sistem Informasi Manajemen Terpadu Sekolah Berbasis Web & Mobile Android**

---

## 📑 DAFTAR ISI
1. [Pendahuluan & Gambaran Umum Sistem](#1-pendahuluan--gambaran-umum-sistem)
2. [Akses Sistem: Web Portal & Aplikasi Mobile Android](#2-akses-sistem-web-portal--aplikasi-mobile-android)
3. [Panduan Masuk (Login) & Pengamanan Akun](#3-panduan-masuk-login--pengamanan-akun)
   - [3.1 Login Standar (Web & HP)](#31-login-standar-web--hp)
   - [3.2 Login Cepat dengan Sidik Jari (Biometrik Mobile)](#32-login-cepat-dengan-sidik-jari-biometrik-mobile)
   - [3.3 Ganti Kata Sandi & Pengaturan Profil](#33-ganti-kata-sandi--pengaturan-profil)
4. [Panduan Administrator & Staf Tata Usaha](#4-panduan-administrator--staf-tata-usaha)
   - [4.1 Manajemen Tahun Ajaran & Semester](#41-manajemen-tahun-ajaran--semester)
   - [4.2 Manajemen Rombongan Belajar (Rombel/Kelas)](#42-manajemen-rombongan-belajar-rombelkelas)
   - [4.3 Manajemen Jadwal Pelajaran](#43-manajemen-jadwal-pelajaran)
   - [4.4 Data Guru & Tenaga Kependidikan (GTK)](#44-data-guru--tenaga-kependidikan-gtk)
   - [4.5 Data Siswa & Import Excel](#45-data-siswa--import-excel)
   - [4.6 Pengelolaan Keuangan & Tagihan SPP (12 Bulan)](#46-pengelolaan-keuangan--tagihan-spp-12-bulan)
   - [4.7 Manajemen PPDB / SPMB Online](#47-manajemen-ppdb--spmb-online)
   - [4.8 Manajemen Pengumuman & CMS Web Publik](#48-manajemen-pengumuman--cms-web-publik)
   - [4.9 Backup Database & Log Audit Aktivitas](#49-backup-database--log-audit-aktivitas)
5. [Panduan Kepala Sekolah](#5-panduan-kepala-sekolah)
   - [5.1 Dashboard Eksekutif & Statistik](#51-dashboard-eksekutif--statistik)
   - [5.2 Pengawasan Presensi Guru & Siswa](#52-pengawasan-presensi-guru--siswa)
   - [5.3 Rekapitulasi Keuangan & Kas Masuk](#53-rekapitulasi-keuangan--kas-masuk)
   - [5.4 Supervisi Pembelajaran & Observasi Siswa](#54-supervisi-pembelajaran--observasi-siswa)
6. [Panduan Guru Mata Pelajaran & Wali Kelas](#6-panduan-guru-mata-pelajaran--wali-kelas)
   - [6.1 Presensi Harian Guru (Validasi Radius GPS & Foto Selfie)](#61-presensi-harian-guru-validasi-radius-gps--foto-selfie)
   - [6.2 Presensi Harian Siswa (Khusus Wali Kelas)](#62-presensi-harian-siswa-khusus-wali-kelas)
   - [6.3 Buku Nilai & Input Capaian Pembelajaran (Kurikulum Merdeka)](#63-buku-nilai--input-capaian-pembelajaran-kurikulum-merdeka)
   - [6.4 Pengisian Nilai Ekstrakurikuler & Karakter P5](#64-pengisian-nilai-ekstrakurikuler--karakter-p5)
   - [6.5 Pencetakan Rapor Siswa Digital (PDF)](#65-pencetakan-rapor-siswa-digital-pdf)
   - [6.6 Buku Catatan Observasi & Layanan BK](#66-buku-catatan-observasi--layanan-bk)
7. [Panduan Wali Murid (Portal Orang Tua)](#7-panduan-wali-murid-portal-orang-tua)
   - [7.1 Memantau Kehadiran Anak Secara Real-Time](#71-memantau-kehadiran-anak-secara-real-time)
   - [7.2 Melihat Rekap Nilai & Unduh Rapor](#72-melihat-rekap-nilai--unduh-rapor)
   - [7.3 Status Tagihan SPP & Konfirmasi Pembayaran](#73-status-tagihan-spp--konfirmasi-pembayaran)
   - [7.4 Informasi Agenda & Pengumuman Sekolah](#74-informasi-agenda--pengumuman-sekolah)
8. [Tanya Jawab & Penyelesaian Masalah (Troubleshooting / FAQ)](#8-tanya-jawab--penyelesaian-masalah-troubleshooting--faq)

---

## 1. Pendahuluan & Gambaran Umum Sistem

**SIM-BIM Purwokerto** adalah platform ekosistem digital terpadu milik **SDI Bina Insan Mandiri Purwokerto**. Sistem ini dirancang untuk mengintegrasikan seluruh operasional madrasah/sekolah, mulai dari pendataan akademik, presensi kehadiran berbasis geolokasi, buku nilai Kurikulum Merdeka, keuangan SPP 12 bulan, hingga keterlibatan wali murid secara *real-time*.

### Hak Akses (User Role)
Sistem memiliki 4 (empat) kategori peran utama:
1. **Administrator / Staf Tata Usaha**: Pengendali penuh data master, konfigurasi sistem, keuangan, dan pembuatan akun.
2. **Kepala Sekolah**: Akses monitoring seluruh modul (presensi, nilai, keuangan, observasi) dan laporan manajerial.
3. **Guru / Tenaga Pendidik**: Pelaksana presensi mandiri, pengampu jadwal, wali kelas, penginput nilai rapor, dan pencatat observasi perilaku/BK.
4. **Wali Murid**: Pemantau perkembangan anak (kehadiran, prestasi, nilai rapor, dan histori tagihan keuangan).

---

## 2. Akses Sistem: Web Portal & Aplikasi Mobile Android

SIM-BIM dapat diakses melalui 2 (dua) pintu utama:

```
                             ┌───────────────────────────────┐
                             │       SIM-BIM PURWOKERTO      │
                             └──────────────┬────────────────┘
                                            │
                    ┌───────────────────────┴───────────────────────┐
                    ▼                                               ▼
         [ Web Portal Browser ]                           [ Aplikasi Mobile APK ]
  Komputer/Laptop/Tablet Guru & TU                  Smartphone Android Guru & Wali Murid
    https://sdislambims.my.id                          (Icon SDI Bina Insan Mandiri)
```

1. **Akses Web Portal**:
   - Buka browser (Google Chrome, Microsoft Edge, Mozilla Firefox, atau Safari).
   - Masukkan alamat resmi: **`https://sdislambims.my.id`**
   - Halaman depan menyajikan profil sekolah, galeri kegiatan, pendaftaran SPMB online, dan tombol **"Masuk ke Portal"**.
2. **Akses Aplikasi Mobile Android**:
   - Pasang file installer **`app-debug.apk`** resmi dari sekolah.
   - Saat dibuka pertama kali, aplikasi langsung mengarah ke halaman login (*auto-routing khusus mobile*).
   - Mendukung fitur eksklusif ponsel: **Sensor Sidik Jari (Biometrik)**, kamera presensi langsung, dan pelacakan GPS akurat.

---

## 3. Panduan Masuk (Login) & Pengamanan Akun

### 3.1 Login Standar (Web & HP)
1. Buka halaman Login SIM-BIM.
2. Masukkan identitas akun pada kolom **Username / Email / No. HP**:
   - *Contoh Username*: `admin`, `guru`, `wali`, atau NIP/NIK yang didaftarkan.
3. Masukkan **Kata Sandi (Password)** Anda.
4. Klik tombol hijau **"Masuk ke SIM-BIM"**.
5. Sistem akan otomatis mengarahkan Anda ke Dashboard sesuai dengan hak akses Anda.

### 3.2 Login Cepat dengan Sidik Jari (Biometrik Mobile)
> [!TIP]
> Fitur ini sangat memudahkan Guru dan Wali Murid agar tidak perlu mengetik ulang kata sandi saat membuka aplikasi di HP.

**Cara Mengaktifkan Sidik Jari:**
1. Login pertama kali menggunakan Username dan Kata Sandi seperti biasa di HP.
2. Sistem akan menampilkan dialog: *"Aktifkan Sidik Jari? Apakah Anda ingin mengaktifkan login cepat dengan Sidik Jari?"*.
3. Pilih **"Ya, Aktifkan"**, lalu tempelkan jari Anda pada sensor sidik jari HP untuk verifikasi.
4. Sidik jari Anda kini telah terdaftar aman di HP tersebut.

*(Alternatif: Anda juga bisa mengaktifkannya kapan saja melalui menu **Profil** -> Tab **Keamanan** -> Centang pilihan **Login Biometrik / Sidik Jari**)*.

**Cara Menggunakan Sidik Jari Saat Masuk:**
1. Buka aplikasi SIM-BIM di HP.
2. Di bawah form login, tekan tombol **"Masuk dengan Sidik Jari"**.
3. Tempelkan jari Anda pada sensor sidik jari HP.
4. Aplikasi akan otomatis memvalidasi ke server dan langsung membawa Anda masuk ke halaman utama dalam hitungan detik.

### 3.3 Ganti Kata Sandi & Pengaturan Profil
1. Klik foto/nama profil Anda di pojok kanan atas layar (atau menu navigasi bawah di HP).
2. Pilih menu **Profil Saya**.
3. Di tab **Biodata**, Anda dapat melengkapi informasi kontak dan foto profil resmi.
4. Di tab **Keamanan**, masukkan Kata Sandi Lama, Kata Sandi Baru, dan Konfirmasi Kata Sandi Baru, lalu klik **Simpan Kata Sandi**.

---

## 4. Panduan Administrator & Staf Tata Usaha

Administrator memiliki wewenang mengelola fondasi data sekolah pada awal semester/tahun ajaran.

### 4.1 Manajemen Tahun Ajaran & Semester
- Navigasi: Menu **Tahun Ajaran** di sidebar.
- Admin dapat menambah tahun ajaran baru (contoh: `2026/2027`), memilih semester aktif (`Ganjil` / `Genap`), dan menentukan tanggal mulai/selesai semester.
- Pastikan hanya ada 1 (satu) tahun ajaran yang berstatus **Aktif** sebagai basis transaksi data akademik dan keuangan berjalan.

### 4.2 Manajemen Rombongan Belajar (Rombel/Kelas)
- Navigasi: Menu **Kelas & Rombel**.
- Buat rombel kelas (misal: *Kelas 1-A Abu Bakar*, *Kelas 2-B Umar*, dll).
- Tetapkan **Wali Kelas** untuk masing-masing rombel agar guru yang bersangkutan memiliki wewenang mengisi presensi dan rapor kelas tersebut.

### 4.3 Manajemen Jadwal Pelajaran
- Navigasi: Menu **Jadwal Pelajaran**.
- Tentukan mata pelajaran, hari, jam mulai-selesai, rombel kelas, dan guru pengampu.
- Jadwal ini akan otomatis muncul pada portal guru pengampu dan menjadi rujukan presensi KBM harian.

### 4.4 Data Guru & Tenaga Kependidikan (GTK)
- Navigasi: Menu **Data Guru & Staff**.
- Tambah data guru baru lengkap dengan NIP/NUPTK, status kepegawaian (GTY/PTY/Honorer), tugas pokok, dan kontak WhatsApp.
- Admin dapat mencentang opsi **"Buat Akun Pengguna Otomatis"** agar guru langsung menerima kredensial untuk login.

### 4.5 Data Siswa & Import Excel
- Navigasi: Menu **Data Siswa**.
- **Input Manual**: Klik tombol **"Tambah Siswa"**, isi formulir identitas, NIS, NISN, serta tautkan akun orang tua/wali murid.
- **Import Massal via Excel**:
  1. Klik tombol **"Import Excel"**.
  2. Unduh template format resmi `.xlsx` yang disediakan.
  3. Isi data siswa sesuai format kolom (jangan ubah header kolom).
  4. Upload kembali file Excel; sistem akan memvalidasi data dan menyimpannya secara otomatis.

### 4.6 Pengelolaan Keuangan & Tagihan SPP (12 Bulan)
- Navigasi: Menu **Keuangan Sekolah**.
- Modul keuangan dirancang fleksibel untuk siklus tahun ajaran (Juli hingga Juni tahun berikutnya):
  1. **Tagihan SPP Bulanan**: Tergenerate otomatis 12 bulan untuk setiap siswa terdaftar.
  2. **Pencatatan Pembayaran**: Kasir/TU memilih nama siswa, mencentang bulan yang dibayar, memilih metode (Tunai / Transfer Bank), dan mencetak kuitansi pembayaran resmi berlogo sekolah.
  3. **Laporan & Tunggakan**: Filter siswa yang sudah lunas atau masih memiliki tunggakan untuk rekap laporan bendahara.

### 4.7 Manajemen PPDB / SPMB Online
- Navigasi: Menu **PPDB / SPMB**.
- Mengelola calon siswa baru yang mendaftar melalui formulir web publik.
- Mengubah status pendaftaran: *Menunggu Verifikasi* ➔ *Lolos Berkas* ➔ *Tes/Observasi* ➔ *Diterima* / *Ditolak*.
- Data pendaftar yang diterima dapat dikonversi langsung menjadi data siswa aktif kelas 1 dengan satu klik.

### 4.8 Manajemen Pengumuman & CMS Web Publik
- **Papan Pengumuman**: Publikasikan edaran resmi sekolah. Pengumuman dapat ditargetkan ke seluruh warga sekolah, khusus guru, atau khusus wali murid.
- **CMS Web Profil**: Kelola artikel berita, prestasi siswa, galeri foto kegiatan madrasah, dan struktur pendidik yang tayang di halaman depan `sdislambims.my.id`.

### 4.9 Backup Database & Log Audit Aktivitas
- **Backup Data**: Admin dapat mengunduh salinan cadangan (*snapshot backup*) database MySQL kapan saja melalui menu **Backup & Restore**.
- **Log Audit**: Memantau rekam jejak digital (siapa yang mengubah nilai, siapa yang menghapus data, dan waktu aktivitas) guna menjaga akuntabilitas integritas data sekolah.

---

## 5. Panduan Kepala Sekolah

Kepala Sekolah bertindak sebagai supervisor manajerial madrasah dengan hak akses monitoring menyeluruh tanpa dibebani administrasi teknis.

```
                    ┌─────────────────────────────────────────┐
                    │       DASHBOARD KEPALA SEKOLAH          │
                    └────────────────────┬────────────────────┘
                                         │
     ┌───────────────────┬───────────────┴───────────────┬───────────────────┐
     ▼                   ▼                               ▼                   ▼
[ Statistik Kehadiran ] [ Rekapitulasi Keuangan ] [ Pantau Nilai & Rapor ] [ Supervisi BK ]
 Kehadiran Guru & Siswa   Penerimaan SPP & Saldo     Pencapaian Siswa Per Rombel  Catatan Kasus Siswa
```

1. **Dashboard Eksekutif**:
   - Memantau ringkasan total siswa aktif, jumlah guru yang bertugas hari ini, dan persentase kehadiran sekolah hari ini.
2. **Monitoring Presensi Guru & Karyawan**:
   - Melihat jam kedatangan guru, swafoto saat presensi, titik koordinat GPS masuk, dan guru yang berhalangan izin/sakit.
3. **Pengawasan Keuangan**:
   - Melihat grafik penerimaan kas bulanan dan persentase kepatuhan pembayaran SPP per angkatan kelas.
4. **Supervisi Akademik & Rapor**:
   - Memeriksa ketuntasan pengisian nilai rapor oleh seluruh wali kelas sebelum rapat kenaikan kelas / pembagian rapor.
5. **Observasi Siswa & Konseling**:
   - Memantau kasus khusus atau perkembangan siswa yang dicatat oleh guru kelas dan tim Bimbingan Konseling (BK).

---

## 6. Panduan Guru Mata Pelajaran & Wali Kelas

### 6.1 Presensi Harian Guru (Validasi Radius GPS & Foto Selfie)
> [!IMPORTANT]
> Pastikan GPS (Lokasi) dan Izin Kamera di browser atau HP Anda sudah diaktifkan sebelum menekan tombol presensi.

1. Buka menu **Presensi Saya** di aplikasi HP atau laptop.
2. Sistem akan mendeteksi koordinat Anda terhadap titik lokasi resmi SDI Bina Insan Mandiri.
3. Klik tombol **"Presensi Masuk"** atau **"Presensi Pulang"**.
4. Ambil swafoto (foto selfie) wajah Anda di lingkungan sekolah.
5. Klik **"Kirim Presensi"**. Jam masuk dan foto kehadiran Anda tercatat otomatis di sistem sekolah.

### 6.2 Presensi Harian Siswa (Khusus Wali Kelas)
1. Masuk ke menu **Presensi Siswa**.
2. Pilih rombel kelas yang Anda ampu dan tanggal hari ini.
3. Daftar siswa akan tampil dengan status default **Hadir (H)**.
4. Ubah status bagi siswa yang **Sakit (S)**, **Izin (I)**, atau **Alpa (A)**, serta lampirkan keterangan jika ada.
5. Klik **"Simpan Presensi Kelas"**. Data ini akan langsung terhubung ke aplikasi HP orang tua masing-masing siswa secara *real-time*.

### 6.3 Buku Nilai & Input Capaian Pembelajaran (Kurikulum Merdeka)
1. Buka menu **Buku Nilai & Rapor**.
2. Pilih mata pelajaran dan kelas yang diampu.
3. Masukkan Tujuan Pembelajaran (TP) atau lingkup materi yang telah diajarkan.
4. Masukkan skor penilaian formatif dan sumatif siswa. Sistem akan otomatis menghitung rata-rata serta merumuskan deskripsi capaian kompetensi secara otomatis.

### 6.4 Pengisian Nilai Ekstrakurikuler & Karakter P5 (Wali Kelas)
1. Pada menu Rapor Kelas, buka tab **Ekstrakurikuler**: Masukkan nama ekskul (misal: Pramuka, Tahfidz, Renang, Pencak Silat) beserta predikat dan keterangan keaktifan siswa.
2. Buka tab **Profil Pelajar Pancasila (P5) & Karakter**: Berikan penilaian pada dimensi akhlak mulia, kemandirian, dan gotong royong sesuai capaian ananda.

### 6.5 Pencetakan Rapor Siswa Digital (PDF)
1. Pastikan seluruh nilai mata pelajaran, presensi semester, dan catatan wali kelas sudah lengkap.
2. Klik tombol **"Preview Rapor"** untuk memeriksa pratinjau lembar rapor.
3. Klik **"Cetak / Unduh PDF Rapor"**.
4. Berkas PDF standar resmi siap dicetak untuk dibagikan pada hari pembagian rapor atau dikirimkan langsung ke portal wali murid.

### 6.6 Buku Catatan Observasi & Layanan BK
1. Jika menemukan siswa yang membutuhkan perhatian khusus (akademik, kedisiplinan, sosial, atau prestasi istimewa), buka menu **Catatan Observasi**.
2. Pilih nama siswa, pilih kategori observasi (*Positif / Perlu Bimbingan*), dan tuliskan kronologi atau catatan tindak lanjut.
3. Catatan ini bersifat rahasia dan hanya dapat diakses oleh Guru Bersangkutan, Wali Kelas, Guru BK, dan Kepala Sekolah.

---

## 7. Panduan Wali Murid (Portal Orang Tua)

Portal Wali Murid dirancang sederhana, bersih, dan informatif agar orang tua dapat mengawal pendidikan putra-putrinya dari mana saja.

```
                  ┌────────────────────────────────────────┐
                  │          PORTAL WALI MURID             │
                  └───────────────────┬────────────────────┘
                                      │
          ┌───────────────────────────┼───────────────────────────┐
          ▼                           ▼                           ▼
[ Status Presensi Anak ]    [ Rapor & Nilai Anak ]     [ Info SPP & Keuangan ]
 Kehadiran Hari Ini (H/S/I)   Hasil Belajar & Catatan    Histori Bayar & Sisa Tagihan
```

### 7.1 Memantau Kehadiran Anak Secara Real-Time
- Buka aplikasi SIM-BIM di HP atau kunjungi web portal.
- Pada halaman utama **Portal Wali**, langsung muncul status kehadiran ananda hari ini (misal: *Hadir jam 07.15 WIB*).
- Jika ada lebih dari satu anak bersekolah di SDI BIM, Anda dapat beralih antar-anak (*switch anak*) melalui menu dropdown di bagian atas.

### 7.2 Melihat Rekap Nilai & Unduh Rapor
- Buka tab **Akademik & Rapor**.
- Orang tua dapat melihat rekap capaian nilai harian, nilai tugas, dan mengunduh salinan digital e-Rapor resmi saat akhir semester tanpa perlu menunggu buku fisik.

### 7.3 Status Tagihan SPP & Konfirmasi Pembayaran
- Buka tab **Keuangan & SPP**.
- Menampilkan rincian kewajiban SPP 12 bulan (bulan yang sudah lunas ditandai centang hijau, bulan yang belum dibayar ditandai kuning/merah).
- Menampilkan nomor rekening resmi yayasan/sekolah untuk keperluan transfer serta riwayat kuitansi pembayaran yang sah.

### 7.4 Informasi Agenda & Pengumuman Sekolah
- Buka menu **Pengumuman**.
- Seluruh surat edaran, jadwal libur sekolah, agenda *field trip*, kegiatan *cooking day*, atau ujian sekolah dapat dibaca langsung tanpa takut terlewat di grup WhatsApp.

---

## 8. Tanya Jawab & Penyelesaian Masalah (Troubleshooting / FAQ)

### Q1: Saya lupa kata sandi akun saya, bagaimana solusinya?
> **Jawaban**: Silakan hubungi Administrator atau Staf Tata Usaha sekolah. Admin memiliki wewenang untuk me-reset kata sandi Anda ke kata sandi sementara yang baru. Setelah login, Anda dianjurkan langsung mengganti kata sandi tersebut di menu Profil.

---

### Q2: Mengapa tombol presensi guru menyatakan "Di luar radius sekolah"?
> **Jawaban**:
> 1. Pastikan fitur **Lokasi / GPS** di HP Anda berada pada mode *Akurasi Tinggi (High Accuracy)*.
> 2. Jika Anda berada di dalam gedung beton yang tebal, cobalah keluar ruangan sejenak agar sinyal GPS satelit terkunci optimal.
> 3. Pastikan Anda sudah memberikan izin akses lokasi pada browser/aplikasi (*Allow Location Access*).

---

### Q3: Saat login sidik jari di HP muncul pesan "Gagal terhubung ke server"?
> **Jawaban**:
> 1. Pastikan koneksi internet (WiFi atau kuota data seluler) di HP Anda aktif.
> 2. Jika koneksi seluler sempat drop, cukup tekan kembali tombol *"Masuk dengan Sidik Jari"* atau tekan tombol hijau *"Masuk"* karena data username Anda sudah otomatis terisi.

---

### Q4: Apakah orang tua yang memiliki 2 atau 3 anak di SDI BIM membutuhkan akun terpisah?
> **Jawaban**: Tidak perlu. Satu akun wali murid dapat menampung seluruh putra-putri Anda yang bersekolah di SDI BIM. Anda cukup memilih nama anak yang ingin dipantau di bagian atas Portal Wali.

---

### Q5: Apakah data nilai dan rapor di SIM-BIM aman?
> **Jawaban**: Sangat aman. Sistem dilengkapi enkripsi token modern (Laravel Sanctum), pencadangan database berkala, pemisahan folder aman di server, dan pembatasan hak akses berbasis peran (*Role-Based Access Control*).

---

*(Buku Panduan ini diterbitkan secara resmi untuk keluarga besar SDI Bina Insan Mandiri Purwokerto)*

