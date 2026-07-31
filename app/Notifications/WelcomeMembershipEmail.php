<?php

namespace App\Notifications;

use App\Models\EmailDeliveryLog;
use App\Models\EmailTemplate;
use App\Services\Communications\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class WelcomeMembershipEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public ?int $deliveryLogId = null;

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rendered = app(EmailTemplateRenderer::class)->render('account.welcome', [
            'customer_name' => $notifiable->name,
            'customer_email' => $notifiable->email,
            'account_url' => route('account.index'),
        ]);

        $this->deliveryLogId = $this->createDeliveryLog($notifiable, $rendered->subject);

        return (new MailMessage)
            ->subject($rendered->subject)
            ->view(
                ['html' => 'emails.templated', 'text' => 'emails.templated-text'],
                ['template' => $rendered],
            );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => 'account',
            'title' => 'Your membership is ready',
            'message' => 'Your email address has been confirmed and your Unclad Collection membership is active.',
            'action_url' => route('account.index'),
            'action_label' => 'Visit my account',
            'context' => ['event' => 'email_verified'],
        ];
    }

    private function createDeliveryLog(object $notifiable, string $subject): ?int
    {
        if (! Schema::hasTable('email_delivery_logs')) {
            return null;
        }

        $templateId = Schema::hasTable('email_templates')
            ? EmailTemplate::query()->where('key', 'account.welcome')->value('id')
            : null;

        return EmailDeliveryLog::query()->create([
            'email_template_id' => $templateId,
            'template_key' => 'account.welcome',
            'user_id' => $notifiable->getKey(),
            'recipient_email' => $notifiable->email,
            'subject' => $subject,
            'status' => 'queued',
            'context' => ['purpose' => 'membership_welcome'],
        ])->getKey();
    }
}
