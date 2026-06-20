<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommitteeMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'division_id',
        'name',
        'role',
        'image',
        'is_leader',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_leader' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(CommitteeDivision::class, 'division_id');
    }

    /**
     * Scope: filter by group type.
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
