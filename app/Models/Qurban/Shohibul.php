<?php

namespace App\Models\Qurban;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shohibul extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'period_id',
        'animal_group_id',
        'name',
        'phone',
        'address',
        'target_type',
        'target_amount',
        'collected_amount',
        'last_payment_month',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
        ];
    }

    /*
    |----------------------------------------------------------------------
    | Accessors
    |----------------------------------------------------------------------
    */

    protected $appends = ['is_lunas', 'remaining_amount', 'payment_status'];

    public function getPaymentStatusAttribute(): string
    {
        if ($this->collected_amount > $this->target_amount) return 'lebih';
        if ($this->collected_amount == $this->target_amount) return 'lunas';
        return 'proses';
    }

    public function getIsLunasAttribute(): bool
    {
        return $this->collected_amount >= $this->target_amount;
    }

    public function getRemainingAmountAttribute(): string
    {
        $remaining = max(0, $this->target_amount - $this->collected_amount);

        return number_format($remaining, 2, '.', '');
    }

    /*
    |----------------------------------------------------------------------
    | Relationships
    |----------------------------------------------------------------------
    */

    public function period(): BelongsTo
    {
        return $this->belongsTo(QurbanPeriod::class, 'period_id');
    }

    public function animalGroup(): BelongsTo
    {
        return $this->belongsTo(AnimalGroup::class, 'animal_group_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(QurbanTransaction::class, 'shohibul_id');
    }

    /*
    |----------------------------------------------------------------------
    | Scopes
    |----------------------------------------------------------------------
    */

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($q, $search) {
            $like = $q->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $q->where(function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                    ->orWhere('phone', $like, "%{$search}%")
                    ->orWhere('address', $like, "%{$search}%");
            });
        });
    }

    public function scopeByStatus($query, ?string $status)
    {
        return $query->when($status, function ($q, $status) {
            match ($status) {
                'lunas' => $q->whereColumn('collected_amount', '>=', 'target_amount'),
                'belum_lunas' => $q->whereColumn('collected_amount', '<', 'target_amount'),
                default => null,
            };
        });
    }

    public function scopeByType($query, ?string $type)
    {
        return $query->when($type, fn ($q) => $q->where('target_type', $type));
    }

    /**
     * Check if shohibul has a pending transaction.
     */
    public function hasPendingTransaction(): bool
    {
        return $this->pendingTransaction() !== null;
    }

    /**
     * Get the active pending transaction.
     */
    public function pendingTransaction()
    {
        $pending = $this->transactions()->where('status', 'pending')->first();

        if ($pending && $pending->expired_at && now()->gt($pending->expired_at)) {
            $pending->update(['status' => 'failed']);

            return null;
        }

        return $pending;
    }
}
