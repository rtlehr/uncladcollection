<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\Notifications\CustomerNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class OrderObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private readonly CustomerNotificationService $notifications) {}

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) return;
        $order->loadMissing('user');
        if (! $order->user) return;

        $details = match ($order->status) {
            Order::STATUS_PAID => ['Order paid', "Your order {$order->order_number} has been paid and is being prepared."],
            Order::STATUS_FAILED => ['Payment unsuccessful', "Payment for order {$order->order_number} was unsuccessful. Your card was not charged by Unclad Collection."],
            Order::STATUS_CANCELED => ['Order canceled', "Order {$order->order_number} was canceled."],
            Order::STATUS_REFUNDED => ['Order refunded', "Order {$order->order_number} has been refunded."],
            Order::STATUS_PARTIALLY_REFUNDED => ['Order partially refunded', "A partial refund was issued for order {$order->order_number}."],
            default => null,
        };

        if (! $details) return;
        $this->notifications->send($order->user, 'orders', $details[0], $details[1], route('account.library.index'), 'View My Library', ['order_id' => $order->id, 'status' => $order->status]);
    }
}
