<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public SupportTicket $ticket, public ?string $guestToken = null) {}
    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $this->guestToken
            ? route('support.guest.show', [$this->ticket, $this->guestToken])
            : route('support.show', $this->ticket);

        return (new MailMessage)
            ->subject("Support request {$this->ticket->ticket_number} received")
            ->greeting('We received your support request')
            ->line($this->ticket->subject)
            ->line('You can review updates and reply using the secure link below.')
            ->action('View support request', $url)
            ->line('Please keep this email for your records.');
    }
}
