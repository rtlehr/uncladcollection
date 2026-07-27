<?php
namespace Database\Factories;
use App\Models\PublicPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
class PublicPageFactory extends Factory
{
 protected $model=PublicPage::class;
 public function definition(): array { $title=fake()->unique()->sentence(3); return ['title'=>$title,'slug'=>Str::slug($title),'content'=>'<p>'.fake()->paragraph().'</p>','page_type'=>'standard','status'=>'draft','is_active'=>true,'sort_order'=>100]; }
 public function published(): static { return $this->state(fn()=>['status'=>'published','published_at'=>now(),'is_active'=>true]); }
}
