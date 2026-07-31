<?php

namespace App\Listeners;

use App\Models\EmailDeliveryLog;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

class UpdateEmailDeliveryLog
{
    public function handle(NotificationSent|NotificationFailed $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $logId = $event->notification->deliveryLogId ?? null;

        if (! $logId) {
            return;
        }

        $log = EmailDeliveryLog::query()->find($logId);

        if (! $log) {
            return;
        }

        if ($event instanceof NotificationSent) {
            $messageId = method_exists($event->response, 'getMessageId')
                ? $event->response->getMessageId()
                : null;

            $log->forceFill([
                'status' => 'sent',
                'message_id' => $messageId,
                'sent_at' => now(),
                'failure_message' => null,
                'failed_at' => null,
            ])->save();

            return;
        }

        $log->forceFill([
            'status' => 'failed',
            'failure_message' => $event->data['exception']?->getMessage()
                ?? 'The mail notification failed.',
            'failed_at' => now(),
        ])->save();
    }
}
