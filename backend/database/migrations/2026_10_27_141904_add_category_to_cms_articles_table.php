<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cms_articles', function (Blueprint $table) {
            $table->string('category', 100)->default('Artikel')->after('title');
        });

        // Salin data prestasi lama ke cms_articles dengan kategori 'Prestasi'
        if (Schema::hasTable('cms_achievements')) {
            $achievements = \Illuminate\Support\Facades\DB::table('cms_achievements')->get();
            foreach ($achievements as $ach) {
                \Illuminate\Support\Facades\DB::table('cms_articles')->insert([
                    'title' => $ach->title,
                    'category' => 'Prestasi',
                    'description' => 'Dokumentasi dan pengumuman pencapaian prestasi siswa: ' . $ach->title,
                    'image_path' => $ach->image_path,
                    'created_by' => $ach->created_by,
                    'created_at' => $ach->created_at,
                    'updated_at' => $ach->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_articles', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
