<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        $throttleKey = Str::lower($request->input('login_identifier')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return response()->json([
                'error' => [
                    'code' => 'TOO_MANY_ATTEMPTS',
                    'message' => "Too many login attempts. Try again in {$seconds} seconds.",
                ],
            ], 429);
        }

        $user = User::where('login_identifier', $request->input('login_identifier'))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password) || $user->status !== 'active') {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'login_identifier' => 'Thông tin đăng nhập không đúng.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        config(['session.lifetime' => $request->boolean('remember') ? 60 * 24 * 30 : 60 * 24 * 1]);

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'login_identifier' => $user->login_identifier,
            ],
            'available_roles' => $user->userRoles()
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
                ->with('role')
                ->get()
                ->pluck('role.code'),
        ]);
    }
}
