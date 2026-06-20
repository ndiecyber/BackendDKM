<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
