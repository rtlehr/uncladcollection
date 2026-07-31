<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('communications.templates', []) as $key => $definition) {
            EmailTemplate::query()->firstOrCreate(
                ['key' => $key],
                [
                    'name' => $definition['name'],
                    'category' => $definition['category'],
                    'description' => $definition['description'] ?? null,
                    'subject' => $definition['subject'],
                    'preview_text' => $definition['preview_text'] ?? null,
                    'body_html' => $definition['body_html'],
                    'body_text' => $definition['body_text'] ?? null,
                    'variables' => $definition['variables'] ?? [],
                    'required_variables' => $definition['required_variables'] ?? [],
                    'is_transactional' => (bool) ($definition['transactional'] ?? false),
                    'is_active' => true,
                    'is_system' => true,
                ],
            );
        }
    }
}
