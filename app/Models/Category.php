<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nama',
        'tipe',
        'deskripsi',
        'status',
        'visibilitas',
    ];

    /*
    |----------------------------------------------------------------------
    | Relationships
    |----------------------------------------------------------------------
    */

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /*
    |----------------------------------------------------------------------
    | Scopes
    |----------------------------------------------------------------------
    */

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeByTipe($query, string $tipe)
    {
        return $query->where('tipe', $tipe);
    }

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('nama', 'ilike', '%'.$search.'%')
                    ->orWhere('deskripsi', 'ilike', '%'.$search.'%');
            });
        });
    }
}
