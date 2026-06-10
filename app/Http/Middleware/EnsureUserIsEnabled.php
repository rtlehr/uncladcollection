<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            Auth::check() &&
            Auth::user()->is_disabled
        ) {
            Auth::logout();

            return redirect('/login')
                ->withErrors([
                    'email' => 'This account has been disabled.',
                ]);
        }

        return $next($request);
    }
}