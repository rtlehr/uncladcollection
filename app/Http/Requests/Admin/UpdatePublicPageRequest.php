<?php

namespace App\Http\Requests\Admin;

use App\Models\PublicPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePublicPageRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('update', $this->route('publicPage')) === true; }

    public function rules(): array
    {
        $page = $this->route('publicPage');
        return [
            'title' => ['required','string','max:255'],
            'slug' => ['required','alpha_dash','max:255', Rule::unique('public_pages','slug')->ignore($page), Rule::notIn(config('public-pages.reserved_slugs', []))],
            'eyebrow' => ['nullable','string','max:120'],
            'introduction' => ['nullable','string','max:3000'],
            'content' => ['nullable','string'],
            'page_type' => ['required', Rule::in(array_keys(config('public-pages.types', [])))],
            'status' => ['required', Rule::in([PublicPage::STATUS_DRAFT, PublicPage::STATUS_PUBLISHED])],
            'published_at' => ['nullable','date'],
            'is_active' => ['boolean'],
            'navigation_label' => ['nullable','string','max:120'],
            'navigation_locations' => ['nullable','array'],
            'navigation_locations.*' => [Rule::in(array_keys(config('public-pages.navigation_locations', [])))],
            'sort_order' => ['required','integer','min:0','max:9999'],
            'seo_title' => ['nullable','string','max:255'],
            'seo_description' => ['nullable','string','max:500'],
            'canonical_url' => ['nullable','url','max:2048'],
        ];
    }
}
