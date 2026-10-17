<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->boolean('is_tematik')->default(false)->after('nama');
            $table->unsignedTinyInteger('tema_nomor')->nullable()->after('is_tematik');
            $table->json('muatan_pelajaran')->nullable()->after('tema_nomor');
            $table->text('deskripsi')->nullable()->after('muatan_pelajaran');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['is_tematik', 'tema_nomor', 'muatan_pelajaran', 'deskripsi']);
        });
    }
};
