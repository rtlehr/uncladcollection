<?php

namespace App\Enums;

enum AssetStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Review = 'review';
    case Published = 'published';
    case Archived = 'archived';
}
