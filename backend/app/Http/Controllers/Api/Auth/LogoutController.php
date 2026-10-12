<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function __invoke(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // auth:sanctum đổi default guard thành 'sanctum' khi xác thực request này
        // (Illuminate\Auth\Middleware\Authenticate::authenticate() gọi shouldUse()).
        // Trả lại 'web' để mọi kiểm tra "đã đăng nhập chưa" sau logout nhất quán.
        Auth::shouldUse('web');

        return response()->noContent();
    }
}
