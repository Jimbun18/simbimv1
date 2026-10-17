<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $roleAdmin = Role::where('nama', Role::STAF_ADMIN)->first();
    $this->admin = User::create([
        'nama' => 'Admin Utama',
        'username' => 'adminutama',
        'password' => Hash::make('password123'),
        'role_id' => $roleAdmin->id,
        'status' => 'aktif',
    ]);
    $this->token = $this->admin->createToken('admin-token')->plainTextToken;
});

test('admin can create new user', function () {
    $roleGuru = Role::where('nama', Role::GURU)->first();

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson('/api/v1/users', [
            'nama' => 'Guru Baru',
            'username' => 'gurubaru',
            'email' => 'gurubaru@simbim.sch.id',
            'no_hp' => '+628111222333',
            'password' => 'Rahasia123!',
            'role_id' => $roleGuru->id,
            'status' => 'aktif',
        ]);

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.username', 'gurubaru');

    $this->assertDatabaseHas('users', [
        'username' => 'gurubaru',
        'email' => 'gurubaru@simbim.sch.id',
    ]);
});

test('admin can search and filter users', function () {
    $roleGuru = Role::where('nama', Role::GURU)->first();
    User::create([
        'nama' => 'Ahmad Dahlan',
        'username' => 'dahlan',
        'password' => Hash::make('password123'),
        'role_id' => $roleGuru->id,
        'status' => 'aktif',
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson('/api/v1/users?search=Dahlan');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.username', 'dahlan');
});

test('admin can update user and reset password', function () {
    $roleGuru = Role::where('nama', Role::GURU)->first();
    $guru = User::create([
        'nama' => 'Guru Edit',
        'username' => 'guruedit',
        'password' => Hash::make('password123'),
        'role_id' => $roleGuru->id,
        'status' => 'aktif',
    ]);

    // Update
    $updateResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->putJson("/api/v1/users/{$guru->id}", [
            'nama' => 'Guru Diedit',
        ]);

    $updateResponse->assertOk()
        ->assertJsonPath('data.nama', 'Guru Diedit');

    // Reset password
    $resetResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->postJson("/api/v1/users/{$guru->id}/reset-password", [
            'password' => 'NewPassword123!',
        ]);

    $resetResponse->assertOk()
        ->assertJsonPath('success', true);

    $guru->refresh();
    expect($guru->must_change_password)->toBeTrue();
    expect(Hash::check('NewPassword123!', $guru->password))->toBeTrue();
});

test('admin cannot deactivate own account but can deactivate other user', function () {
    $roleGuru = Role::where('nama', Role::GURU)->first();
    $other = User::create([
        'nama' => 'User Lain',
        'username' => 'userlain',
        'password' => Hash::make('password123'),
        'role_id' => $roleGuru->id,
        'status' => 'aktif',
    ]);

    // Attempt deactivating self
    $selfResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson("/api/v1/users/{$this->admin->id}");

    $selfResponse->assertStatus(422)
        ->assertJsonPath('success', false);

    // Deactivate other
    $otherResponse = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->deleteJson("/api/v1/users/{$other->id}");

    $otherResponse->assertOk()
        ->assertJsonPath('success', true);

    $other->refresh();
    expect($other->status)->toBe('nonaktif');
    expect($other->trashed())->toBeTrue();
});
