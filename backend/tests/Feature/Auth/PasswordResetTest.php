<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_returns_202_for_an_unknown_account(): void
    {
        $response = $this->postJson('/api/v1/auth/password/forgot', [
            'login_identifier' => 'nobody@example.com',
        ]);

        $response->assertStatus(202);
    }

    public function test_forgot_password_returns_202_for_a_real_account(): void
    {
        User::factory()->create(['login_identifier' => 'teacher@example.com']);

        $response = $this->postJson('/api/v1/auth/password/forgot', [
            'login_identifier' => 'teacher@example.com',
        ]);

        $response->assertStatus(202);
    }

    public function test_reset_password_with_a_valid_token_succeeds(): void
    {
        $user = User::factory()->create(['login_identifier' => 'teacher@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'login_identifier' => 'teacher@example.com',
            'token' => $token,
            'password' => 'newpassword123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        $user = User::factory()->create(['login_identifier' => 'teacher@example.com']);
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/auth/password/reset', [
            'login_identifier' => 'teacher@example.com',
            'token' => $token,
            'password' => 'newpassword123',
        ])->assertOk();

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'login_identifier' => 'teacher@example.com',
            'token' => $token,
            'password' => 'anotherpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_token_expires_after_60_minutes(): void
    {
        $user = User::factory()->create(['login_identifier' => 'teacher@example.com']);
        $token = Password::broker()->createToken($user);

        Carbon::setTestNow(now()->addMinutes(61));

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'login_identifier' => 'teacher@example.com',
            'token' => $token,
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(422);

        Carbon::setTestNow();
    }

    public function test_reset_password_requires_minimum_length(): void
    {
        $user = User::factory()->create(['login_identifier' => 'teacher@example.com']);
        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/v1/auth/password/reset', [
            'login_identifier' => 'teacher@example.com',
            'token' => $token,
            'password' => 'short',
        ]);

        $response->assertStatus(422);
    }
}
