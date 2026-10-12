<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'login_identifier' => $user->login_identifier,
            ],
            'active_role' => $request->session()->get('active_role_code'),
            'available_roles' => $user->userRoles()
                ->where(fn ($q) => $q->whereNull('valid_to')->orWhere('valid_to', '>', now()))
                ->with('role')
                ->get()
                ->pluck('role.code'),
        ]);
    }
}
