<?php

namespace App\Enums;

enum AssetConfigurationDisplayType: string
{
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case ColorSwatch = 'color_swatch';
    case ImageSwatch = 'image_swatch';
    case Text = 'text';
    case Number = 'number';

    public function label(): string
    {
        return match ($this) {
            self::Select => 'Dropdown',
            self::Radio => 'Radio buttons',
            self::Checkbox => 'Checkboxes',
            self::ColorSwatch => 'Color swatches',
            self::ImageSwatch => 'Image swatches',
            self::Text => 'Text input',
            self::Number => 'Number input',
        };
    }

    public function usesValues(): bool
    {
        return ! in_array($this, [self::Text, self::Number], true);
    }
}
