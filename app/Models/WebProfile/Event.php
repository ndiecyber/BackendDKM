<?php

namespace App\Models\WebProfile;

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

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_active' => 'boolean',
            'hits' => 'integer',
        ];
    }
}
