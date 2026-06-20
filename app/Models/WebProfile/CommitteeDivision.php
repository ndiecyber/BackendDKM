<?php

namespace App\Models\WebProfile;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|CommitteeMember[] $members
 */
class CommitteeDivision extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function members(): HasMany
    {
        return $this->hasMany(CommitteeMember::class, 'division_id')->orderBy('sort_order');
    }
}
