<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ReferenceTableSeeder::class,
            SchoolProfileSeeder::class,
            AcademicYearSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            FeeTypeSeeder::class,
            SchoolSampleDataSeeder::class,
        ]);
    }
}
