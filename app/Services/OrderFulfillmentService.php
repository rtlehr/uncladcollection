<?php

namespace App\Services;

use App\Enums\OrderFulfillmentStatus;
use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderFulfillmentService
{
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

            return $order->refresh();
        });
    }
}
