<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'title',
        'icon',
        'category',
        'badge',
        'bg_image',
        'description',
        'details',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'details' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
