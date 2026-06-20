<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'user_agent',
        'visited_date',
    ];

    protected function casts(): array
    {
        return [
            'visited_date' => 'date',
        ];
    }
}
