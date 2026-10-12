<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword123']);

        $response = $this->actingAs($user)->patchJson('/api/v1/me/password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }

    public function test_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword123']);

        $response = $this->actingAs($user)->patchJson('/api/v1/me/password', [
            'current_password' => 'wrong-password',
            'new_password' => 'newpassword123',
        ]);

        $response->assertStatus(422);
        $this->assertTrue(Hash::check('oldpassword123', $user->refresh()->password));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->patchJson('/api/v1/me/password', [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
        ]);

        $response->assertStatus(401);
    }
}
