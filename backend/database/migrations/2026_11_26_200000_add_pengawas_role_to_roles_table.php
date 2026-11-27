<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Role::firstOrCreate(
            ['nama' => Role::PENGAWAS],
            ['guard_name' => 'web']
        );
    }

    public function down(): void
    {
        Role::where('nama', Role::PENGAWAS)->delete();
    }
};
