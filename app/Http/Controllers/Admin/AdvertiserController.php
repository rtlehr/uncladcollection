<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\{Advertiser,AdvertiserMembership,User}; use Illuminate\Http\Request; use Illuminate\Support\Str; use Inertia\Inertia;
class AdvertiserController {
 public function index(){return Inertia::render('Admin/Advertising/Advertisers/Index',['advertisers'=>Advertiser::withCount(['campaigns','memberships'])->orderBy('name')->get()]);}
 public function create(){return Inertia::render('Admin/Advertising/Advertisers/Form',['advertiser'=>null,'users'=>[],'membershipRoles'=>AdvertiserMembership::ROLES]);}
 public function store(Request $r){$d=$this->data($r);$d['uuid']=(string)Str::uuid();$d['slug']=Str::slug($d['name']).'-'.Str::lower(Str::random(5));$advertiser=Advertiser::create($d);return to_route('admin.advertisers.edit',$advertiser)->with('success','Advertiser created. Add portal members below.');}
 public function edit(Advertiser $advertiser){return Inertia::render('Admin/Advertising/Advertisers/Form',['advertiser'=>$advertiser->load('memberships.user'),'users'=>User::orderBy('name')->get(['id','name','email']),'membershipRoles'=>AdvertiserMembership::ROLES]);}
 public function update(Request $r,Advertiser $advertiser){$advertiser->update($this->data($r));return to_route('admin.advertisers.index')->with('success','Advertiser updated.');}
 public function destroy(Advertiser $advertiser){abort_if($advertiser->campaigns()->exists(),422,'Advertisers with campaigns cannot be deleted.');$advertiser->delete();return back()->with('success','Advertiser deleted.');}
 private function data(Request $r):array{return $r->validate(['name'=>'required|string|max:255','status'=>'required|in:active,inactive,prospect','website_url'=>'nullable|url|max:500','billing_email'=>'nullable|email','contact_name'=>'nullable|string|max:255','contact_email'=>'nullable|email','contact_phone'=>'nullable|string|max:50','billing_address'=>'nullable|string|max:2000','notes'=>'nullable|string|max:5000']);}
}
