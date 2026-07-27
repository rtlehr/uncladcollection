<?php
namespace Database\Seeders;
use App\Services\PublicPages\PublicPageTransferService; use Illuminate\Database\Seeder; use Illuminate\Support\Facades\File;
class PublicPageSeeder extends Seeder{public function run(PublicPageTransferService $t):void{$path=database_path('seeders/data/public-pages.json');if(File::exists($path))$t->importJson(File::get($path));}}
