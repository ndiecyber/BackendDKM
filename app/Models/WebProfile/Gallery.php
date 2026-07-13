<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_path',
        'caption',
        'subcaption',
        'tag',
        'category',
        'icon_name',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected function imagePath(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value && str_starts_with($value, '/storage') ? asset(ltrim($value, '/')) : $value,
        );
    }
}
