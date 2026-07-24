<?php

namespace App\Enums;

enum SupportAttachmentScanStatus: string
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Rejected = 'rejected';
    case Failed = 'failed';
}
