<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTestEmailTemplateRequest;
use App\Http\Requests\Admin\UpdateEmailTemplateRequest;
use App\Mail\RenderedTemplateMail;
use App\Models\EmailDeliveryLog;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateRevision;
use App\Services\Communications\EmailTemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\CommunicationSetting;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EmailTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'in:active,disabled'],
        ]);

        return Inertia::render('Admin/Communications/EmailTemplates/Index', [
            'templates' => EmailTemplate::query()
                ->with('updatedBy:id,name')
                ->when($filters['search'] ?? null, function ($query, string $search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('key', 'like', "%{$search}%")
                            ->orWhere('subject', 'like', "%{$search}%");
                    });
                })
                ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
                ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
                ->when(($filters['status'] ?? null) === 'disabled', fn ($query) => $query->where('is_active', false))
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
            'filters' => $filters,
            'categories' => EmailTemplate::query()->distinct()->orderBy('category')->pluck('category'),
            'deliverySummary' => [
                'sent' => EmailDeliveryLog::query()->where('status', 'sent')->count(),
                'failed' => EmailDeliveryLog::query()->where('status', 'failed')->count(),
                'queued' => EmailDeliveryLog::query()->whereIn('status', ['queued', 'pending'])->count(),
            ],
            'defaultTestRecipient' => CommunicationSetting::current()->default_test_recipient,
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): Response
    {
        return Inertia::render('Admin/Communications/EmailTemplates/Edit', [
            'template' => $emailTemplate->load(['updatedBy:id,name', 'revisions.createdBy:id,name']),
        ]);
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->saveRevision($emailTemplate, $request->user()?->id);

        $emailTemplate->update([
            ...$request->validated(),
            'updated_by_user_id' => $request->user()?->id,
        ]);

        return back()->with('success', 'Email template updated.');
    }

    public function restore(EmailTemplate $emailTemplate): RedirectResponse
    {
        $definitions = config('communications.templates', []);
        $definition = is_array($definitions) ? ($definitions[$emailTemplate->key] ?? null) : null;
        abort_unless(is_array($definition), 422, 'This template does not have a system default.');

        $this->saveRevision($emailTemplate, request()->user()?->id);
        $emailTemplate->update([
            'subject' => $definition['subject'],
            'preview_text' => $definition['preview_text'] ?? null,
            'body_html' => $definition['body_html'],
            'body_text' => $definition['body_text'] ?? null,
            'is_active' => true,
            'updated_by_user_id' => request()->user()?->id,
        ]);

        return back()->with('success', 'System default restored.');
    }

    public function sendTest(SendTestEmailTemplateRequest $request, EmailTemplate $emailTemplate, EmailTemplateRenderer $renderer): RedirectResponse
    {
        $data = collect($emailTemplate->variables ?? [])->mapWithKeys(
            fn (string $variable): array => [$variable => $this->sampleValue($variable)]
        )->all();

        $log = EmailDeliveryLog::query()->create([
            'email_template_id' => $emailTemplate->id,
            'template_key' => $emailTemplate->key,
            'user_id' => $request->user()?->id,
            'recipient_email' => $request->validated('email'),
            'subject' => $emailTemplate->subject,
            'status' => 'pending',
            'context' => ['test' => true],
        ]);

        try {
            $rendered = $renderer->render($emailTemplate->key, $data);
            Mail::to($request->validated('email'))->send(new RenderedTemplateMail($rendered));
            $log->update(['subject' => $rendered->subject, 'status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $log->update(['status' => 'failed', 'failure_message' => $exception->getMessage(), 'failed_at' => now()]);
            report($exception);

            return back()->withErrors(['email' => 'The test email could not be sent. Check the mail configuration and delivery log.']);
        }

        return back()->with('success', 'Test email sent to '.$request->validated('email').'.');
    }

    private function saveRevision(EmailTemplate $template, ?int $userId): void
    {
        $number = ((int) $template->revisions()->max('revision_number')) + 1;
        EmailTemplateRevision::query()->create([
            'email_template_id' => $template->id,
            'revision_number' => $number,
            'subject' => $template->subject,
            'preview_text' => $template->preview_text,
            'body_html' => $template->body_html,
            'body_text' => $template->body_text,
            'is_active' => $template->is_active,
            'created_by_user_id' => $userId,
        ]);
    }

    private function sampleValue(string $variable): string
    {
        return match ($variable) {
            'customer_name' => 'Sample Member',
            'customer_email' => 'member@example.com',
            'verification_url' => url('/email/verify/sample'),
            'expiration_minutes' => '60',
            'account_url' => url('/account'),
            'order_number' => 'UC-10001',
            'order_total' => '$49.00',
            'order_url' => url('/account/purchases'),
            'asset_title' => 'Sample Collection Asset',
            'license_name' => 'Commercial Use',
            'license_url' => url('/account/licenses'),
            'ticket_number' => 'UC-SUPPORT-1001',
            'reply_excerpt' => 'This is sample content from the support team.',
            'ticket_url' => url('/account/tickets'),
            default => 'Sample '.str($variable)->replace('_', ' ')->title(),
        };
    }
}
