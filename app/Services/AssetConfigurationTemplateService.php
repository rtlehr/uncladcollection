<?php

namespace App\Services;

use App\Models\AssetConfigurationTemplate;
use Illuminate\Support\Str;

class AssetConfigurationTemplateService
{
    public function toAssetGroup(AssetConfigurationTemplate $template): array
    {
        $template->loadMissing('activeValues');

        return [
            'id' => null,
            'name' => $template->name,
            'code' => $template->code,
            'display_type' => $template->display_type->value,
            'is_required' => $template->is_required_default,
            'allows_multiple' => $template->allows_multiple_default,
            'placeholder' => $template->placeholder,
            'help_text' => $template->help_text,
            'minimum_value' => $template->minimum_value,
            'maximum_value' => $template->maximum_value,
            'step_value' => $template->step_value,
            'is_active' => true,
            'values' => $template->activeValues->map(fn ($value) => [
                'id' => null,
                'label' => $value->label,
                'value' => $value->value,
                'description' => $value->description,
                'swatch_color' => $value->swatch_color,
                'image_path' => $value->image_path,
                'is_default' => $value->is_default,
                'is_active' => true,
                'price_adjustment_cents' => $value->price_adjustment_cents,
                'currency' => $value->currency,
            ])->values()->all(),
        ];
    }

    public function uniqueCode(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source, '_') ?: 'configuration_template';
        $code = $base;
        $counter = 2;

        while (AssetConfigurationTemplate::withTrashed()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('code', $code)
            ->exists()) {
            $code = $base.'_'.$counter++;
        }

        return $code;
    }
}
