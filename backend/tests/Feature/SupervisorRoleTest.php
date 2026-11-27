<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupervisorRoleTest extends TestCase
{
    use RefreshDatabase;

    private User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['nama' => Role::PENGAWAS], ['guard_name' => 'web']);

        $this->supervisor = User::create([
            'nama' => 'Pengawas Sekolah',
            'username' => 'pengawas_test',
            'email' => 'pengawas@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);
    }

    public function test_pengawas_can_access_monitoring_and_read_only_endpoints(): void
    {
        $getEndpoints = [
            '/api/v1/dashboard/summary',
            '/api/v1/students',
            '/api/v1/teachers',
            '/api/v1/staff',
            '/api/v1/guardians',
            '/api/v1/assets',
            '/api/v1/fee-types',
            '/api/v1/finance-transactions',
            '/api/v1/reports/finance',
            '/api/v1/ppdb',
            '/api/v1/report-cards/default-subjects',
            '/api/v1/organization-structure',
            '/api/v1/calendar',
        ];

        foreach ($getEndpoints as $endpoint) {
            $this->actingAs($this->supervisor)
                ->getJson($endpoint)
                ->assertOk();
        }
    }

    public function test_pengawas_is_blocked_from_all_business_write_endpoints(): void
    {
        $this->actingAs($this->supervisor)
            ->postJson('/api/v1/students', ['nama_lengkap' => 'Tes'])
            ->assertForbidden();

        $this->actingAs($this->supervisor)
            ->postJson('/api/v1/invoices', ['student_id' => 1])
            ->assertForbidden();

        $this->actingAs($this->supervisor)
            ->postJson('/api/v1/assets', ['nama_barang' => 'Meja'])
            ->assertForbidden();

        $this->actingAs($this->supervisor)
            ->postJson('/api/v1/users', ['nama' => 'Baru'])
            ->assertForbidden();

        $this->actingAs($this->supervisor)
            ->putJson('/api/v1/settings', ['settings' => []])
            ->assertForbidden();

        $this->actingAs($this->supervisor)
            ->postJson('/api/v1/attendance/teachers/check-in', [])
            ->assertForbidden();

        $this->actingAs($this->supervisor)
            ->deleteJson('/api/v1/announcements/1')
            ->assertForbidden();

        $this->actingAs($this->supervisor)
            ->getJson('/api/v1/activity-logs')
            ->assertForbidden();
    }

    public function test_pengawas_can_still_use_personal_non_business_post_endpoints(): void
    {
        $this->actingAs($this->supervisor)
            ->postJson('/api/v1/notifications/read-all')
            ->assertOk();

        $this->actingAs($this->supervisor)
            ->postJson('/api/v1/announcements/mark-all-read')
            ->assertOk();
    }

    public function test_admin_can_create_pengawas_user_using_role_name(): void
    {
        $adminRole = Role::firstOrCreate(['nama' => Role::STAF_ADMIN], ['guard_name' => 'web']);
        $admin = User::create([
            'nama' => 'Admin Test',
            'username' => 'admin_test',
            'email' => 'admin@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $adminRole->id,
            'status' => 'aktif',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/v1/users', [
                'nama' => 'lpmp',
                'username' => 'lpmp',
                'no_hp' => '01234567891',
                'role' => 'pengawas',
                'password' => 'Password123!',
            ])
            ->assertCreated()
            ->assertJsonPath('data.role.nama', 'pengawas');
    }
}
