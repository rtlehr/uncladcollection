<?php

namespace App\Enums;

enum AssetFileScanStatus: string
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case NotRequired = 'not_required';
}
