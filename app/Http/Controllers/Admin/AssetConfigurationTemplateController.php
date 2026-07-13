<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AssetConfigurationDisplayType;
use App\Http\Controllers\Controller;
use App\Models\AssetConfigurationTemplate;
use App\Services\AssetConfigurationTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AssetConfigurationTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('Admin/ConfigurationTemplates/Index', [
            'templates' => AssetConfigurationTemplate::query()
                ->withCount('values')
                ->when($search, fn ($query) => $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn ($template) => $this->formatTemplate($template)),
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/ConfigurationTemplates/Create', $this->formOptions());
    }

    public function store(Request $request, AssetConfigurationTemplateService $service): RedirectResponse
    {
        $validated = $this->validateTemplate($request);

        $template = DB::transaction(function () use ($validated, $service) {
            $template = AssetConfigurationTemplate::create([
                ...Arr::except($validated, ['values']),
                'code' => $service->uniqueCode($validated['code'] ?: $validated['name']),
            ]);
            $this->syncValues($template, $validated['values'] ?? []);
            return $template;
        });

        return redirect()->route('admin.configuration-templates.edit', $template)
            ->with('success', 'Configuration template created successfully.');
    }

    public function edit(AssetConfigurationTemplate $configurationTemplate): Response
    {
        $configurationTemplate->load('values');

        return Inertia::render('Admin/ConfigurationTemplates/Edit', [
            ...$this->formOptions(),
            'templateRecord' => $this->formatTemplate($configurationTemplate, true),
        ]);
    }

    public function update(Request $request, AssetConfigurationTemplate $configurationTemplate, AssetConfigurationTemplateService $service): RedirectResponse
    {
        $validated = $this->validateTemplate($request, $configurationTemplate);

        DB::transaction(function () use ($validated, $configurationTemplate, $service): void {
            $configurationTemplate->update([
                ...Arr::except($validated, ['values']),
                'code' => $service->uniqueCode($validated['code'] ?: $validated['name'], $configurationTemplate->id),
            ]);
            $configurationTemplate->values()->withTrashed()->forceDelete();
            $this->syncValues($configurationTemplate, $validated['values'] ?? []);
        });

        return back()->with('success', 'Configuration template updated successfully.');
    }

    public function destroy(AssetConfigurationTemplate $configurationTemplate): RedirectResponse
    {
        $configurationTemplate->delete();

        return redirect()->route('admin.configuration-templates.index')
            ->with('success', 'Configuration template removed from the library.');
    }

    private function syncValues(AssetConfigurationTemplate $template, array $values): void
    {
        foreach (array_values($values) as $index => $value) {
            $template->values()->create([
                ...$value,
                'sort_order' => ($index + 1) * 10,
                'currency' => strtoupper($value['currency'] ?? 'USD'),
            ]);
        }
    }

    private function validateTemplate(Request $request, ?AssetConfigurationTemplate $template = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255', Rule::unique('asset_configuration_templates', 'code')->ignore($template?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_type' => ['required', Rule::enum(AssetConfigurationDisplayType::class)],
            'is_required_default' => ['boolean'],
            'allows_multiple_default' => ['boolean'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'help_text' => ['nullable', 'string', 'max:2000'],
            'minimum_value' => ['nullable', 'numeric'],
            'maximum_value' => ['nullable', 'numeric'],
            'step_value' => ['nullable', 'numeric', 'gt:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'values' => ['nullable', 'array', 'max:100'],
            'values.*.label' => ['required_with:values', 'string', 'max:255'],
            'values.*.value' => ['required_with:values', 'string', 'max:255'],
            'values.*.description' => ['nullable', 'string', 'max:2000'],
            'values.*.swatch_color' => ['nullable', 'string', 'max:32'],
            'values.*.image_path' => ['nullable', 'string', 'max:1024'],
            'values.*.price_adjustment_cents' => ['nullable', 'integer', 'min:-100000000', 'max:100000000'],
            'values.*.currency' => ['nullable', 'string', 'size:3'],
            'values.*.is_default' => ['boolean'],
            'values.*.is_active' => ['boolean'],
        ]);
    }

    private function formOptions(): array
    {
        return [
            'displayTypes' => collect(AssetConfigurationDisplayType::cases())
                ->map(fn ($type) => ['value' => $type->value, 'label' => $type->label(), 'uses_values' => $type->usesValues()])
                ->values(),
        ];
    }

    private function formatTemplate(AssetConfigurationTemplate $template, bool $detailed = false): array
    {
        $data = [
            'id' => $template->id,
            'name' => $template->name,
            'code' => $template->code,
            'description' => $template->description,
            'display_type' => $template->display_type->value,
            'display_type_label' => $template->display_type->label(),
            'is_required_default' => $template->is_required_default,
            'allows_multiple_default' => $template->allows_multiple_default,
            'placeholder' => $template->placeholder,
            'help_text' => $template->help_text,
            'minimum_value' => $template->minimum_value,
            'maximum_value' => $template->maximum_value,
            'step_value' => $template->step_value,
            'sort_order' => $template->sort_order,
            'is_active' => $template->is_active,
            'values_count' => $template->values_count ?? $template->values()->count(),
        ];

        if ($detailed) {
            $data['values'] = $template->values->map(fn ($value) => [
                'id' => $value->id,
                'label' => $value->label,
                'value' => $value->value,
                'description' => $value->description,
                'swatch_color' => $value->swatch_color,
                'image_path' => $value->image_path,
                'price_adjustment_cents' => $value->price_adjustment_cents,
                'currency' => $value->currency,
                'is_default' => $value->is_default,
                'is_active' => $value->is_active,
            ])->values();
        }

        return $data;
    }
}
