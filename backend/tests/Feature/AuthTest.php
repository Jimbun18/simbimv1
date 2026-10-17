<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('user can login using username', function () {
    $role = Role::where('nama', Role::STAF_ADMIN)->first();
    User::create([
        'nama' => 'Admin Test',
        'username' => 'admintest',
        'email' => 'admin@test.com',
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'admintest',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'nama', 'username', 'role'],
                'token',
            ],
        ]);
});

test('user can login using email', function () {
    $role = Role::where('nama', Role::GURU)->first();
    User::create([
        'nama' => 'Guru Test',
        'username' => 'gurutest',
        'email' => 'guru@test.com',
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'guru@test.com',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.role', Role::GURU);
});

test('user can login using phone number starting with 08 or +62', function () {
    $role = Role::where('nama', Role::WALI)->first();
    User::create([
        'nama' => 'Wali Test',
        'username' => 'walitest',
        'no_hp' => '+6281234567890',
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);

    // Test with 08 format
    $response = $this->postJson('/api/v1/auth/login', [
        'login' => '081234567890',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);
});

test('failed login attempts increment counter and lock account after 5 fails', function () {
    $role = Role::where('nama', Role::GURU)->first();
    $user = User::create([
        'nama' => 'Lock Test',
        'username' => 'locktest',
        'email' => 'lock@test.com',
        'password' => Hash::make('correctpassword'),
        'role_id' => $role->id,
        'status' => 'aktif',
        'login_attempts' => 0,
    ]);

    // 4 failed attempts
    for ($i = 1; $i <= 4; $i++) {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'locktest',
            'password' => 'wrongpassword',
        ]);
        $response->assertStatus(422);
    }

    $user->refresh();
    expect($user->login_attempts)->toBe(4);
    expect($user->locked_until)->toBeNull();

    // 5th failed attempt locks the account
    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'locktest',
        'password' => 'wrongpassword',
    ]);
    $response->assertStatus(422);

    $user->refresh();
    expect($user->locked_until)->not->toBeNull();
    expect($user->isLocked())->toBeTrue();

    // Even with correct password, login is blocked
    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'locktest',
        'password' => 'correctpassword',
    ]);
    $response->assertStatus(422)
        ->assertJsonPath('errors.login.0', fn ($val) => str_contains($val, 'terkunci'));
});

test('inactive user cannot login', function () {
    $role = Role::where('nama', Role::GURU)->first();
    User::create([
        'nama' => 'Nonaktif Test',
        'username' => 'nonaktif',
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'status' => 'nonaktif',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'login' => 'nonaktif',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('errors.login.0', fn ($val) => str_contains($val, 'nonaktif'));
});

test('authenticated user can view profile, update profile and change password', function () {
    $role = Role::where('nama', Role::GURU)->first();
    $user = User::create([
        'nama' => 'Guru Profile',
        'username' => 'guruprofile',
        'email' => 'guruprofile@test.com',
        'password' => Hash::make('oldpassword123'),
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);

    $token = $user->createToken('test')->plainTextToken;

    // View profile
    $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/me');

    $meResponse->assertOk()
        ->assertJsonPath('data.username', 'guruprofile');

    // Update profile
    $updateResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson('/api/v1/me', [
            'nama' => 'Guru Profile Updated',
            'no_hp' => '+6289999999999',
        ]);

    $updateResponse->assertOk()
        ->assertJsonPath('data.nama', 'Guru Profile Updated');

    // Change password
    $pwdResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson('/api/v1/me/password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

    $pwdResponse->assertOk()
        ->assertJsonPath('success', true);

    // Logout
    $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout');

    $logoutResponse->assertOk();
    expect($user->tokens()->count())->toBe(0);
});

test('authenticated user can upload, persist, and delete profile photo', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    $role = Role::where('nama', Role::STAF_ADMIN)->first();
    $user = User::create([
        'nama' => 'Admin Photo Test',
        'username' => 'adminphoto',
        'email' => 'adminphoto@test.com',
        'password' => Hash::make('password123'),
        'role_id' => $role->id,
        'status' => 'aktif',
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $file = \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg', 400, 400);

    // Upload via POST /api/v1/me with _method PUT (multipart/form-data)
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->post('/api/v1/me', [
            '_method' => 'PUT',
            'foto' => $file,
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $user->refresh();
    expect($user->foto_path)->not->toBeNull();
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($user->foto_path);

    // Reload check via /me
    $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/me');
    $meResponse->assertOk()
        ->assertJsonPath('data.foto_path', $user->foto_path);

    // Delete photo
    $deleteResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/me/photo');
    $deleteResponse->assertOk()
        ->assertJsonPath('success', true);

    $user->refresh();
    expect($user->foto_path)->toBeNull();
});
