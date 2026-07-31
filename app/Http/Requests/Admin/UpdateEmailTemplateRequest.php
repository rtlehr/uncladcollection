<?php

namespace App\Http\Requests\Admin;

use App\Services\Communications\EmailTemplateRenderer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_communications') === true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'preview_text' => ['nullable', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'body_text' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $template = $this->route('emailTemplate');
            $required = $template?->required_variables ?? [];
            $renderer = app(EmailTemplateRenderer::class);
            $combined = collect(['subject', 'preview_text', 'body_html', 'body_text'])
                ->map(fn (string $field): string => (string) $this->input($field, ''))
                ->implode("\n");
            $present = $renderer->variablesIn($combined);

            foreach ($required as $variable) {
                if (! in_array($variable, $present, true)) {
                    $validator->errors()->add('body_html', "The required variable {{ {$variable} }} must remain in this template.");
                }
            }
        }];
    }
}
