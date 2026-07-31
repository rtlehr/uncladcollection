<?php

namespace App\Notifications;

use App\Models\EmailDeliveryLog;
use App\Models\EmailTemplate;
use App\Services\Communications\EmailTemplateRenderer;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class VerifyMembershipEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public ?int $deliveryLogId = null;

    public function toMail($notifiable)
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes($this->expirationMinutes()),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );

        $rendered = app(EmailTemplateRenderer::class)->render('account.verify_email', [
            'customer_name' => $notifiable->name,
            'customer_email' => $notifiable->getEmailForVerification(),
            'verification_url' => $verificationUrl,
            'expiration_minutes' => $this->expirationMinutes(),
        ]);

        $this->deliveryLogId = $this->createDeliveryLog(
            $notifiable,
            $rendered->subject,
            ['purpose' => 'membership_verification'],
        );

        return (new MailMessage)
            ->subject($rendered->subject)
            ->view(
                ['html' => 'emails.templated', 'text' => 'emails.templated-text'],
                ['template' => $rendered],
            );
    }

    private function expirationMinutes(): int
    {
        return (int) config('auth.verification.expire', 60);
    }

    private function createDeliveryLog(object $notifiable, string $subject, array $context): ?int
    {
        if (! Schema::hasTable('email_delivery_logs')) {
            return null;
        }

        $templateId = Schema::hasTable('email_templates')
            ? EmailTemplate::query()->where('key', 'account.verify_email')->value('id')
            : null;

        return EmailDeliveryLog::query()->create([
            'email_template_id' => $templateId,
            'template_key' => 'account.verify_email',
            'user_id' => $notifiable->getKey(),
            'recipient_email' => $notifiable->getEmailForVerification(),
            'subject' => $subject,
            'status' => 'queued',
            'context' => $context,
        ])->getKey();
    }
}
