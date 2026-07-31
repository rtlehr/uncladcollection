<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendTestEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('manage_communications') === true;
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email:rfc', 'max:255']];
    }
}
