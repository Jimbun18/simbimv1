<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('dusun', 100)->nullable()->after('rw');
            $table->string('telepon', 30)->nullable()->after('no_hp_siswa');
            $table->string('skhun', 50)->nullable()->after('asal_sekolah_tk');
            $table->string('penerima_kps', 10)->nullable()->default('Tidak')->after('skhun');
            $table->string('no_kps', 50)->nullable()->after('penerima_kps');
            $table->string('no_peserta_un', 50)->nullable()->after('no_kps');
            $table->string('no_seri_ijazah', 50)->nullable()->after('no_peserta_un');
            $table->string('penerima_kip', 10)->nullable()->default('Tidak')->after('no_seri_ijazah');
            $table->string('nomor_kip', 50)->nullable()->after('penerima_kip');
            $table->string('nama_di_kip', 100)->nullable()->after('nomor_kip');
            $table->string('nomor_kks', 50)->nullable()->after('nama_di_kip');
            $table->string('bank', 50)->nullable()->after('nomor_kks');
            $table->string('nomor_rekening_bank', 50)->nullable()->after('bank');
            $table->string('rekening_atas_nama', 100)->nullable()->after('nomor_rekening_bank');
            $table->string('layak_pip', 20)->nullable()->default('Tidak')->after('rekening_atas_nama');
            $table->text('alasan_layak_pip')->nullable()->after('layak_pip');
            $table->decimal('lingkar_kepala', 5, 1)->nullable()->after('berat_kg');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'dusun',
                'telepon',
                'skhun',
                'penerima_kps',
                'no_kps',
                'no_peserta_un',
                'no_seri_ijazah',
                'penerima_kip',
                'nomor_kip',
                'nama_di_kip',
                'nomor_kks',
                'bank',
                'nomor_rekening_bank',
                'rekening_atas_nama',
                'layak_pip',
                'alasan_layak_pip',
                'lingkar_kepala',
            ]);
        });
    }
};
