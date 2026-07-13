<?php

namespace App\Enums;

enum AssetType: string
{
    case Image = 'image';
    case Vector = 'vector';
    case Video = 'video';
    case Document = 'document';
    case Bundle = 'bundle';
    case Template = 'template';
    case MixedMedia = 'mixed_media';
    case Product = 'product';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Vector => 'Vector',
            self::Video => 'Video',
            self::Document => 'Document',
            self::Bundle => 'Bundle',
            self::Template => 'Template',
            self::MixedMedia => 'Mixed Media',
            self::Product => 'Product',
        };
    }
}