<?php

namespace App\Notifications;

use App\Models\EmailDeliveryLog;
use App\Models\EmailTemplate;
use App\Models\SupportTicket;
use App\Services\Communications\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class SupportTicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ?int $deliveryLogId = null;

    public function __construct(public SupportTicket $ticket, public ?string $guestToken = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->guestToken
            ? route('support.guest.show', [$this->ticket, $this->guestToken])
            : route('support.show', $this->ticket);

        $email = $notifiable->email ?? $notifiable->routes['mail'] ?? $this->ticket->guest_email;
        $name = $notifiable->name ?? $this->ticket->guest_name ?? 'Customer';

        $rendered = app(EmailTemplateRenderer::class)->render('support.ticket_created', [
            'customer_name' => $name,
            'customer_email' => $email,
            'ticket_number' => $this->ticket->ticket_number,
            'ticket_subject' => $this->ticket->subject,
            'ticket_url' => $url,
        ]);

        $this->deliveryLogId = $this->createDeliveryLog($notifiable, (string) $email, $rendered->subject);

        return (new MailMessage)
            ->subject($rendered->subject)
            ->view(
                ['html' => 'emails.templated', 'text' => 'emails.templated-text'],
                ['template' => $rendered],
            );
    }

    private function createDeliveryLog(object $notifiable, string $email, string $subject): ?int
    {
        if (! Schema::hasTable('email_delivery_logs')) {
            return null;
        }

        $templateId = Schema::hasTable('email_templates')
            ? EmailTemplate::query()->where('key', 'support.ticket_created')->value('id')
            : null;

        return EmailDeliveryLog::query()->create([
            'email_template_id' => $templateId,
            'template_key' => 'support.ticket_created',
            'user_id' => method_exists($notifiable, 'getKey') ? $notifiable->getKey() : null,
            'recipient_email' => $email,
            'subject' => $subject,
            'status' => 'queued',
            'context' => ['ticket_id' => $this->ticket->id, 'ticket_number' => $this->ticket->ticket_number],
        ])->getKey();
    }
}
