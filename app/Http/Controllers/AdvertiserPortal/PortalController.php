<?php

namespace App\Http\Controllers\AdvertiserPortal;

use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use App\Models\AdvertiserMembership;
use Illuminate\Http\Request;

abstract class PortalController extends Controller
{
    protected function advertiser(Request $request): Advertiser
    {
        return $request->attributes->get('advertiser');
    }

    protected function membership(Request $request): AdvertiserMembership
    {
        return $request->attributes->get('advertiserMembership');
    }
}
