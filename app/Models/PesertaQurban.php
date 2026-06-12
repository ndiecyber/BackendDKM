<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesertaQurban extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_qurban_id',
        'total_tabungan',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetQurban(): BelongsTo
    {
        return $this->belongsTo(TargetQurban::class);
    }

    // Menggunakan jamaah_id agar tidak error dengan tabel setoran lamamu
    public function setorans()
    {
        return $this->hasMany(Setoran::class, 'jamaah_id');
    }
}