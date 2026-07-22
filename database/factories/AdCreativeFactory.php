<?php
namespace Database\Factories;
use App\Models\{AdCreative,AdvertisingCampaign}; use Illuminate\Database\Eloquent\Factories\Factory; use Illuminate\Support\Str;
class AdCreativeFactory extends Factory { protected $model=AdCreative::class; public function definition():array{return['uuid'=>(string)Str::uuid(),'advertising_campaign_id'=>AdvertisingCampaign::factory(),'name'=>fake()->words(3,true),'creative_type'=>'image','status'=>'draft','media_path'=>'advertising/test/rendered/creative.jpg','original_media_path'=>'advertising/test/original/source.jpg','mime_type'=>'image/jpeg','file_size'=>12000,'width'=>1200,'height'=>628,'headline'=>fake()->sentence(),'destination_url'=>'/images','alt_text'=>fake()->sentence()];}}
