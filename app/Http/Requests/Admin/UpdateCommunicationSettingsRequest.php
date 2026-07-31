<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunicationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_communications') ?? false;
    }

    public function rules(): array
    {
        return [
            'sender_name' => ['nullable', 'string', 'max:120'],
            'sender_email' => ['nullable', 'email:rfc', 'max:255'],
            'reply_to_name' => ['nullable', 'string', 'max:120'],
            'reply_to_email' => ['nullable', 'email:rfc', 'max:255'],
            'default_test_recipient' => ['nullable', 'email:rfc', 'max:255'],
        ];
    }
}
