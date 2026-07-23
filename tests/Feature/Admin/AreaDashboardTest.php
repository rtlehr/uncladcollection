<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AreaDashboardTest extends TestCase
{
    public function test_area_dashboard_routes_are_registered_with_admin_middleware(): void
    {
        foreach ([
            'admin.dashboards.assets',
            'admin.dashboards.blog',
            'admin.dashboards.advertising',
            'admin.dashboards.marketing',
            'admin.dashboards.administration',
        ] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] should exist.");
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains('verified', $route->gatherMiddleware());
            $this->assertContains('permission:view_admin', $route->gatherMiddleware());
        }
    }
}
