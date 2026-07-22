<?php

namespace App\Http\Controllers\AdvertiserPortal;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountController extends PortalController
{
    public function edit(Request $request){return Inertia::render('Advertiser/Account/Edit',['advertiser'=>$this->advertiser($request)->load('memberships.user'),'membership'=>$this->membership($request)]);}
    public function update(Request $request){abort_unless($this->membership($request)->canManageAccount(),403);$data=$request->validate(['contact_name'=>'nullable|string|max:255','contact_email'=>'nullable|email|max:255','contact_phone'=>'nullable|string|max:100','billing_email'=>'nullable|email|max:255','website_url'=>'nullable|url|max:1000','billing_address'=>'nullable|string|max:3000']);$this->advertiser($request)->update($data);return back()->with('success','Advertiser account updated.');}
}
