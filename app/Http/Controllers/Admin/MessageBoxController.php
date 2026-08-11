<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Mews\Purifier\Facades\Purifier;

class MessageBoxController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/MessageBoxes/Index', [
            'messages' => MessageBox::query()->withCount(['views','submissions'])->orderBy('priority')->latest()->get(),
        ]);
    }

    public function create(): Response { return Inertia::render('Admin/MessageBoxes/Create'); }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['uuid'] = (string) Str::uuid();
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store("message-boxes/{$data['uuid']}", 'public');
        MessageBox::create($data);
        return redirect()->route('admin.message-boxes.index')->with('success', 'Message created.');
    }

    public function edit(MessageBox $messageBox): Response
    {
        return Inertia::render('Admin/MessageBoxes/Edit', ['messageBox'=>$messageBox]);
    }

    public function update(Request $request, MessageBox $messageBox): RedirectResponse
    {
        $data = $this->validated($request);
        if ($request->boolean('remove_image')) {
            if ($messageBox->image_path) Storage::disk('public')->delete($messageBox->image_path);
            $data['image_path'] = null;
        } elseif ($request->hasFile('image')) {
            if ($messageBox->image_path) Storage::disk('public')->delete($messageBox->image_path);
            $data['image_path'] = $request->file('image')->store("message-boxes/{$messageBox->uuid}", 'public');
        }
        $messageBox->update($data);
        return redirect()->route('admin.message-boxes.index')->with('success', 'Message updated.');
    }

    public function destroy(MessageBox $messageBox): RedirectResponse
    {
        Storage::disk('public')->deleteDirectory("message-boxes/{$messageBox->uuid}");
        $messageBox->delete();
        return redirect()->route('admin.message-boxes.index')->with('success', 'Message deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name'=>['required','string','max:255'],'title'=>['nullable','string','max:255'],'body_html'=>['nullable','string','max:100000'],
            'image'=>['nullable','image','max:20480'],'remove_image'=>['nullable','boolean'],
            'presentation'=>['required',Rule::in(['modal','bottom_banner','top_banner'])],
            'trigger_type'=>['required',Rule::in(['auto','action'])],'trigger_key'=>['nullable','required_if:trigger_type,action','string','max:100','regex:/^[A-Za-z0-9_.:-]+$/'],
            'page_patterns_text'=>['nullable','string','max:10000'],'audience'=>['required',Rule::in(['all','guests','authenticated'])],
            'show_once'=>['nullable','boolean'],'is_dismissible'=>['nullable','boolean'],'is_active'=>['nullable','boolean'],'priority'=>['required','integer','min:0','max:10000'],
            'starts_at'=>['nullable','date'],'ends_at'=>['nullable','date','after_or_equal:starts_at'],
            'buttons'=>['nullable','array','max:3'],'buttons.*.label'=>['required_with:buttons','string','max:100'],'buttons.*.url'=>['nullable','string','max:1000'],'buttons.*.style'=>['nullable',Rule::in(['primary','secondary','outline'])],
            'form_fields'=>['nullable','array','max:10'],'form_fields.*.name'=>['required_with:form_fields','string','max:80','regex:/^[A-Za-z][A-Za-z0-9_]*$/'],'form_fields.*.label'=>['required_with:form_fields','string','max:120'],
            'form_fields.*.type'=>['required_with:form_fields',Rule::in(['text','email','textarea','select','checkbox'])],'form_fields.*.required'=>['nullable','boolean'],'form_fields.*.placeholder'=>['nullable','string','max:255'],'form_fields.*.options_text'=>['nullable','string','max:2000'],
            'form_submit_label'=>['nullable','string','max:100'],'form_success_message'=>['nullable','string','max:500'],
        ]);

        $data['body_html'] = Purifier::clean($data['body_html'] ?? '');
        $patterns = preg_split('/[\r\n,]+/', (string) ($data['page_patterns_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $data['page_patterns'] = array_values(array_unique(array_map('trim', $patterns ?: ['*'])));
        foreach ($data['form_fields'] ?? [] as &$field) {
            $field['required'] = filter_var($field['required'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $field['options'] = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', (string)($field['options_text'] ?? '')) ?: [])));
            unset($field['options_text']);
        }
        unset($field, $data['page_patterns_text'], $data['image'], $data['remove_image']);
        foreach (['show_once','is_dismissible','is_active'] as $key) $data[$key] = $request->boolean($key);
        if (($data['trigger_type'] ?? 'auto') === 'auto') $data['trigger_key'] = null;
        return $data;
    }
}
