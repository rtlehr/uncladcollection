<?php

namespace Database\Seeders;

use App\Enums\AssetConfigurationDisplayType;
use App\Models\AssetConfigurationTemplate;
use Illuminate\Database\Seeder;

class AssetConfigurationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $templateData) {
            $values = $templateData['values'];
            unset($templateData['values']);

            $template = AssetConfigurationTemplate::withTrashed()->updateOrCreate(
                ['code' => $templateData['code']],
                $templateData,
            );

            if ($template->trashed()) {
                $template->restore();
            }

            foreach ($values as $valueData) {
                $value = $template->values()
                    ->withTrashed()
                    ->updateOrCreate(
                        ['value' => $valueData['value']],
                        $valueData,
                    );

                if ($value->trashed()) {
                    $value->restore();
                }
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'name' => 'Apparel Size',
                'code' => 'apparel_size',
                'description' => 'Common apparel sizes for clothing and wearable products.',
                'display_type' => AssetConfigurationDisplayType::Select,
                'is_required_default' => true,
                'allows_multiple_default' => false,
                'placeholder' => 'Choose a size',
                'help_text' => 'Select the size that should be used for this product.',
                'sort_order' => 10,
                'is_active' => true,
                'values' => [
                    $this->value('Small', 'small', 10),
                    $this->value('Medium', 'medium', 20),
                    $this->value('Large', 'large', 30),
                    $this->value('XL', 'xl', 40),
                    $this->value('2XL', '2xl', 50),
                ],
            ],
            [
                'name' => 'Standard Colors',
                'code' => 'standard_colors',
                'description' => 'A reusable set of common product colors.',
                'display_type' => AssetConfigurationDisplayType::ColorSwatch,
                'is_required_default' => false,
                'allows_multiple_default' => false,
                'placeholder' => null,
                'help_text' => 'Choose the preferred product color.',
                'sort_order' => 20,
                'is_active' => true,
                'values' => [
                    $this->value('Black', 'black', 10, '#000000'),
                    $this->value('White', 'white', 20, '#ffffff'),
                    $this->value('Navy', 'navy', 30, '#1e3a5f'),
                    $this->value('Red', 'red', 40, '#dc2626'),
                ],
            ],
            [
                'name' => 'Print Size',
                'code' => 'print_size',
                'description' => 'Common finished sizes for prints and posters.',
                'display_type' => AssetConfigurationDisplayType::Radio,
                'is_required_default' => true,
                'allows_multiple_default' => false,
                'placeholder' => null,
                'help_text' => 'Choose the finished print dimensions.',
                'sort_order' => 30,
                'is_active' => true,
                'values' => [
                    $this->value('8 × 10', '8x10', 10),
                    $this->value('11 × 17', '11x17', 20),
                    $this->value('18 × 24', '18x24', 30),
                    $this->value('24 × 36', '24x36', 40),
                ],
            ],
            [
                'name' => 'Video Resolution',
                'code' => 'video_resolution',
                'description' => 'Common delivery resolutions for video assets.',
                'display_type' => AssetConfigurationDisplayType::Radio,
                'is_required_default' => true,
                'allows_multiple_default' => false,
                'placeholder' => null,
                'help_text' => 'Choose the video resolution to deliver.',
                'sort_order' => 40,
                'is_active' => true,
                'values' => [
                    $this->value('1080p', '1080p', 10),
                    $this->value('4K UHD', '4k', 20),
                    $this->value('8K', '8k', 30),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function value(
        string $label,
        string $value,
        int $sortOrder,
        ?string $swatchColor = null,
    ): array {
        return [
            'label' => $label,
            'value' => $value,
            'swatch_color' => $swatchColor,
            'price_adjustment_cents' => 0,
            'currency' => 'USD',
            'is_default' => false,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ];
    }
}
