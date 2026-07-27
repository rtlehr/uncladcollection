<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageDiscoverySection extends Model
{
    protected $fillable = ['section_key', 'label', 'eyebrow', 'heading', 'description', 'sort_order', 'item_limit', 'is_enabled', 'audience'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'item_limit' => 'integer', 'is_enabled' => 'boolean'];
    }

    public function visibleTo(bool $authenticated): bool
    {
        return $this->is_enabled && match ($this->audience) {
            'guest' => ! $authenticated,
            'authenticated' => $authenticated,
            default => true,
        };
    }
}
