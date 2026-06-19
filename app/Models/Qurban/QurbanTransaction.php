<?php

namespace App\Models\Qurban;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QurbanTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'shohibul_id',
        'order_id',
        'amount',
        'status',
        'payment_method',
        'payment_number',
        'total_payment',
        'expired_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'total_payment' => 'decimal:2',
            'expired_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /*
    |----------------------------------------------------------------------
    | Relationships
    |----------------------------------------------------------------------
    */

    public function shohibul(): BelongsTo
    {
        return $this->belongsTo(Shohibul::class, 'shohibul_id');
    }

    /*
    |----------------------------------------------------------------------
    | Scopes
    |----------------------------------------------------------------------
    */

    public function scopeByStatus($query, ?string $status)
    {
        return $query->when($status, fn ($q) => $q->where('status', $status));
    }

    public function scopeByMethod($query, ?string $method)
    {
        return $query->when($method, fn ($q) => $q->where('payment_method', $method));
    }

    public function scopeByDateRange($query, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to.' 23:59:59'));
    }
}
