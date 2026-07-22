<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdvertiserPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $membership = $request->user()?->advertiserMemberships()
            ->where('is_active', true)
            ->with('advertiser')
            ->orderByDesc('is_primary')
            ->first();

        abort_unless($membership && $membership->advertiser, 403, 'You do not have access to an advertiser account.');
        $request->attributes->set('advertiserMembership', $membership);
        $request->attributes->set('advertiser', $membership->advertiser);

        return $next($request);
    }
}
