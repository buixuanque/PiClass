<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_switches_to_a_role_the_user_currently_holds(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'admin']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/auth/context', ['role_code' => 'admin']);

        $response->assertOk();
        $response->assertJson(['active_role' => 'admin']);
    }

    public function test_rejects_a_role_the_user_never_held(): void
    {
        $user = User::factory()->create();
        Role::factory()->create(['code' => 'admin']);

        $response = $this->actingAs($user)->postJson('/api/v1/auth/context', ['role_code' => 'admin']);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'ROLE_NOT_HELD');
    }

    public function test_rejects_a_role_whose_assignment_has_ended(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subMonth(),
            'valid_to' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/auth/context', ['role_code' => 'teacher']);

        $response->assertStatus(403);
    }
}
