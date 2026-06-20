<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CtaSetting extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'quote',
        'quote_source',
        'total_donors',
        'slider_images',
    ];

    protected function casts(): array
    {
        return [
            'total_donors' => 'integer',
            'slider_images' => 'array',
        ];
    }

    public function programs(): HasMany
    {
        return $this->hasMany(CtaProgram::class)->orderBy('sort_order');
    }
}
