<?php

namespace App\Services\PageHelp;

use App\Models\PageHelp;
use Illuminate\Http\Request;

class PageHelpContext
{
    public function __construct(
        private readonly PageHelpRegistry $registry,
        private readonly PageHelpResolver $resolver,
    ) {}

    public function forRequest(Request $request): ?array
    {
        $route = $request->route();

        $page = $this->registry->forRoute(
            $route?->getName(),
            $route?->gatherMiddleware() ?? [],
            $route?->uri(),
        );

        if (! $page) {
            return null;
        }

        $area = strtolower((string) ($page['area'] ?? 'public'));
        $entries = $this->resolver
            ->resolve($page['key'], $request->user(), $area)
            ->map(fn (PageHelp $help) => [
                'id' => $help->id,
                'title' => $help->title,
                'summary' => $help->summary,
                'content' => $help->content,
            ])
            ->values()
            ->all();

        $canManage = (bool) $request->user()?->hasPermission('manage_page_help');

        if ($entries === [] && ! $canManage) {
            return null;
        }

        return [
            'key' => $page['key'],
            'page_name' => $page['name'] ?? $page['key'],
            'entries' => $entries,
            'can_manage' => $canManage,
            'manage_url' => $canManage
                ? route('admin.page-help.create', ['page_key' => $page['key']])
                : null,
        ];
    }
}
