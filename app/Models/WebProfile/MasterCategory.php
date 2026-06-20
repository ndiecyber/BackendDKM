<?php

namespace App\Models\WebProfile;

use Illuminate\Database\Eloquent\Model;

class MasterCategory extends Model
{
    protected $fillable = [
        'type',
        'name',
        'description',
        'icon_name',
        'color',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * Scope: filter by type (kategori, tipe_berita, label, status).
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
