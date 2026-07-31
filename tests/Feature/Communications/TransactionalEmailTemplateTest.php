<?php

use App\Models\EmailDeliveryLog;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\CustomerAccountNotification;
use App\Services\Communications\EmailTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the editable order confirmation template', function () {
    $rendered = app(EmailTemplateRenderer::class)->render('order.confirmed', [
        'customer_name' => 'Test Customer',
        'customer_email' => 'customer@example.com',
        'order_number' => 'UC-1001',
        'order_total' => '$19.99',
        'order_url' => 'https://example.com/account',
    ]);

    expect($rendered->subject)->toContain('UC-1001')
        ->and($rendered->html)->toContain('$19.99');
});

it('uses an editable template and creates a delivery log for account notifications', function () {
    $user = User::factory()->create();

    EmailTemplate::query()->create([
        'key' => 'order.status_updated',
        'name' => 'Order Status Update',
        'category' => 'Commerce',
        'subject' => 'Custom {{ order_number }} update',
        'body_html' => '<p>{{ order_message }}</p>',
        'body_text' => '{{ order_message }}',
        'variables' => ['order_number', 'order_message', 'order_url'],
        'required_variables' => ['order_number', 'order_message', 'order_url'],
        'is_transactional' => true,
        'is_active' => true,
        'is_system' => true,
    ]);

    $notification = new CustomerAccountNotification(
        category: 'orders',
        title: 'Order refunded',
        message: 'Your order was refunded.',
        actionUrl: 'https://example.com/account',
        channels: ['mail'],
        emailTemplateKey: 'order.status_updated',
        emailTemplateData: [
            'order_number' => 'UC-1002',
            'order_message' => 'Your order was refunded.',
            'order_url' => 'https://example.com/account',
        ],
    );

    $mail = $notification->toMail($user);

    expect($mail->subject)->toBe('Custom UC-1002 update')
        ->and($notification->deliveryLogId)->not->toBeNull();

    expect(EmailDeliveryLog::query()->where('template_key', 'order.status_updated')->exists())->toBeTrue();
});
