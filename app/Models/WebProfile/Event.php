<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'date',
        'time',
        'type',
        'category',
        'badge',
        'image',
        'location',
        'author',
        'description',
        'content',
        'is_active',
        'hits',
    ];

    protected $appends = [
        'day',
        'month',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_active' => 'boolean',
            'hits' => 'integer',
        ];
    }

    protected function day(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['date']) ? \Carbon\Carbon::parse($attributes['date'])->format('d') : null,
        );
    }

    protected function month(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => isset($attributes['date']) ? \Carbon\Carbon::parse($attributes['date'])->locale('id')->isoFormat('MMM') : null,
        );
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value && str_starts_with($value, '/storage') ? asset(ltrim($value, '/')) : $value,
        );
    }
}
