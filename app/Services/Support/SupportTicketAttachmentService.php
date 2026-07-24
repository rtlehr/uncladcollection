<?php

namespace App\Services\Support;

use App\Enums\SupportAttachmentScanStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SupportTicketAttachmentService
{
    public function store(
        SupportTicket $ticket,
        UploadedFile $file,
        ?SupportTicketMessage $message = null,
        ?User $uploader = null,
        bool $customerVisible = true,
    ): SupportTicketAttachment {
        $this->validate($file);

        $uuid = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension());
        $path = sprintf('support-tickets/%s/%s.%s', $ticket->uuid, $uuid, $extension ?: 'bin');
        $disk = (string) config('support.attachments.disk', 'local');
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false || ! Storage::disk($disk)->put($path, $contents)) {
            throw ValidationException::withMessages(['attachment' => 'The support attachment could not be stored.']);
        }

        return $ticket->attachments()->create([
            'uuid' => $uuid,
            'support_ticket_message_id' => $message?->id,
            'uploaded_by' => $uploader?->id,
            'disk' => $disk,
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'extension' => $extension ?: null,
            'size_bytes' => $file->getSize(),
            'checksum_sha256' => hash('sha256', $contents),
            'scan_status' => SupportAttachmentScanStatus::Pending,
            'is_customer_visible' => $customerVisible,
        ]);
    }

    private function validate(UploadedFile $file): void
    {
        $maxKb = (int) config('support.attachments.max_kb', 10240);
        $allowed = (array) config('support.attachments.allowed_extensions', []);
        $extension = strtolower($file->getClientOriginalExtension());

        if (! $file->isValid() || $file->getSize() > ($maxKb * 1024) || ! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'attachment' => 'The attachment type or size is not allowed.',
            ]);
        }
    }
}
