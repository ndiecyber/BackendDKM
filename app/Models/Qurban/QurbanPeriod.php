<?php

namespace App\Models\Qurban;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QurbanPeriod extends Model
{
    use HasFactory;

    protected $table = 'qurban_periods';

    protected $fillable = [
        'name',
        'sapi_price_per_slot',
        'kambing_price',
        'deadline_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sapi_price_per_slot' => 'decimal:2',
            'kambing_price' => 'decimal:2',
            'deadline_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /*
    |----------------------------------------------------------------------
    | Relationships
    |----------------------------------------------------------------------
    */

    public function animalGroups(): HasMany
    {
        return $this->hasMany(AnimalGroup::class, 'period_id');
    }

    public function shohibuls(): HasMany
    {
        return $this->hasMany(Shohibul::class, 'period_id');
    }

    /*
    |----------------------------------------------------------------------
    | Scopes
    |----------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
