<?php
namespace Database\Factories; use App\Models\Advertiser; use Illuminate\Database\Eloquent\Factories\Factory;
class AdvertiserFactory extends Factory { protected $model=Advertiser::class; public function definition(): array { return ['uuid'=>(string) fake()->uuid(),'name'=>fake()->company(),'slug'=>fake()->unique()->slug(),'status'=>'active','billing_email'=>fake()->companyEmail(),'contact_name'=>fake()->name(),'contact_email'=>fake()->email()]; } }
