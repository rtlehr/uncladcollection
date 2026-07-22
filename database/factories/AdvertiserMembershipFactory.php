<?php
namespace Database\Factories;
use App\Models\{Advertiser,AdvertiserMembership,User}; use Illuminate\Database\Eloquent\Factories\Factory;
class AdvertiserMembershipFactory extends Factory { protected $model=AdvertiserMembership::class; public function definition():array{return ['advertiser_id'=>Advertiser::factory(),'user_id'=>User::factory(),'role'=>'report_viewer','is_primary'=>false,'is_active'=>true,'invited_at'=>now(),'accepted_at'=>now()];}}
