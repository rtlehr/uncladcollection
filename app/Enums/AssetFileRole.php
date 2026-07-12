<?php

namespace App\Enums;

enum AssetFileRole: string
{
    case Preview = 'preview';
    case Thumbnail = 'thumbnail';
    case Icon = 'icon';
    case Poster = 'poster';
    case Primary = 'primary';
    case HighResolution = 'high_resolution';
    case Print = 'print';
    case Vector = 'vector';
    case Video = 'video';
    case Source = 'source';
    case Bundle = 'bundle';
    case Supplemental = 'supplemental';
}
