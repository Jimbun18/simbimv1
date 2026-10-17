# Pertanyaan Terbuka — SIM-BIM

Dokumen ini mencatat pertanyaan yang belum terjawab pasti beserta asumsi sementara.
Jika jawabannya berubah, kode perlu disesuaikan.

## Fase 1

| # | Pertanyaan | Asumsi Sementara | Status |
|---|------------|------------------|--------|
| OQ-01 | Auth: Sanctum (SPA + token) vs JWT? | Sanctum — native Laravel 11, httpOnly cookie untuk SPA, token untuk PWA mobile | ✅ Disetujui |
| OQ-02 | Foto bukti mengajar: per sesi atau per hari? (PRD §19 #3) | Per sesi (per schedule), configurable via settings | ✅ Disetujui |
| OQ-03 | `time_slots.hari_berlaku`: multi-hari per row atau satu row per hari? | Satu row per hari (kolom `hari` enum Senin-Sabtu) | ✅ Disetujui |
| OQ-04 | Setting absensi: di `school_profile` atau `settings`? | Split: `school_profile` untuk profil + geofence/jam, `settings` (key-value) untuk lainnya | ✅ Disetujui |
| OQ-05 | Akses guru ke data siswa: semua kelas atau hanya diampu? | Hanya kelas yang diampu via `teacher_assignments` + wali kelas | ✅ Disetujui |

## Fase 2

| # | Pertanyaan | Asumsi Sementara | Status |
|---|------------|------------------|--------|
| OQ-06 | Format dan penanganan duplikasi data Excel Import? | Disediakan opsi per batch: `skip` (lewati yang duplikat), `update` (timpa data lama), `reject` (batalkan seluruh batch jika ada duplikat). Dilengkapi rollback 1-klik. | ✅ Terimplementasi |
| OQ-07 | Kebijakan proteksi & masking data PTK (Guru & Tendik)? | NPWP, nama bank, no rekening hanya boleh diakses Admin dan PTK pemilik akun. NIK dimasking 6 digit tengah untuk peran lain. | ✅ Terimplementasi |
| OQ-08 | Validasi bentrok jadwal KBM (Schedule Conflict)? | Dicegah di tingkat service & validasi: 1 guru tidak boleh di 2 kelas bersamaan, 1 ruangan tidak boleh dipakai 2 kelas bersamaan, 1 rombel tidak boleh ada 2 mapel bersamaan. | ✅ Terimplementasi |

## Fase 3

| # | Pertanyaan | Asumsi Sementara | Status |
|---|------------|------------------|--------|
| OQ-09 | Validasi GPS Geofence & Kompresi Foto Selfie? | Memakai Haversine formula terhadap koordinat `school_profile`. Dapat diaktifkan/dinonaktifkan via `settings.geofence_enabled`. Foto dikompres & di-watermark teks nama, jam, dan SIM-BIM. | ✅ Terimplementasi |
| OQ-10 | Kewajiban bukti mengajar sebelum absensi murid? | Wajib jika `settings.require_teaching_evidence` bernilai true pada sesi/jadwal KBM. Foto di-watermark mapel, kelas, dan waktu. | ✅ Terimplementasi |
| OQ-11 | Batas waktu edit absensi murid oleh guru? | Guru dibatasi 24 jam (`settings.student_attendance_edit_limit_hours`). Setelahnya dikunci dan hanya dapat dikoreksi oleh Admin dengan alasan wajib tercatat di audit log. | ✅ Terimplementasi |

## Fase 4

| # | Pertanyaan | Asumsi Sementara | Status |
|---|------------|------------------|--------|
| OQ-12 | Kontrol akses privasi data anak untuk Wali Murid? | Wali murid HANYA dapat melihat kehadiran, kegiatan, dan progres anak kandung/tangungannya via relasi `student_guardian`. Akses ke ID anak lain diblokir dengan 403. | ✅ Terimplementasi |
| OQ-13 | Format & fleksibilitas pencatatan progres karakter? | Mendukung kategori `akademik`, `tahfidz`, `sholat`, `karakter`, `ekskul` baik input per anak maupun input massal (batch check-in). | ✅ Terimplementasi |
| OQ-14 | Mekanisme notifikasi in-app & penargetan pengumuman? | Notifikasi tersimpan di tabel `notifications` dengan pelacakan status dibaca (`dibaca_pada`). Pengumuman ditargetkan per peran (`semua`, `guru`, `wali`, dll.). | ✅ Terimplementasi |

## Fase 5

| # | Pertanyaan | Asumsi Sementara | Status |
|---|------------|------------------|--------|
| OQ-15 | Kebijakan pembatalan pembayaran & koreksi kas? (FR-KEU-08) | Data transaksi keuangan tidak pernah dihapus keras (hard delete). Pembatalan dilakukan dengan menandai `is_batal = true`, menerbitkan transaksi jurnal pembalik kas bertipe lawan (`is_pembalik = true`), dan mengembalikan sisa tagihan invoice secara transaksional dengan audit log lengkap. | ✅ Terimplementasi |
| OQ-16 | Penagihan Massal (Batch Invoicing) SPP? | Tagihan dapat di-generate otomatis per rombel atau seluruh siswa aktif dengan pencegahan duplikasi tagihan pada jenis biaya & periode yang sama. Status tagihan otomatis bertransisi: `belum_bayar` -> `sebagian` -> `lunas` (atau `menunggak` jika melewati jatuh tempo). | ✅ Terimplementasi |
| OQ-17 | Pelacakan Inventaris Aset & Peminjaman Sarpras? | Setiap aset memiliki kode unik, pencatatan histori pemeliharaan (biaya & teknisi), serta pelacakan sirkulasi peminjaman/pengembalian barang sarana prasarana sekolah. | ✅ Terimplementasi |

## Fase 6

| # | Pertanyaan | Asumsi Sementara | Status |
|---|------------|------------------|--------|
| OQ-18 | Strategi Backup & Kebijakan Retensi Arsip? (FR-BCK-01 s/d FR-BCK-05) | Backup terjadwal setiap hari pukul 01:00 WIB (`app:backup-run`) dan manual melalui Web UI. Format arsip berupa `.zip` berisi snapshot SQLite & metadata JSON beserta checksum SHA-256. Retensi otomatis memelihara 30 backup terbaru. Restorasi dilindungi verifikasi password administrator dan pembuatan snapshot pengaman otomatis sebelum penimpaan database. | ✅ Terimplementasi |
| OQ-19 | Dashboard Eksekutif Kepala Sekolah & Aksesibilitas? | Menampilkan KPI kesiswaan & PTK, live tracking kehadiran hari ini (guru, siswa, kepatuhan bukti mengajar KBM), capaian SPP & saldo kas bulan berjalan, status sarpras, serta feed jejak audit aktivitas terbaru. Hak akses dibatasi hanya untuk Kepala Sekolah dan Staf Administrasi. | ✅ Terimplementasi |
| OQ-20 | Arsitektur SPA/PWA Antarmuka Pengguna (Frontend)? | Frontend dibangun menggunakan Vue 3 + Tailwind CSS v4 + Vite + Pinia + Vue Router. Layout responsif: navigasi atas desktop untuk Admin, dan bottom navigation bar di perangkat mobile untuk Guru dan Wali Murid. Dilengkapi quick switcher kredensial demo untuk pengujian operasional sekolah. | ✅ Terimplementasi |


