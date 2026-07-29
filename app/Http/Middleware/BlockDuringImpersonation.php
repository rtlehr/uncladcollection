<?php

namespace App\Http\Middleware;

use App\Services\UserImpersonationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDuringImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->has(UserImpersonationService::ORIGINAL_USER_ID)) {
            return back()->with(
                'error',
                'This action is disabled while impersonating a customer. Stop impersonating to continue.',
            );
        }

        return $next($request);
    }
}
