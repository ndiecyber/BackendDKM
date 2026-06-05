<?php

namespace App\Models;

use Database\Factories\JamaahFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jamaah extends Model
{
    /** @use HasFactory<JamaahFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'jamaah';

    protected $fillable = [
        'nama_lengkap',
        'no_hp',
        'email',
        'jenis_kelamin',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSearch($query, $search)
    {
        return $query->when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('nama_lengkap', 'ilike', '%'.$search.'%')
                    ->orWhere('no_hp', 'ilike', '%'.$search.'%')
                    ->orWhere('email', 'ilike', '%'.$search.'%');
            });
        });
    }
}
