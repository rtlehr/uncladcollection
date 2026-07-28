<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerAccountNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $category,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $actionUrl = null,
        public readonly ?string $actionLabel = null,
        public readonly array $context = [],
        private readonly array $channels = ['database'],
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
        $mail = (new MailMessage)->subject($this->title)->greeting('Hello '.$notifiable->name.',')->line($this->message);
        if ($this->actionUrl) {
            $mail->action($this->actionLabel ?: 'View details', $this->actionUrl);
        }
        return $mail->line('You can manage optional email notifications from your Unclad Collection account.');
    }
}
