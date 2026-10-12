<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate(['login_identifier' => ['required', 'string']]);

        // Chủ ý bỏ qua kết quả trả về của broker — luôn trả 202
        // để response không bao giờ lộ việc tài khoản có tồn tại hay không.
        Password::broker()->sendResetLink([
            'login_identifier' => $request->input('login_identifier'),
        ]);

        return response()->json([], 202);
    }
}
