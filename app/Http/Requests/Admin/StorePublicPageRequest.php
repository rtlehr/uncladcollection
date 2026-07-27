<?php

namespace App\Http\Requests\Admin;

use App\Models\PublicPage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicPageRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('create', PublicPage::class) === true; }

    public function rules(): array
    {

        return [
            'title' => ['required','string','max:255'],
            'slug' => ['required','alpha_dash','max:255', 'unique:public_pages,slug', Rule::notIn(config('public-pages.reserved_slugs', []))],
            'eyebrow' => ['nullable','string','max:120'], 'introduction' => ['nullable','string','max:3000'],
            'content' => ['nullable','string'], 'page_type' => ['required', Rule::in(array_keys(config('public-pages.types', [])))],
            'status' => ['required', Rule::in([PublicPage::STATUS_DRAFT, PublicPage::STATUS_PUBLISHED])],
            'published_at' => ['nullable','date'], 'is_active' => ['boolean'],
            'navigation_label' => ['nullable','string','max:120'], 'navigation_locations' => ['nullable','array'],
            'navigation_locations.*' => [Rule::in(array_keys(config('public-pages.navigation_locations', [])))],
            'sort_order' => ['required','integer','min:0','max:9999'], 'seo_title' => ['nullable','string','max:255'],
            'seo_description' => ['nullable','string','max:500'], 'canonical_url' => ['nullable','url','max:2048'],
            'header_image_original' => ['nullable','image','mimes:jpeg,jpg,png,webp','max:20480'],
            'header_image_rendered' => ['nullable','image','mimes:jpeg,jpg,png,webp','max:10240'],
            'header_image_edit' => ['nullable','array'], 'header_image_alt' => ['nullable','string','max:255'],
            'remove_header_image' => ['nullable','boolean'], 'legal_version' => ['nullable','string','max:40'],
            'effective_date' => ['nullable','date'], 'revised_date' => ['nullable','date'],
            'faq_items' => ['nullable','array','max:100'], 'faq_items.*.question' => ['required_with:faq_items','string','max:500'],
            'faq_items.*.answer' => ['required_with:faq_items','string','max:20000'],
            'faq_items.*.is_active' => ['nullable','boolean'], 'faq_items.*.sort_order' => ['nullable','integer','min:0','max:9999'],
        ];
    }
}
