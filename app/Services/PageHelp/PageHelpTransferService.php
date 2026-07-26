<?php

namespace App\Services\PageHelp;

use App\Enums\PageHelpAudience;
use App\Models\PageHelp;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use JsonException;

class PageHelpTransferService
{
    public const FORMAT = 'unclad-page-help';
    public const VERSION = 1;

    public function exportPayload(): array
    {
        $entries = PageHelp::query()
            ->with(['roles:id,name', 'permissions:id,name'])
            ->orderBy('page_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (PageHelp $help): array => [
                'page_key' => $help->page_key,
                'title' => $help->title,
                'summary' => $help->summary,
                'content' => $help->content,
                'audience' => $help->audience->value,
                'is_active' => $help->is_active,
                'is_published' => $help->is_published,
                'published_at' => $help->published_at?->toIso8601String(),
                'sort_order' => $help->sort_order,
                'roles' => $help->roles->pluck('name')->sort()->values()->all(),
                'permissions' => $help->permissions->pluck('name')->sort()->values()->all(),
            ])
            ->all();

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),
            'entry_count' => count($entries),
            'entries' => $entries,
        ];
    }

    public function exportJson(): string
    {
        return json_encode(
            $this->exportPayload(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ).PHP_EOL;
    }

    public function decode(string $json): array
    {
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The selected file does not contain valid JSON.', previous: $exception);
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('The selected file does not contain a Page Help export.');
        }

        $validator = Validator::make($payload, [
            'format' => ['required', Rule::in([self::FORMAT])],
            'version' => ['required', 'integer', Rule::in([self::VERSION])],
            'entries' => ['required', 'array'],
            'entries.*.page_key' => ['required', 'string', 'max:160'],
            'entries.*.title' => ['required', 'string', 'max:255'],
            'entries.*.summary' => ['nullable', 'string', 'max:2000'],
            'entries.*.content' => ['required', 'string'],
            'entries.*.audience' => ['required', Rule::in(PageHelpAudience::values())],
            'entries.*.is_active' => ['required', 'boolean'],
            'entries.*.is_published' => ['required', 'boolean'],
            'entries.*.published_at' => ['nullable', 'date'],
            'entries.*.sort_order' => ['required', 'integer', 'min:0'],
            'entries.*.roles' => ['present', 'array'],
            'entries.*.roles.*' => ['string', 'max:100'],
            'entries.*.permissions' => ['present', 'array'],
            'entries.*.permissions.*' => ['string', 'max:160'],
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        return $validator->validated();
    }

    public function importJson(
        string $json,
        string $mode = 'merge',
        ?User $actor = null,
        bool $dryRun = false,
    ): array {
        return $this->importPayload($this->decode($json), $mode, $actor, $dryRun);
    }

    public function importPayload(
        array $payload,
        string $mode = 'merge',
        ?User $actor = null,
        bool $dryRun = false,
    ): array {
        if (! in_array($mode, ['merge', 'replace'], true)) {
            throw new InvalidArgumentException('Import mode must be merge or replace.');
        }

        $roleIds = Role::query()->pluck('id', 'name');
        $permissionIds = Permission::query()->pluck('id', 'name');
        $summary = [
            'mode' => $mode,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'deleted' => 0,
            'missing_roles' => [],
            'missing_permissions' => [],
            'dry_run' => $dryRun,
        ];

        $work = function () use ($payload, $mode, $actor, $dryRun, $roleIds, $permissionIds, &$summary): void {
            $retainedIds = [];

            foreach ($payload['entries'] as $entry) {
                $identity = [
                    'page_key' => $entry['page_key'],
                    'title' => $entry['title'],
                    'audience' => $entry['audience'],
                    'sort_order' => (int) $entry['sort_order'],
                ];

                $existing = PageHelp::query()->where($identity)->first();
                $roleNames = collect($entry['roles'])->unique()->values();
                $permissionNames = collect($entry['permissions'])->unique()->values();
                $foundRoleIds = $roleNames->map(fn (string $name) => $roleIds->get($name))->filter()->values();
                $foundPermissionIds = $permissionNames->map(fn (string $name) => $permissionIds->get($name))->filter()->values();

                $summary['missing_roles'] = array_values(array_unique(array_merge(
                    $summary['missing_roles'],
                    $roleNames->reject(fn (string $name) => $roleIds->has($name))->all(),
                )));
                $summary['missing_permissions'] = array_values(array_unique(array_merge(
                    $summary['missing_permissions'],
                    $permissionNames->reject(fn (string $name) => $permissionIds->has($name))->all(),
                )));

                $attributes = [
                    'page_key' => $entry['page_key'],
                    'title' => $entry['title'],
                    'summary' => Arr::get($entry, 'summary'),
                    'content' => $entry['content'],
                    'audience' => $entry['audience'],
                    'is_active' => (bool) $entry['is_active'],
                    'is_published' => (bool) $entry['is_published'],
                    'published_at' => $entry['published_at'] ? Carbon::parse($entry['published_at']) : null,
                    'sort_order' => (int) $entry['sort_order'],
                    'updated_by' => $actor?->id,
                ];

                $changed = ! $existing
                    || collect($attributes)->contains(fn ($value, $key) => $this->different($existing->{$key}, $value))
                    || $existing->roles()->pluck('roles.id')->sort()->values()->all() !== $foundRoleIds->sort()->values()->all()
                    || $existing->permissions()->pluck('permissions.id')->sort()->values()->all() !== $foundPermissionIds->sort()->values()->all();

                if (! $existing) {
                    $summary['created']++;
                } elseif ($changed) {
                    $summary['updated']++;
                } else {
                    $summary['unchanged']++;
                }

                if ($dryRun) {
                    if ($existing) {
                        $retainedIds[] = $existing->id;
                    }
                    continue;
                }

                $help = PageHelp::query()->updateOrCreate($identity, array_merge($attributes, [
                    'created_by' => $existing?->created_by ?? $actor?->id,
                ]));
                $help->roles()->sync($foundRoleIds->all());
                $help->permissions()->sync($foundPermissionIds->all());
                $retainedIds[] = $help->id;
            }

            if ($mode === 'replace') {
                $deleteQuery = PageHelp::query();
                if ($retainedIds !== []) {
                    $deleteQuery->whereNotIn('id', $retainedIds);
                }
                $summary['deleted'] = $deleteQuery->count();
                if (! $dryRun) {
                    $deleteQuery->delete();
                }
            }
        };

        if ($dryRun) {
            $work();
        } else {
            DB::transaction($work);
        }

        sort($summary['missing_roles']);
        sort($summary['missing_permissions']);

        return $summary;
    }

    private function different(mixed $current, mixed $incoming): bool
    {
        if ($current instanceof Carbon) {
            $current = $current->toIso8601String();
        }
        if ($incoming instanceof Carbon) {
            $incoming = $incoming->toIso8601String();
        }
        if ($current instanceof PageHelpAudience) {
            $current = $current->value;
        }

        return $current != $incoming;
    }
}
