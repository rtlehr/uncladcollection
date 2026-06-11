<?php

namespace App\Services;

use App\Models\AdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AdminActivityService
{
    /**
     * Create a single activity record.
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $fieldName = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $description = null,
    ): AdminActivity {
        return AdminActivity::create([
            'user_id' => Auth::id(),
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->id,
            'subject_name' => $this->getSubjectName($subject),
            'action' => $action,
            'field_name' => $fieldName,
            'old_value' => $this->formatValue($oldValue),
            'new_value' => $this->formatValue($newValue),
            'description' => $description,
        ]);
    }

    /**
     * Compare two arrays and log any changed fields.
     */
    public function logChanges(
        Model $subject,
        array $oldValues,
        array $newValues,
        string $action = 'updated',
    ): void {
        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;

            if ($oldValue == $newValue) {
                continue;
            }

            $this->log(
                action: $action,
                subject: $subject,
                fieldName: $field,
                oldValue: $oldValue,
                newValue: $newValue,
                description: $this->getDescription($field)
            );
        }
    }

    /**
     * Record a create action.
     */
    public function created(
        Model $subject,
        ?string $description = null
    ): void {
        $this->log(
            action: 'created',
            subject: $subject,
            description: $description ?? 'Record created.'
        );
    }

    /**
     * Record a delete action.
     */
    public function deleted(
        Model $subject,
        ?string $description = null
    ): void {
        $this->log(
            action: 'deleted',
            subject: $subject,
            description: $description ?? 'Record deleted.'
        );
    }

    private function getSubjectName(?Model $subject): ?string
    {
        if (! $subject) {
            return null;
        }

        if (method_exists($subject, 'getActivityName')) {
            return $subject->getActivityName();
        }

        return null;
    }

    private function getDescription(string $field): string
    {
        return match ($field) {
            'title' => 'Title changed.',
            'slug' => 'Slug changed.',
            'description' => 'Description changed.',
            'photographer' => 'Photographer changed.',
            'sort_order' => 'Sort order changed.',
            'is_active' => 'Publishing status changed.',
            'is_disabled' => 'Account status changed.',
            'email' => 'Email address changed.',
            'username' => 'Username changed.',
            'name' => 'Name changed.',
            'collection' => 'Collection changed.',
            'collection_id' => 'Collection changed.',
            'categories' => 'Categories changed.',
            'tags' => 'Tags changed.',
            'roles' => 'Roles changed.',
            'permissions' => 'Permissions changed.',
            'image' => 'Image file changed.',
            'original_path',
            'high_res_path',
            'thumbnail_path',
            'icon_path' => 'Image file path changed.',
            default => 'Record updated.',
        };
    }

    /**
     * Convert values into a storable string.
     */
    private function formatValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }
}