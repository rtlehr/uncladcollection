<?php

use Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->in('Feature', 'Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function something()
{
    // ..
}

function assertDedicatedTestDatabase(): void
{
    $databaseName = DB::connection()->getDatabaseName();

    expect(app()->environment())->toBe('testing')
        ->and(DB::connection()->getDriverName())->toBe('mysql')
        ->and($databaseName)->not->toBeEmpty()
        ->and(strtolower($databaseName))->toContain('test');
}