<?php

use App\Mail\RenderedTemplateMail;
use App\Models\EmailDeliveryLog;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;

it('filters email templates by category', function () {
    $this->seed(Database\Seeders\EmailTemplateSeeder::class);

    $this->actingAs(communicationsAdmin())
        ->get('/admin/communications/email-templates?category=Commerce')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Communications/EmailTemplates/Index')
            ->where('templates', fn ($templates) => collect($templates)->every(fn ($template) => $template['category'] === 'Commerce')));
});

it('shows email delivery activity to communications administrators', function () {
    EmailDeliveryLog::query()->create([
        'template_key' => 'account.welcome',
        'recipient_email' => 'member@example.com',
        'subject' => 'Welcome',
        'status' => 'failed',
        'failure_message' => 'SMTP unavailable',
    ]);

    $this->actingAs(communicationsAdmin())
        ->get('/admin/communications/delivery-activity')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Communications/DeliveryActivity/Index')
            ->has('logs.data', 1)
            ->where('summary.failed', 1));
});

it('updates communication sender settings', function () {
    $this->actingAs(communicationsAdmin())
        ->put('/admin/communications/settings', [
            'sender_name' => 'Unclad Collection',
            'sender_email' => 'hello@example.com',
            'reply_to_name' => 'Support',
            'reply_to_email' => 'support@example.com',
            'default_test_recipient' => 'owner@example.com',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('communication_settings', [
        'sender_email' => 'hello@example.com',
        'reply_to_email' => 'support@example.com',
    ]);
});

it('retries a failed delivery with retained template data', function () {
    Mail::fake();
    $this->seed(Database\Seeders\EmailTemplateSeeder::class);
    $template = EmailTemplate::query()->where('key', 'account.welcome')->firstOrFail();

    $log = EmailDeliveryLog::query()->create([
        'email_template_id' => $template->id,
        'template_key' => $template->key,
        'recipient_email' => 'member@example.com',
        'subject' => 'Welcome',
        'status' => 'failed',
        'failure_message' => 'Temporary failure',
        'context' => ['template_data' => [
            'customer_name' => 'Member',
            'customer_email' => 'member@example.com',
            'account_url' => url('/account'),
        ]],
    ]);

    $this->actingAs(communicationsAdmin())
        ->post("/admin/communications/delivery-activity/{$log->id}/retry")
        ->assertRedirect();

    Mail::assertSent(RenderedTemplateMail::class);
    $this->assertDatabaseHas('email_delivery_logs', [
        'retried_from_id' => $log->id,
        'status' => 'sent',
    ]);
});
