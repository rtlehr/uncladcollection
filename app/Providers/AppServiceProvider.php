<?php

namespace App\Providers;

use App\Contracts\AssetVirusScanner;
use App\Analytics\AnalyticsReportCache;
use App\Models\AnalyticsEvent;
use App\Models\PageHelp;
use App\Models\PublicPage;
use App\Models\User;
use App\Policies\PageHelpPolicy;
use App\Policies\PublicPagePolicy;
use App\Services\NullAssetVirusScanner;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);

        $this->app->bind(AssetVirusScanner::class, function ($app): AssetVirusScanner {
            $scanner = config('asset-media.virus_scanner', NullAssetVirusScanner::class);

            return $app->make($scanner);
        });
    }

    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureSupportRateLimits();

        AnalyticsEvent::created(fn () => app(AnalyticsReportCache::class)->flush());
        AnalyticsEvent::deleted(fn () => app(AnalyticsReportCache::class)->flush());

        Gate::policy(PageHelp::class, PageHelpPolicy::class);
        Gate::policy(PublicPage::class, PublicPagePolicy::class);

        Gate::before(function (User $user, string $ability) {
            return $user->hasPermission($ability) ? true : null;
        });
    }


    protected function configureSupportRateLimits(): void
    {
        RateLimiter::for('support-public', function (Request $request): Limit {
            return Limit::perMinute((int) config('support.rate_limits.public_submissions_per_minute', 6))
                ->by($request->ip());
        });

        RateLimiter::for('support-guest-reply', function (Request $request): Limit {
            return Limit::perMinute((int) config('support.rate_limits.guest_replies_per_minute', 12))
                ->by($request->ip());
        });

        RateLimiter::for('support-member-write', function (Request $request): Limit {
            return Limit::perMinute((int) config('support.rate_limits.member_writes_per_minute', 20))
                ->by((string) ($request->user()?->id ?? $request->ip()));
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
