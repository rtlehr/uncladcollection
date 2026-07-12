<?php

namespace App\Enums;

enum AssetType: string
{
    case Image = 'image';
    case Vector = 'vector';
    case Video = 'video';
    case MixedMedia = 'mixed_media';
    case Document = 'document';
    case Template = 'template';
    case Bundle = 'bundle';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Vector => 'Vector',
            self::Video => 'Video',
            self::MixedMedia => 'Mixed Media',
            self::Document => 'Document',
            self::Template => 'Template',
            self::Bundle => 'Bundle',
        };
    }
}
