<?php
namespace App\Console\Commands;
use App\Services\PublicPages\PublicPageTransferService; use Illuminate\Console\Command; use Illuminate\Support\Facades\File;
class ExportPublicPages extends Command{protected $signature='public-pages:export {--path=database/seeders/data/public-pages.json}';protected $description='Export Public Pages to the version-controlled JSON seed file.';public function handle(PublicPageTransferService $t):int{$path=base_path($this->option('path'));File::ensureDirectoryExists(dirname($path));File::put($path,$t->exportJson());$this->info('Exported Public Pages to '.$path);return self::SUCCESS;}}
