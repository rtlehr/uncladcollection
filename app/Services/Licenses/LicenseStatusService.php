<?php

namespace App\Services\Licenses;

use App\Models\License;

class LicenseStatusService
{
    /** @return array{key:string,label:string,tone:string,can_download:bool,message:string,is_expiring_soon:bool,days_until_expiry:int|null} */
    public function describe(License $license): array
    {
        $days = $license->expires_at ? (int) now()->startOfDay()->diffInDays($license->expires_at->copy()->startOfDay(), false) : null;
        $expiredByDate = $days !== null && $days < 0;
        $limitReached = $license->download_limit !== null && $license->downloads_used >= $license->download_limit;
        $expiringSoon = $license->status === License::STATUS_ACTIVE && $days !== null && $days >= 0 && $days <= 30;

        if ($license->status === License::STATUS_REVOKED) {
            return $this->result('revoked', 'Revoked', 'danger', false, $license->status_reason ?: 'This license has been revoked. Contact support if you believe this is an error.', false, $days);
        }

        if ($license->status === License::STATUS_REFUNDED) {
            return $this->result('refunded', 'Refunded', 'warning', false, $license->status_reason ?: 'This purchase was refunded and download access is no longer available.', false, $days);
        }

        if ($license->status === License::STATUS_EXPIRED || $expiredByDate) {
            return $this->result('expired', 'Expired', 'warning', false, $license->status_reason ?: 'This license has expired. Your purchase record remains available for reference.', false, $days);
        }

        if ($limitReached) {
            return $this->result('limit_reached', 'Download limit reached', 'warning', false, 'The download allowance for this license has been used. Contact support if you need assistance.', $expiringSoon, $days);
        }

        if ($expiringSoon) {
            return $this->result('expiring_soon', 'Expiring soon', 'warning', true, "This license expires in {$days} day".($days === 1 ? '' : 's').'.', true, $days);
        }

        return $this->result('active', 'Active', 'success', true, 'This license is active and available according to its purchased terms.', false, $days);
    }

    private function result(string $key, string $label, string $tone, bool $canDownload, string $message, bool $isExpiringSoon, ?int $days): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'tone' => $tone,
            'can_download' => $canDownload,
            'message' => $message,
            'is_expiring_soon' => $isExpiringSoon,
            'days_until_expiry' => $days,
        ];
    }
}
