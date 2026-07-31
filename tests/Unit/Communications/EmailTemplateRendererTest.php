<?php

use App\Services\Communications\EmailTemplateRenderer;

it('renders a code provided email template with variables', function () {
    $rendered = app(EmailTemplateRenderer::class)->render('account.welcome', [
        'customer_name' => 'Ross',
        'customer_email' => 'ross@example.com',
        'account_url' => 'https://example.com/account',
    ]);

    expect($rendered->subject)->toBe('Welcome to Unclad Collection')
        ->and($rendered->html)->toContain('Ross')
        ->and($rendered->html)->toContain('https://example.com/account');
});

it('rejects a template missing a required variable', function () {
    app(EmailTemplateRenderer::class)->render('account.verify_email', [
        'customer_name' => 'Ross',
        'expiration_minutes' => 60,
    ]);
})->throws(InvalidArgumentException::class, 'requires [verification_url]');
