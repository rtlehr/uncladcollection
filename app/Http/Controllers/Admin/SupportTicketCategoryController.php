<?php
namespace App\Http\Controllers\Admin;

use App\Enums\SupportTicketPriority;
use App\Http\Controllers\Controller;
use App\Models\SupportTicketCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Support/Categories/Index', [
            'categories' => SupportTicketCategory::query()->with('defaultAssignee:id,name')->withCount('tickets')->orderBy('sort_order')->orderBy('name')->get(),
            'staff' => User::query()->orderBy('name')->get(['id','name']),
            'priorities' => array_map(fn ($e) => $e->value, SupportTicketPriority::cases()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);
        SupportTicketCategory::create($data);
        return back()->with('success', 'Support category created.');
    }

    public function update(Request $request, SupportTicketCategory $supportCategory): RedirectResponse
    {
        $data = $this->validated($request, $supportCategory);
        $data['slug'] = Str::slug($data['name']);
        $supportCategory->update($data);
        return back()->with('success', 'Support category updated.');
    }

    public function destroy(SupportTicketCategory $supportCategory): RedirectResponse
    {
        abort_if($supportCategory->tickets()->exists(), 422, 'Categories with tickets cannot be deleted.');
        $supportCategory->delete();
        return back()->with('success', 'Support category deleted.');
    }

    private function validated(Request $request, ?SupportTicketCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:100', Rule::unique('support_ticket_categories','name')->ignore($category)],
            'description' => ['nullable','string','max:1000'],
            'default_priority' => ['required', Rule::enum(SupportTicketPriority::class)],
            'default_assignee_id' => ['nullable','exists:users,id'],
            'is_public' => ['boolean'], 'is_member' => ['boolean'], 'is_advertiser' => ['boolean'], 'is_active' => ['boolean'],
            'sort_order' => ['required','integer','min:0','max:9999'],
        ]);
    }
}
