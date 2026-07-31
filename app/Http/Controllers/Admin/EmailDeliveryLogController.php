<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RenderedTemplateMail;
use App\Models\EmailDeliveryLog;
use App\Services\Communications\EmailTemplateRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class EmailDeliveryLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:queued,pending,sent,failed'],
            'template' => ['nullable', 'string', 'max:160'],
        ]);

        $logs = EmailDeliveryLog::query()
            ->with(['template:id,name,key', 'user:id,name,email'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('recipient_email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('template_key', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['template'] ?? null, fn ($query, string $key) => $query->where('template_key', $key))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('Admin/Communications/DeliveryActivity/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'templateOptions' => EmailDeliveryLog::query()->select('template_key')->distinct()->orderBy('template_key')->pluck('template_key'),
            'summary' => [
                'sent' => EmailDeliveryLog::query()->where('status', 'sent')->count(),
                'failed' => EmailDeliveryLog::query()->where('status', 'failed')->count(),
                'queued' => EmailDeliveryLog::query()->whereIn('status', ['queued', 'pending'])->count(),
            ],
        ]);
    }

    public function retry(EmailDeliveryLog $emailDeliveryLog, EmailTemplateRenderer $renderer): RedirectResponse
    {
        abort_unless($emailDeliveryLog->status === 'failed', 422, 'Only failed deliveries can be retried.');

        $data = data_get($emailDeliveryLog->context, 'template_data');
        abort_unless(is_array($data), 422, 'This older delivery does not contain enough template data to retry safely.');

        $retry = EmailDeliveryLog::query()->create([
            'retried_from_id' => $emailDeliveryLog->id,
            'email_template_id' => $emailDeliveryLog->email_template_id,
            'template_key' => $emailDeliveryLog->template_key,
            'user_id' => $emailDeliveryLog->user_id,
            'recipient_email' => $emailDeliveryLog->recipient_email,
            'subject' => $emailDeliveryLog->subject,
            'status' => 'pending',
            'retry_count' => $emailDeliveryLog->retry_count + 1,
            'context' => [...($emailDeliveryLog->context ?? []), 'retried_by_user_id' => request()->user()?->id],
        ]);

        try {
            $rendered = $renderer->render($retry->template_key, $data);
            Mail::to($retry->recipient_email)->send(new RenderedTemplateMail($rendered));
            $retry->update(['subject' => $rendered->subject, 'status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $retry->update([
                'status' => 'failed',
                'failure_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);
            report($exception);

            return back()->withErrors(['retry' => 'The email failed again. Review the failure details and mail configuration.']);
        }

        return back()->with('success', 'Email retried successfully.');
    }
}
