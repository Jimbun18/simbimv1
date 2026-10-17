<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            Role::KEPALA_SEKOLAH => 'web',
            Role::STAF_ADMIN => 'web',
            Role::GURU => 'web',
            Role::WALI => 'web',
        ];

        foreach ($roles as $name => $guard) {
            Role::firstOrCreate(
                ['nama' => $name],
                ['guard_name' => $guard]
            );
        }
    }
}
