<?php

namespace App\Notifications;

use App\Models\EmailDeliveryLog;
use App\Models\EmailTemplate;
use App\Services\Communications\CommunicationPreferenceService;
use App\Services\Communications\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class CustomerAccountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ?int $deliveryLogId = null;

    public function __construct(
        public readonly string $category,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $actionUrl = null,
        public readonly ?string $actionLabel = null,
        public readonly array $context = [],
        private readonly array $channels = ['database'],
        public readonly ?string $emailTemplateKey = null,
        public readonly array $emailTemplateData = [],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'context' => $this->context,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $unsubscribeUrl = app(CommunicationPreferenceService::class)->unsubscribeUrl($notifiable, $this->category);
        if ($this->emailTemplateKey) {
            $rendered = app(EmailTemplateRenderer::class)->render($this->emailTemplateKey, [
                'customer_name' => $notifiable->name,
                'customer_email' => $notifiable->email,
                'action_url' => $this->actionUrl,
                'action_label' => $this->actionLabel ?: 'View details',
                ...$this->emailTemplateData,
            ]);

            $this->deliveryLogId = $this->createDeliveryLog($notifiable, $rendered->subject);

            return (new MailMessage)
                ->subject($rendered->subject)
                ->view(
                    ['html' => 'emails.templated', 'text' => 'emails.templated-text'],
                    ['template' => $rendered, 'unsubscribeUrl' => $unsubscribeUrl],
                );
        }

        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting('Hello '.$notifiable->name.'.')
            ->line($this->message);

        if ($this->actionUrl) {
            $mail->action($this->actionLabel ?: 'View details', $this->actionUrl);
        }

        if ($unsubscribeUrl) {
            $mail->line('This is an optional email. Unsubscribe from this category: '.$unsubscribeUrl);
        }

        return $mail->line('You can manage your communication preferences from your Unclad Collection account.');
    }

    private function createDeliveryLog(object $notifiable, string $subject): ?int
    {
        if (! Schema::hasTable('email_delivery_logs')) {
            return null;
        }

        $templateId = Schema::hasTable('email_templates')
            ? EmailTemplate::query()->where('key', $this->emailTemplateKey)->value('id')
            : null;

        return EmailDeliveryLog::query()->create([
            'email_template_id' => $templateId,
            'template_key' => $this->emailTemplateKey,
            'user_id' => $notifiable->getKey(),
            'recipient_email' => $notifiable->email,
            'subject' => $subject,
            'status' => 'queued',
            'context' => [
                'category' => $this->category,
                ...$this->context,
                'template_data' => [
                    'customer_name' => $notifiable->name,
                    'customer_email' => $notifiable->email,
                    'action_url' => $this->actionUrl,
                    'action_label' => $this->actionLabel ?: 'View details',
                    ...$this->emailTemplateData,
                ],
            ],
        ])->getKey();
    }
}
