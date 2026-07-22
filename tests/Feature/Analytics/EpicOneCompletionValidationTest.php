<?php

namespace Tests\Feature\Analytics;

use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EpicOneCompletionValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_analytics_routes_exist_and_require_report_permission(): void
    {
        $routeNames = [
            'admin.analytics.index',
            'admin.analytics.financial', 'admin.analytics.financial.export',
            'admin.analytics.assets.index', 'admin.analytics.assets.export', 'admin.analytics.assets.show',
            'admin.analytics.customers.index', 'admin.analytics.customers.export', 'admin.analytics.customers.show',
            'admin.analytics.blog.index', 'admin.analytics.blog.export', 'admin.analytics.blog.show',
            'admin.analytics.campaigns.index', 'admin.analytics.campaigns.export', 'admin.analytics.campaigns.show',
            'admin.analytics.search.index', 'admin.analytics.search.export', 'admin.analytics.search.show',
            'admin.analytics.downloads.index', 'admin.analytics.downloads.export', 'admin.analytics.downloads.show',
            'admin.analytics.operations.index', 'admin.analytics.operations.export', 'admin.analytics.operations.show',
        ];

        foreach ($routeNames as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing analytics route [{$routeName}].");
            $middleware = Route::getRoutes()->getByName($routeName)?->gatherMiddleware() ?? [];
            $this->assertContains('auth', $middleware, "Route [{$routeName}] must require authentication.");
            $this->assertContains('verified', $middleware, "Route [{$routeName}] must require verified email.");
            $this->assertContains('permission:view_admin', $middleware, "Route [{$routeName}] must require admin access.");
            $this->assertContains('permission:view_reports', $middleware, "Route [{$routeName}] must require report access.");
        }
    }

    public function test_analytics_validation_command_passes_with_required_permissions(): void
    {
        foreach (['view_admin', 'view_reports'] as $name) {
            Permission::query()->create([
                'name' => $name,
                'label' => str($name)->replace('_', ' ')->title(),
                'description' => "Allows access to {$name}",
            ]);
        }

        $this->artisan('analytics:validate --strict')
            ->expectsOutputToContain('Analytics validation passed.')
            ->assertSuccessful();
    }
}
