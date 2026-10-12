<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SetContextRequest;

class ContextController extends Controller
{
    public function __invoke(SetContextRequest $request)
    {
        $roleCode = $request->input('role_code');

        if (! $request->user()->hasActiveRole($roleCode)) {
            return response()->json([
                'error' => ['code' => 'ROLE_NOT_HELD', 'message' => 'User does not currently hold this role.'],
            ], 403);
        }

        $request->session()->put('active_role_code', $roleCode);

        return response()->json(['active_role' => $roleCode]);
    }
}
