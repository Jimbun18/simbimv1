<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('unauthenticated request to protected endpoint is denied with 401', function () {
    $response = $this->getJson('/api/v1/users');
    $response->assertUnauthorized();
});

test('guru and wali are denied access to user management with 403', function () {
    $roleGuru = Role::where('nama', Role::GURU)->first();
    $guru = User::create([
        'nama' => 'Guru RBAC',
        'username' => 'gururbac',
        'password' => Hash::make('password123'),
        'role_id' => $roleGuru->id,
        'status' => 'aktif',
    ]);
    $tokenGuru = $guru->createToken('test')->plainTextToken;

    $responseGuru = $this->withHeader('Authorization', "Bearer {$tokenGuru}")
        ->getJson('/api/v1/users');

    $responseGuru->assertForbidden()
        ->assertJsonPath('success', false);

    $roleWali = Role::where('nama', Role::WALI)->first();
    $wali = User::create([
        'nama' => 'Wali RBAC',
        'username' => 'walirbac',
        'password' => Hash::make('password123'),
        'role_id' => $roleWali->id,
        'status' => 'aktif',
    ]);
    $tokenWali = $wali->createToken('test')->plainTextToken;

    $responseWali = $this->withHeader('Authorization', "Bearer {$tokenWali}")
        ->getJson('/api/v1/users');

    $responseWali->assertForbidden()
        ->assertJsonPath('success', false);
});

test('staf admin and kepala sekolah can access user management', function () {
    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $admin = User::create([
        'nama' => 'Admin RBAC',
        'username' => 'adminrbac',
        'password' => Hash::make('password123'),
        'role_id' => $roleAdmin->id,
        'status' => 'aktif',
    ]);
    $tokenAdmin = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
        ->getJson('/api/v1/users');

    $response->assertOk()
        ->assertJsonPath('success', true);
});
