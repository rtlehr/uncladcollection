<?php

namespace App\Providers;

use App\Contracts\AssetVirusScanner;
use App\Analytics\AnalyticsReportCache;
use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Services\NullAssetVirusScanner;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AssetVirusScanner::class, function ($app): AssetVirusScanner {
            $scanner = config('asset-media.virus_scanner', NullAssetVirusScanner::class);

            return $app->make($scanner);
        });
    }

    public function boot(): void
    {
        $this->configureDefaults();

        AnalyticsEvent::created(fn () => app(AnalyticsReportCache::class)->flush());
        AnalyticsEvent::deleted(fn () => app(AnalyticsReportCache::class)->flush());

        Gate::before(function (User $user, string $ability) {
            return $user->hasPermission($ability) ? true : null;
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
