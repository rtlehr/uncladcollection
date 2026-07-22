<?php
namespace Database\Factories; use App\Models\AdPlacement; use Illuminate\Database\Eloquent\Factories\Factory;
class AdPlacementFactory extends Factory { protected $model=AdPlacement::class; public function definition(): array { return ['uuid'=>(string) fake()->uuid(),'name'=>fake()->words(3,true),'code'=>fake()->unique()->slug(2),'location'=>'homepage','format'=>'banner','max_active_campaigns'=>1,'base_price_cents'=>50000,'pricing_model'=>'flat','is_active'=>true]; } }
