<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CtaSetting extends Model
{
    use HasFactory;

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

    protected function sliderImages(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!is_array($value)) return $value;
                return array_map(function ($img) {
                    return $img && str_starts_with($img, '/storage') ? asset(ltrim($img, '/')) : $img;
                }, $value);
            },
        );
    }
}
