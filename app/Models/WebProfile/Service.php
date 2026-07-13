<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

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

    protected function bgImage(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value && str_starts_with($value, '/storage') ? asset(ltrim($value, '/')) : $value,
        );
    }

    protected function details(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (is_array($value) && isset($value['supervisorImage']) && str_starts_with($value['supervisorImage'], '/storage')) {
                    $value['supervisorImage'] = asset(ltrim($value['supervisorImage'], '/'));
                }

                return $value;
            },
        );
    }
}
