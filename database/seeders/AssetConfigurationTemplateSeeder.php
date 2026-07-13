<?php

namespace Database\Seeders;

use App\Enums\AssetConfigurationDisplayType;
use App\Models\AssetConfigurationTemplate;
use Illuminate\Database\Seeder;

class AssetConfigurationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['name' => 'Apparel Size', 'code' => 'apparel_size', 'display_type' => AssetConfigurationDisplayType::Select, 'values' => [['Small','small'],['Medium','medium'],['Large','large'],['XL','xl'],['2XL','2xl']]],
            ['name' => 'Standard Colors', 'code' => 'standard_colors', 'display_type' => AssetConfigurationDisplayType::ColorSwatch, 'values' => [['Black','black','#000000'],['White','white','#ffffff'],['Navy','navy','#1e3a5f'],['Red','red','#dc2626']]],
            ['name' => 'Print Size', 'code' => 'print_size', 'display_type' => AssetConfigurationDisplayType::Radio, 'values' => [['8 × 10','8x10'],['11 × 17','11x17'],['18 × 24','18x24'],['24 × 36','24x36']]],
            ['name' => 'Video Resolution', 'code' => 'video_resolution', 'display_type' => AssetConfigurationDisplayType::Radio, 'values' => [['1080p','1080p'],['4K UHD','4k'],['8K','8k']]],
        ];

        foreach ($templates as $sort => $data) {
            $template = AssetConfigurationTemplate::updateOrCreate(['code' => $data['code']], [
                'name' => $data['name'],
                'display_type' => $data['display_type'],
                'sort_order' => ($sort + 1) * 10,
                'is_active' => true,
            ]);
            $template->values()->forceDelete();
            foreach ($data['values'] as $index => $value) {
                $template->values()->create([
                    'label' => $value[0], 'value' => $value[1], 'swatch_color' => $value[2] ?? null,
                    'sort_order' => ($index + 1) * 10, 'is_active' => true,
                ]);
            }
        }
    }
}
