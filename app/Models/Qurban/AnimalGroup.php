<?php

namespace App\Models\Qurban;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnimalGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'name',
        'target_type',
    ];

    /*
    |----------------------------------------------------------------------
    | Relationships
    |----------------------------------------------------------------------
    */

    public function period(): BelongsTo
    {
        return $this->belongsTo(QurbanPeriod::class, 'period_id');
    }

    public function shohibuls(): HasMany
    {
        return $this->hasMany(Shohibul::class, 'animal_group_id');
    }

    /*
    |----------------------------------------------------------------------
    | Scopes
    |----------------------------------------------------------------------
    */

    public function scopeByType($query, ?string $type)
    {
        return $query->when($type, fn ($q) => $q->where('target_type', $type));
    }

    /**
     * Filter groups that still have available slots (< 7 members for sapi).
     */
    public function scopeAvailable($query)
    {
        return $query->withCount('shohibuls')
            ->having('shohibuls_count', '<', 7);
    }
}
