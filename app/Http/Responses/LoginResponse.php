<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $destination = $request->user()?->hasPermission('view_admin')
            ? route('admin.dashboard')
            : route('account.index');

        return redirect()->intended($destination);
    }
}
