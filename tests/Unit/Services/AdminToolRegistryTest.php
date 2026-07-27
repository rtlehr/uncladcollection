<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\AdminToolRegistry;
use Mockery;
use PHPUnit\Framework\TestCase;

class AdminToolRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_returns_only_tools_the_user_can_access(): void
    {
        $allowed = ['view_admin', 'manage_collections', 'view_reports'];
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')
            ->andReturnUsing(fn (string $permission): bool => in_array($permission, $allowed, true));

        $groups = (new AdminToolRegistry())->forUser($user);
        $tools = collect($groups)->flatMap(fn (array $group) => $group['tools']);

        $this->assertTrue($tools->contains(fn (array $tool) => $tool['id'] === 'collections'));
        $this->assertTrue($tools->contains(fn (array $tool) => $tool['id'] === 'featured-collections'));
        $this->assertTrue($tools->contains(fn (array $tool) => $tool['id'] === 'search-discovery'));
        $this->assertFalse($tools->contains(fn (array $tool) => $tool['id'] === 'users'));
        $this->assertFalse($tools->contains(fn (array $tool) => $tool['id'] === 'assets'));
    }

    public function test_it_returns_no_tools_without_admin_access(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasPermission')->with('view_admin')->andReturnFalse();

        $this->assertSame([], (new AdminToolRegistry())->forUser($user));
    }
}
