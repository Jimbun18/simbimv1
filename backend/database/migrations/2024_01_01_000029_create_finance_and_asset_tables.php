<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Master Jenis Biaya (fee_types)
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama', 100);
            $table->decimal('nominal', 12, 2);
            $table->string('periode', 20)->default('bulanan'); // bulanan, semester, tahunan, sekali
            $table->boolean('is_aktif')->default(true);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        // 2. Tagihan Siswa (invoices)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('no_tagihan', 50)->unique();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('fee_type_id')->constrained('fee_types')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->string('periode', 30); // misal: '2026-08', '2026/2027 Ganjil'
            $table->decimal('jumlah', 12, 2);
            $table->decimal('jumlah_dibayar', 12, 2)->default(0);
            $table->decimal('sisa', 12, 2);
            $table->string('status', 20)->default('belum_bayar'); // belum_bayar, sebagian, lunas, menunggak, batal
            $table->date('jatuh_tempo');
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['student_id', 'status']);
            $table->index(['academic_year_id', 'fee_type_id']);
            $table->index('jatuh_tempo');
        });

        // 3. Pembayaran (payments)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('no_kuitansi', 50)->unique();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('jumlah', 12, 2);
            $table->string('metode', 20)->default('tunai'); // tunai, transfer, qris, beasiswa
            $table->string('bukti_path')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('is_batal')->default(false);
            $table->text('alasan_batal')->nullable();
            $table->foreignId('dicatat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['invoice_id', 'tanggal']);
            $table->index('tanggal');
        });

        // 4. Kas Masuk & Keluar Sekolah (finance_transactions)
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('no_transaksi', 50)->unique();
            $table->string('tipe', 10); // masuk, keluar
            $table->string('kategori', 50); // pembayaran_siswa, operasional, gaji, sarpras, atk, dana_bos, infaq, lainnya
            $table->decimal('jumlah', 12, 2);
            $table->date('tanggal');
            $table->text('keterangan');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('lampiran_path')->nullable();
            $table->boolean('is_pembalik')->default(false);
            $table->unsignedBigInteger('referensi_transaksi_id')->nullable();
            $table->foreignId('dicatat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipe', 'tanggal']);
            $table->index('tanggal');
        });

        // 5. Aset Sarpras (assets)
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama', 150);
            $table->string('kategori', 50); // mebel, elektronik, buku, alat_peraga, kendaraan, gedung_ruang, olahraga, lainnya
            $table->string('lokasi', 100);
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->integer('tahun_perolehan')->nullable();
            $table->decimal('nilai', 12, 2)->default(0);
            $table->string('kondisi', 20)->default('baik'); // baik, rusak_ringan, rusak_berat, hilang
            $table->integer('jumlah')->default(1);
            $table->string('satuan', 20)->default('unit');
            $table->string('foto_path')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['kategori', 'kondisi']);
            $table->index('lokasi');
        });

        // 6. Perawatan Aset (asset_maintenance)
        Schema::create('asset_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('tanggal');
            $table->text('deskripsi');
            $table->decimal('biaya', 12, 2)->default(0);
            $table->string('pelaksana', 100)->nullable();
            $table->string('kondisi_sesudah', 20)->default('baik');
            $table->string('bukti_path')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'tanggal']);
        });

        // 7. Peminjaman Aset (asset_loans)
        Schema::create('asset_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('peminjam_nama', 100);
            $table->foreignId('peminjam_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tgl_pinjam');
            $table->date('tgl_rencana_kembali');
            $table->date('tgl_kembali_aktual')->nullable();
            $table->string('status', 20)->default('dipinjam'); // dipinjam, dikembalikan, terlambat
            $table->text('keperluan')->nullable();
            $table->string('kondisi_sebelum', 20)->default('baik');
            $table->string('kondisi_sesudah', 20)->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['asset_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_loans');
        Schema::dropIfExists('asset_maintenance');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('fee_types');
    }
};
