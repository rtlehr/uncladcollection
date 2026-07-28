<?php

namespace App\Services\Notifications;

class NotificationCategoryRegistry
{
    /** @return array<string,array{label:string,description:string,transactional:bool,default_email:bool}> */
    public function all(): array
    {
        return [
            'orders' => ['label' => 'Orders and payments', 'description' => 'Payment confirmations, failures, cancellations, and refunds.', 'transactional' => true, 'default_email' => true],
            'fulfillment' => ['label' => 'Order fulfillment', 'description' => 'Download readiness, shipping, tracking, and delivery updates.', 'transactional' => true, 'default_email' => true],
            'licenses' => ['label' => 'License updates', 'description' => 'Expiration, revocation, refund, and important license changes.', 'transactional' => true, 'default_email' => true],
            'downloads' => ['label' => 'Download access', 'description' => 'Download limits, package readiness, and file availability.', 'transactional' => true, 'default_email' => true],
            'security' => ['label' => 'Account security', 'description' => 'Important sign-in and account protection notices.', 'transactional' => true, 'default_email' => true],
            'discovery' => ['label' => 'Recommendations and new assets', 'description' => 'Optional recommendations and assets matching your interests.', 'transactional' => false, 'default_email' => false],
            'wish_lists' => ['label' => 'Wish-list changes', 'description' => 'Optional price and availability changes for saved assets.', 'transactional' => false, 'default_email' => false],
        ];
    }

    public function get(string $category): array
    {
        return $this->all()[$category] ?? ['label' => ucfirst(str_replace('_', ' ', $category)), 'description' => '', 'transactional' => false, 'default_email' => false];
    }
}
