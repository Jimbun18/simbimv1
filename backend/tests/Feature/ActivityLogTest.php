<?php

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('login and profile actions create activity log records', function () {
    $role = Role::where('nama', Role::STAF_ADMIN)->first();
    $user = User::create([
        'nama' => 'Log User',
        'username' => 'loguser',
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);

    // Perform login
    $this->postJson('/api/v1/auth/login', [
        'login' => 'loguser',
        'password' => 'password123',
    ]);

    expect(ActivityLog::where('aksi', 'login')->where('user_id', $user->id)->exists())->toBeTrue();

    // Perform profile update
    $token = $user->createToken('test')->plainTextToken;
    $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson('/api/v1/me', [
            'nama' => 'Log User Updated',
        ]);

    $log = ActivityLog::where('aksi', 'update_profile')->where('user_id', $user->id)->first();
    expect($log)->not->toBeNull();
    expect($log->data_sesudah['nama'])->toBe('Log User Updated');
});

test('only admin can view activity logs list', function () {
    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $admin = User::create([
        'nama' => 'Admin Log',
        'username' => 'adminlog',
        'password' => Hash::make('password123'),
        'role_id' => $roleAdmin->id,
        'status' => 'aktif',
    ]);
    $tokenAdmin = $admin->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$tokenAdmin}")
        ->getJson('/api/v1/activity-logs');

    $response->assertOk()
        ->assertJsonPath('success', true);
});
