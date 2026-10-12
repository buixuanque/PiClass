<?php

namespace Tests\Feature\Models;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_active_role_when_valid_to_is_null(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subDay(),
            'valid_to' => null,
        ]);

        $this->assertTrue($user->hasActiveRole('teacher'));
    }

    public function test_user_does_not_have_active_role_when_valid_to_in_past(): void
    {
        $user = User::factory()->create();
        $role = Role::factory()->create(['code' => 'teacher']);
        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'valid_from' => now()->subMonth(),
            'valid_to' => now()->subDay(),
        ]);

        $this->assertFalse($user->hasActiveRole('teacher'));
    }

    public function test_user_does_not_have_a_role_it_never_held(): void
    {
        $user = User::factory()->create();
        Role::factory()->create(['code' => 'admin']);

        $this->assertFalse($user->hasActiveRole('admin'));
    }
}
