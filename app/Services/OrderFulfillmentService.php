<?php

namespace App\Services;

use App\Enums\OrderFulfillmentStatus;
use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Services\Notifications\CustomerNotificationService;

class OrderFulfillmentService
{
    public function __construct(private readonly CustomerNotificationService $notifications) {}
    public function update(Order $order, array $data, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $data, $actor): Order {
            $status = OrderFulfillmentStatus::from($data['fulfillment_status']);
            $updates = [
                'fulfillment_status' => $status->value,
                'shipping_carrier' => $data['shipping_carrier'] ?? null,
                'tracking_number' => $data['tracking_number'] ?? null,
                'fulfillment_notes' => $data['fulfillment_notes'] ?? null,
            ];

            if ($status === OrderFulfillmentStatus::Shipped && ! $order->shipped_at) {
                $updates['shipped_at'] = now();
            }
            if ($status === OrderFulfillmentStatus::Delivered && ! $order->delivered_at) {
                $updates['delivered_at'] = now();
            }
            if ($status === OrderFulfillmentStatus::Fulfilled && ! $order->fulfilled_at) {
                $updates['fulfilled_at'] = now();
            }

            $order->update($updates);

            OrderFulfillmentEvent::create([
                'order_id' => $order->id,
                'user_id' => $actor?->id,
                'status' => $status->value,
                'note' => $data['event_note'] ?? null,
                'metadata' => [
                    'shipping_carrier' => $updates['shipping_carrier'],
                    'tracking_number' => $updates['tracking_number'],
                ],
                'created_at' => now(),
            ]);

            $updated = $order->refresh()->loadMissing('user');
            DB::afterCommit(function () use ($updated, $status): void {
                if (! $updated->user) return;
                $label = str($status->value)->replace('_', ' ')->title()->toString();
                $message = "Order {$updated->order_number} is now {$label}.";
                if ($updated->tracking_number) $message .= " Tracking: {$updated->tracking_number}.";
                $this->notifications->send($updated->user, 'fulfillment', "Order {$label}", $message, route('account.library.index'), 'View order', ['order_id' => $updated->id, 'status' => $status->value], 'order.fulfillment_updated', [
                    'order_number' => $updated->order_number,
                    'fulfillment_status' => $label,
                    'tracking_number' => $updated->tracking_number ?: 'Not available',
                    'order_url' => route('account.library.index'),
                ]);
            });
            return $updated;
        });
    }
}
