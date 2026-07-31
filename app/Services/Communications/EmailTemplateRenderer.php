<?php

namespace App\Services\Communications;

use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class EmailTemplateRenderer
{
    public function render(string $key, array $data = []): RenderedEmailTemplate
    {
        $definitions = config('communications.templates', []);
        $definition = is_array($definitions) ? ($definitions[$key] ?? null) : null;
        $template = Schema::hasTable('email_templates')
            ? EmailTemplate::query()->where('key', $key)->where('is_active', true)->first()
            : null;

        if (! $template && ! is_array($definition)) {
            throw new InvalidArgumentException("Unknown email template [{$key}].");
        }

        $subject = $template?->subject ?? $definition['subject'];
        $previewText = $template?->preview_text ?? ($definition['preview_text'] ?? null);
        $html = $template?->body_html ?? $definition['body_html'];
        $text = $template?->body_text ?? ($definition['body_text'] ?? strip_tags($html));
        $required = $template?->required_variables ?? ($definition['required_variables'] ?? []);

        foreach ($required as $variable) {
            if (! array_key_exists($variable, $data) || blank($data[$variable])) {
                throw new InvalidArgumentException("Email template [{$key}] requires [{$variable}].");
            }
        }

        return new RenderedEmailTemplate(
            key: $key,
            subject: $this->replace($subject, $data, false),
            previewText: $previewText ? $this->replace($previewText, $data, false) : null,
            html: $this->replace($html, $data, true),
            text: $this->replace($text, $data, false),
        );
    }

    public function variablesIn(string $content): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', $content, $matches);

        return collect($matches[1] ?? [])->unique()->values()->all();
    }

    private function replace(string $content, array $data, bool $escape): string
    {
        return preg_replace_callback('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', function (array $match) use ($data, $escape): string {
            $value = data_get($data, $match[1], '');
            $value = is_scalar($value) ? (string) $value : '';

            return $escape ? e($value) : $value;
        }, $content) ?? $content;
    }
}
