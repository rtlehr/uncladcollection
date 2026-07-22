<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertiser;
use App\Models\AdvertiserMembership;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdvertiserMembershipController extends Controller
{
    public function store(Request $request, Advertiser $advertiser)
    {
        $data=$request->validate(['user_id'=>['required',Rule::exists('users','id')],'role'=>['required',Rule::in(AdvertiserMembership::ROLES)],'is_primary'=>'boolean']);
        if($data['is_primary']??false)$advertiser->memberships()->update(['is_primary'=>false]);
        $advertiser->memberships()->updateOrCreate(['user_id'=>$data['user_id']],array_merge($data,['is_active'=>true,'invited_at'=>now(),'accepted_at'=>now(),'invited_by'=>$request->user()->id]));
        return back()->with('success','Advertiser portal member added.');
    }
    public function update(Request $request, Advertiser $advertiser, AdvertiserMembership $membership)
    {
        abort_unless($membership->advertiser_id===$advertiser->id,404);$data=$request->validate(['role'=>['required',Rule::in(AdvertiserMembership::ROLES)],'is_active'=>'boolean','is_primary'=>'boolean']);if($data['is_primary']??false)$advertiser->memberships()->whereKeyNot($membership->id)->update(['is_primary'=>false]);$membership->update($data);return back()->with('success','Advertiser portal member updated.');
    }
    public function destroy(Advertiser $advertiser, AdvertiserMembership $membership){abort_unless($membership->advertiser_id===$advertiser->id,404);abort_if($membership->is_primary,422,'The primary advertiser member cannot be removed.');$membership->delete();return back()->with('success','Advertiser portal member removed.');}
}
