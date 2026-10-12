<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_in_with_correct_credentials_and_lists_active_roles(): void
    {
        $user = User::factory()->create([
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
        ]);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('user.login_identifier', 'teacher@example.com');
        $response->assertJson(['available_roles' => ['teacher']]);
        $this->assertAuthenticatedAs($user);
    }

    public function test_rejects_wrong_password(): void
    {
        User::factory()->create([
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => 'teacher@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_rejects_inactive_user_even_with_correct_password(): void
    {
        User::factory()->create([
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_locks_out_after_five_failed_attempts_in_a_minute(): void
    {
        User::factory()->create([
            'login_identifier' => 'teacher@example.com',
            'password' => 'password123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'login_identifier' => 'teacher@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'login_identifier' => 'teacher@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }
}
