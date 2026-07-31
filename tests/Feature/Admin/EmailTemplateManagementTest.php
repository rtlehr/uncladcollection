<?php

use App\Models\EmailTemplate;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(EmailTemplateSeeder::class);
});

function communicationsAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $permissions = collect([
        ['name' => 'view_admin', 'label' => 'View Admin Area'],
        ['name' => 'manage_communications', 'label' => 'Manage Communications'],
    ])->map(fn (array $item) => Permission::query()->firstOrCreate(
        ['name' => $item['name']],
        ['label' => $item['label'], 'group_name' => 'Administration', 'description' => $item['label'].'.'],
    ));
    $user->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());

    return $user;
}

it('allows an authorized administrator to view email templates', function () {
    $this->actingAs(communicationsAdmin())
        ->get('/admin/communications/email-templates')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Communications/EmailTemplates/Index')
            ->has('templates', EmailTemplate::query()->count()));
});

it('creates a revision when an email template is updated', function () {
    $template = EmailTemplate::query()->where('key', 'account.welcome')->firstOrFail();

    $this->actingAs(communicationsAdmin())
        ->put("/admin/communications/email-templates/{$template->id}", [
            'subject' => 'A new welcome subject',
            'preview_text' => 'Welcome preview',
            'body_html' => '<p>Hello {{ customer_name }}</p>',
            'body_text' => 'Hello {{ customer_name }}',
            'is_active' => true,
        ])
        ->assertRedirect();

    expect($template->fresh()->subject)->toBe('A new welcome subject')
        ->and($template->revisions()->count())->toBe(1);
});

it('prevents required variables from being removed', function () {
    $template = EmailTemplate::query()->where('key', 'account.verify_email')->firstOrFail();

    $this->actingAs(communicationsAdmin())
        ->put("/admin/communications/email-templates/{$template->id}", [
            'subject' => 'Verify your email',
            'preview_text' => 'Verify',
            'body_html' => '<p>No secure link here.</p>',
            'body_text' => 'No secure link here.',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('body_html');
});

it('can send a test email from an editable template', function () {
    Mail::fake();
    $template = EmailTemplate::query()->where('key', 'account.welcome')->firstOrFail();

    $this->actingAs(communicationsAdmin())
        ->post("/admin/communications/email-templates/{$template->id}/test", [
            'email' => 'owner@example.com',
        ])
        ->assertRedirect();

    Mail::assertSent(App\Mail\RenderedTemplateMail::class);
    $this->assertDatabaseHas('email_delivery_logs', [
        'template_key' => 'account.welcome',
        'recipient_email' => 'owner@example.com',
        'status' => 'sent',
    ]);
});
