<?php
namespace Database\Factories; use App\Models\{AdvertisingCampaign,Advertiser}; use Illuminate\Database\Eloquent\Factories\Factory; use Illuminate\Support\Str;
class AdvertisingCampaignFactory extends Factory { protected $model=AdvertisingCampaign::class; public function definition():array{return ['uuid'=>(string)fake()->uuid(),'advertiser_id'=>Advertiser::factory(),'name'=>fake()->catchPhrase(),'public_code'=>'AD-'.Str::upper(Str::random(8)),'status'=>'draft','objective'=>'awareness','pricing_model'=>'flat','budget_cents'=>100000,'contract_value_cents'=>100000];} }
