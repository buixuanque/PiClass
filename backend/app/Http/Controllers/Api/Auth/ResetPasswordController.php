<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'login_identifier' => ['required', 'string'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $status = Password::broker()->reset(
            [
                'login_identifier' => $request->input('login_identifier'),
                'token' => $request->input('token'),
                'password' => $request->input('password'),
            ],
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => 'Token không hợp lệ hoặc đã hết hạn.',
            ]);
        }

        return response()->json([], 200);
    }
}
