# 📋 Laporan Audit Black Box Testing (SIM-BIM)
**Sistem Informasi Manajemen Bimbingan & Akademik Terpadu (SIM-BIM)**  
**SDI Bina Insan Mandiri Purwokerto**  
*Tanggal Pengujian: 30 Oktober 2026*  
*Tipe Audit: Black Box Testing (Functional, Boundary, Security & Authorization)*  
*Status: 100% Lulus (52 / 52 Test Cases Passed)*

---

## 📊 1. Ringkasan Eksekutif (Execution Summary)

Pengujian kotak hitam (*Black Box Testing*) ini mengevaluasi fungsionalitas, alur bisnis (*business process*), penanganan error masukan pengguna (*negative testing*), serta batasan otorisasi peran (*Role-Based Access Control*) pada sistem SIM-BIM tanpa bergantung pada implementasi kode internal.

| Kategori Pengujian | Total Skenario | Lulus (PASS) | Gagal (FAIL) | Tingkat Kelulusan |
| :--- | :---: | :---: | :---: | :---: |
| **A. Autentikasi & Kontrol Akses (RBAC)** | 8 | 8 | 0 | 100% |
| **B. Catatan Observasi Perilaku Siswa** | 8 | 8 | 0 | 100% |
| **C. Pemantauan & Analisis Guru BK** | 6 | 6 | 0 | 100% |
| **D. Manajemen Data Master & Akademik** | 6 | 6 | 0 | 100% |
| **E. Presensi Mandiri Guru & Geofence** | 5 | 5 | 0 | 100% |
| **F. PPDB Online (Portal Calon Wali & Admin)** | 6 | 6 | 0 | 100% |
| **G. Buku Nilai & Rapor Siswa** | 5 | 5 | 0 | 100% |
| **H. Transaksi Keuangan & Kas** | 4 | 4 | 0 | 100% |
| **I. Pencadangan & Pemulihan Sistem (Backup)** | 4 | 4 | 0 | 100% |
| **TOTAL KESELURUHAN** | **52** | **52** | **0** | **100%** |

---

## 🧪 2. Detail Kasus Uji & Hasil Pengujian

### Suite A: Autentikasi, Keamanan Sesi & Otorisasi Peran

| ID Kasus Uji | Skenario Uji | Tindakan / Input | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **TC-AUT-01** | Login Berhasil (Kredensial Valid) | Masukkan username/email valid dan password yang benar. | Berhasil login, menerima token Sanctum, diarahkan ke dashboard sesuai role. | Token tersimpan, redirect ke halaman peran masing-masing. | **PASS** |
| **TC-AUT-02** | Login Gagal (Password Salah) | Masukkan username valid dengan password salah. | Muncul notifikasi kesalahan generik, akun tidak boleh login. | Pesan "*Kredensial tidak cocok*", login ditolak. | **PASS** |
| **TC-AUT-03** | Proteksi Brute-Force (Lockout) | Melakukan percobaan login salah > 5 kali berturut-turut. | Akun dikunci sementara (*locked until*), menampilkan estimasi menit tunggu. | Muncul pesan akun terkunci selama durasi cooldown. | **PASS** |
| **TC-AUT-04** | Proteksi Rute Terproteksi Tanpa Token | Akses URL API private langsung tanpa header Authorization `Bearer`. | Mengembalikan respon HTTP `401 Unauthorized`. | Respon `401` dengan pesan sesi tidak valid. | **PASS** |
| **TC-AUT-05** | Akses Menu Admin oleh Guru Umum | Guru mencoba mengakses `/dashboard` atau `/users-settings`. | Sistem memblokir dan mengarahkan kembali ke menu berhak guru. | Dicegat router guard & middleware API (`403`). | **PASS** |
| **TC-AUT-06** | Logout Pengguna | Klik tombol keluar pada header dashboard. | Token sesi dicabut/dihapus dari server dan local storage dibersihkan. | Token di-revoke, pengguna kembali ke halaman login. | **PASS** |
| **TC-AUT-07** | Ubah Password dengan Konfirmasi Beda | Isi password baru yang tidak sama dengan field konfirmasi password. | Tombol simpan dicegah atau muncul validasi konfirmasi tidak cocok. | Validasi form menolak submit sebelum cocok. | **PASS** |
| **TC-AUT-08** | Ubah Password Minimal Karakter | Isi password baru kurang dari 8 karakter. | Muncul pesan error validasi minimal 8 karakter. | Pesan validasi minimal 8 karakter tampil rapi. | **PASS** |

---

### Suite B: Modul Catatan Observasi Perilaku Siswa

| ID Kasus Uji | Skenario Uji | Tindakan / Input | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **TC-OBS-01** | Tambah Catatan Observasi (Valid) | Guru mengisi Siswa, Tanggal, Jam, Tempat, dan format A-B-C (Faktual). | Data tersimpan, muncul di daftar rekam catatan siswa bersangkutan. | Observasi tersimpan, status awal: "Baru". | **PASS** |
| **TC-OBS-02** | Validasi Field Wajib Observasi | Kirim form dengan mengosongkan siswa atau deskripsi perilaku. | Form menolak pengiriman dan menandai field berwarna merah. | Peringatan field wajib tampil sebelum kirim. | **PASS** |
| **TC-OBS-03** | Isolasi Laporan Antar-Guru | Guru A login dan membuka daftar catatan observasi. | Guru A hanya melihat catatan yang dibuat oleh dirinya sendiri. | Catatan guru lain tidak bocor ke Guru A. | **PASS** |
| **TC-OBS-04** | Hak Akses Guru BK Terhadap Laporan | Guru BK login dan membuka catatan observasi. | Guru BK dapat melihat seluruh catatan masuk dari semua guru pelapor. | Seluruh catatan masuk tampil lengkap bagi Guru BK. | **PASS** |
| **TC-OBS-05** | Indikator Status Dilihat BK | Laporan baru dikirim oleh guru kelas. | Muncul badge "*Belum Dilihat*" bagi Guru BK. | Badge abu-abu "*Belum Dilihat*" muncul. | **PASS** |
| **TC-OBS-06** | Perubahan Status Dilihat Otomatis | Guru BK membuka halaman detail / tanggapan laporan tersebut. | Status otomatis terbarui menjadi "*Sudah Dilihat*" (badge hijau). | Status langsung berubah jadi "*Sudah Dilihat*". | **PASS** |
| **TC-OBS-07** | Rekomendasi Disimpan di Kelas | Guru BK memilih opsi "*Tidak, Cukup Penanganan di Kelas*". | Catatan berstatus selesai/terpantau, tidak masuk ke rekap kasus BK. | Rekomendasi tersimpan, tidak masuk daftar kasus BK. | **PASS** |
| **TC-OBS-08** | Eskalasi ke Kasus Bimbingan Konseling | Guru BK memilih opsi "*Ya, Masukkan ke Pemantauan & Analisis BK*". | Otomatis dibuatkan entitas kasus baru di tabel analisis & monitoring BK. | Terbuat kasus baru dengan log "Informasi Masuk". | **PASS** |

---

### Suite C: Pemantauan & Analisis Kasus Guru BK

| ID Kasus Uji | Skenario Uji | Tindakan / Input | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **TC-MBK-01** | Akses Khusus Menu BK | Akun non-BK (Guru Biasa/Admin) mencoba membuka `/bk-monitoring`. | Akses ditolak dan diarahkan ke halaman izin terkait. | Diredirect otomatis, API merespon `403 Forbidden`. | **PASS** |
| **TC-MBK-02** | Pencarian Kasus Siswa | Mengetik nama siswa atau NIS pada kolom pencarian kasus BK. | Tabel memfilter data siswa yang cocok secara instan. | Hasil pencarian reaktif dan akurat. | **PASS** |
| **TC-MBK-03** | Tambah Jurnal Rekam Kasus | Guru BK menambah tahapan konseling (misal: "Konseling Siswa Ke-1"). | Tahapan bertambah pada timeline riwayat penanganan kasus siswa. | Timeline jurnal terupdate dengan tanggal dan ringkasan. | **PASS** |
| **TC-MBK-04** | Perubahan Status Kasus | Mengubah status kasus dari "Dalam Penanganan" ke "Selesai". | Status kasus berubah, warna badge berganti hijau, masuk filter selesai. | Status tersimpan dan indikator badge berubah. | **PASS** |
| **TC-MBK-05** | Eksport Laporan Konseling | Klik tombol export/cetak rekapitulasi monitoring BK. | Menghasilkan berkas rekapitulasi data kasus siswa. | File unduhan tergenerate dengan data valid. | **PASS** |
| **TC-MBK-06** | Input Kasus Mandiri Guru BK | Guru BK mendaftarkan kasus langsung tanpa melalui catatan guru lain. | Kasus berhasil dibuat langsung ke pemantauan BK. | Form kasus mandiri tersimpan sukses. | **PASS** |

---

### Suite D: Manajemen Data Siswa & Akademik

| ID Kasus Uji | Skenario Uji | Tindakan / Input | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **TC-STD-01** | Filter Siswa Berdasarkan Rombel | Memilih rombel "Kelas 1 Ibnu Sina" pada dropdown. | Daftar hanya menampilkan siswa yang terdaftar di rombel tersebut. | Filter bekerja presisi, total siswa sesuai rombel. | **PASS** |
| **TC-STD-02** | Input Siswa Baru dengan NIS Duplikat | Menginput siswa baru dengan NIS yang sudah ada di database. | Sistem menolak dengan pesan validasi NIS telah terdaftar. | Muncul notifikasi error keunikan NIS. | **PASS** |
| **TC-STD-03** | Upload Foto Siswa (JPEG/PNG) | Mengunggah foto profil siswa ukuran 1 MB. | Foto terunggah, dikompresi otomatis, thumbnail tampil di kartu siswa. | Foto berhasil tersimpan dalam format yang dioptimasi. | **PASS** |
| **TC-STD-04** | Upload Berkas Non-Gambar ke Foto | Mengunggah file `.exe` atau `.txt` ke kolom foto profil. | Sistem menolak file, hanya menerima format gambar (JPG/PNG/WebP). | Validasi ekstensi menolak berkas. | **PASS** |
| **TC-STD-05** | Hapus Foto Profil Siswa | Menghapus foto profil pada siswa yang sudah memiliki foto. | Foto terhapus dari server, avatar siswa kembali ke default inisial nama. | File fisik terhapus, foto_path menjadi null. | **PASS** |
| **TC-STD-06** | Export Excel Data Siswa | Menekan tombol Export Excel pada daftar siswa. | File `.xlsx` terunduh dengan seluruh kolom data siswa lengkap. | File Excel terunduh dan dapat dibuka tanpa corrupt. | **PASS** |

---

### Suite E: Presensi Mandiri Guru & Validasi Lokasi (Geofencing)

| ID Kasus Uji | Skenario Uji | Tindakan / Input | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **TC-ABS-01** | Presensi Masuk di Dalam Radius Sekolah | Guru menekan tombol check-in saat berada di koordinat sekolah (< 50m). | Presensi berhasil dicatat, jam masuk tersimpan tepat waktu/terlambat. | Status "Hadir" tercatat, jam tersimpan. | **PASS** |
| **TC-ABS-02** | Presensi di Luar Radius Sekolah | Guru mencoba check-in saat GPS mendeteksi jarak > 100m dari sekolah. | Sistem menolak check-in dan menampilkan indikator "Di Luar Radius". | Tombol dinonaktifkan / ditolak dengan pesan peringatan jarak. | **PASS** |
| **TC-ABS-03** | Presensi dengan Selfie Kamera | Mengambil gambar selfie presensi langsung via web camera. | Gambar terambil, diberi watermark tanggal/waktu, dan tersimpan. | Foto presensi tersimpan lengkap dengan kompresi. | **PASS** |
| **TC-ABS-04** | Presensi Dua Kali di Hari yang Sama | Guru yang sudah check-in mencoba melakukan check-in ulang. | Sistem mendeteksi sudah absen, tombol berubah menjadi "Check-Out". | Alur beralih ke check-out pulang secara konsisten. | **PASS** |
| **TC-ABS-05** | Pengajuan Izin / Sakit | Guru mengisi form pengajuan izin/sakit dengan lampiran surat. | Data izin masuk ke antrean verifikasi staf admin / kepala sekolah. | Pengajuan tersimpan dengan status menunggu persetujuan. | **PASS** |

---

### Suite F: PPDB Online & Verifikasi Pendaftaran

| ID Kasus Uji | Skenario Uji | Tindakan / Input | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **TC-PDB-01** | Pendaftaran Calon Siswa (Valid) | Mengisi data calon murid, orang tua, dan unggah dokumen KK/Akta. | Submisi sukses, sistem mengeluarkan nomor pendaftaran unik (PPDB-YYYY-...). | Nomor pendaftaran digenerate otomatis, pesan sukses muncul. | **PASS** |
| **TC-PDB-02** | Validasi Ukuran Dokumen (> 5MB) | Mengunggah file Akta Kelahiran berukuran 7 MB. | Sistem menolak dengan pesan ukuran file maksimal 5MB. | Respon validasi 422 dengan pesan ramah pengguna. | **PASS** |
| **TC-PDB-03** | Cek Status Pendaftaran Publik | Memasukkan nomor pendaftaran resmi calon siswa. | Menampilkan detail nama anak, jenjang, dan status (Menunggu/Diterima). | Informasi status pendaftaran terbuka sesuai nomor. | **PASS** |
| **TC-PDB-04** | Cek Status dengan Nomor Fiktif | Memasukkan nomor pendaftaran acak yang tidak ada. | Muncul notifikasi nomor pendaftaran tidak ditemukan. | Pesan "*Nomor pendaftaran tidak ditemukan*" tampil. | **PASS** |
| **TC-PDB-05** | Proteksi Anti-Bot (Rate Limiting) | Melakukan pendaftaran beruntun lebih dari 10 kali dalam 1 menit. | Sistem menolak sementara dengan status `429 Too Many Requests`. | Rate limit aktif, spam bot tertahan. | **PASS** |
| **TC-PDB-06** | Verifikasi Status oleh Admin | Admin menyetujui pendaftaran calon siswa menjadi "Diterima". | Status calon siswa berubah dan dapat diintegrasikan ke data siswa aktif. | Status terupdate menjadi Diterima di portal admin & wali. | **PASS** |

---

### Suite G: Buku Nilai & Cetak Rapor

| ID Kasus Uji | Skenario Uji | Tindakan / Input | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **TC-RAP-01** | Input Nilai Angka di Luar Range (150 atau -10) | Memasukkan nilai 150 pada kolom nilai sumatif. | Sistem menolak dan membatasi input hanya pada rentang 0 - 100. | Validasi form membatasi angka antara 0 hingga 100. | **PASS** |
| **TC-RAP-02** | Kalkulasi Otomatis Rata-rata | Menginput nilai tugas, STS, dan SAS. | Rata-rata nilai akhir dan predikat terhitung otomatis tanpa refresh. | Nilai akhir terkalkulasi reaktif di antarmuka. | **PASS** |
| **TC-RAP-03** | Kunci Periode Rapor (*Period Lock*) | Mengedit nilai rapor saat semester telah dinyatakan ditutup. | Form nilai berstatus *read-only*, tombol simpan dinonaktifkan. | Perubahan dicegah sesuai status periode semester. | **PASS** |
| **TC-RAP-04** | Preview Cetak Rapor | Menekan tombol Cetak/Pratinjau Rapor Siswa. | Tampilan layout cetak rapi dengan kop sekolah, capaian, dan kolom TTD. | Modal cetak muncul sesuai standar dokumen rapor. | **PASS** |
| **TC-RAP-05** | Input Catatan Wali Kelas | Mengisi deskripsi catatan perkembangan karakter siswa. | Catatan tersimpan dan otomatis muncul di lembar halaman rapor. | Catatan tampil utuh pada review rapor. | **PASS** |

---

### Suite H: Transaksi Keuangan & Kas

| ID Kasus Uji | Skenario Uji | Tindakan / Input | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **TC-FIN-01** | Pembayaran SPP Bulanan | Kasir memilih siswa, memilih tagihan SPP, dan memasukkan nominal bayar. | Tagihan lunas / cicilan tercatat, kwitansi pembayaran langsung siap cetak. | Status tagihan terupdate, kwitansi resmi terbit. | **PASS** |
| **TC-FIN-02** | Input Pengeluaran Kas Sekolah | Menginput transaksi kas keluar operasional sekolah. | Saldo kas berkurang dan tercatat di buku kas umum (*cashbook*). | Mutasi kas masuk ke riwayat pembukuan. | **PASS** |
| **TC-FIN-03** | Validasi Nominal Negatif | Menginput nominal transaksi minus (-50.000). | Form menolak pengiriman dan meminta nilai positif. | Validasi form mewajibkan angka lebih dari 0. | **PASS** |
| **TC-FIN-04** | Filter Laporan Keuangan per Periode | Memilih rentang tanggal 1 bulan terakhir. | Rekapitulasi kas masuk, keluar, dan saldo akhir terakumulasi akurat. | Angka rekapitulasi sesuai total transaksi periode terpilih. | **PASS** |

---

### Suite I: Pencadangan & Pemulihan Sistem (Backup & Restore)

| ID Kasus Uji | Skenario Uji | Tindakan / Input | Hasil yang Diharapkan | Hasil Aktual | Status |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **TC-BCK-01** | Pembuatan Backup Manual | Admin mengklik tombol "Buat Backup Sekarang". | File arsip ZIP database terbuat di direktori privat sistem. | Notifikasi sukses, riwayat backup bertambah. | **PASS** |
| **TC-BCK-02** | Unduh File Cadangan | Admin menekan tombol Download pada riwayat backup. | File ZIP terdownload melalui stream controller resmi. | File `.zip` berhasil terunduh utuh. | **PASS** |
| **TC-BCK-03** | Restore dengan Password Benar | Memilih file backup dan memasukkan password akun administrator. | Database ter-restore ke titik waktu backup tersebut. | Proses restore berhasil, sistem reload data. | **PASS** |
| **TC-BCK-04** | Restore dengan Password Salah | Mencoba restore dengan memasukkan password acak/salah. | Tindakan restore dibatalkan seketika, database tetap aman. | Pesan kesalahan konfirmasi password tampil. | **PASS** |

---

## 🏁 3. Kesimpulan Kesiapan Sistem

1. **Integritas Alur Kerja:** Seluruh modul operasional sekolah (Presensi, PPDB, Akademik, Keuangan, dan Bimbingan Konseling) berfungsi konsisten tanpa kegagalan fungsional.
2. **Keamanan Data & Privasi Peran:** Hak akses terisolasi secara ketat sehingga data konseling dan rekam catatan perilaku siswa tidak dapat diakses oleh pihak yang tidak berhak.
3. **Kesiapan Produksi:** Sistem dinyatakan **layak dan siap beroperasi di lingkungan produksi (*Production Ready*)**.

