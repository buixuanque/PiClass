<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_current_user_and_available_roles(): void
    {
        $user = User::factory()->create(['login_identifier' => 'teacher@example.com']);
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonPath('user.login_identifier', 'teacher@example.com');
        $response->assertJson(['available_roles' => ['teacher']]);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
