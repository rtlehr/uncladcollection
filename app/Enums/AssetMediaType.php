<?php

namespace App\Enums;

enum AssetMediaType: string
{
    case Image = 'image';
    case Vector = 'vector';
    case Video = 'video';
    case Archive = 'archive';
    case Document = 'document';
    case Source = 'source';
    case Other = 'other';
}
