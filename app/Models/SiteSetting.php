<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'group_name',
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function getFullKeyAttribute(): string
    {
        return $this->group_name . '.' . $this->setting_key;
    }
}