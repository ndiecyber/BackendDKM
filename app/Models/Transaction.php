<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nomor_transaksi',
        'tipe',
        'nama',
        'deskripsi',
        'nominal',
        'tanggal',
        'category_id',
        'program_id',
        'bank_kas_asal_id',
        'bank_kas_tujuan_id',
        'jamaah_id',
        'biaya_admin',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
            'biaya_admin' => 'decimal:2',
            'tanggal' => 'date',
        ];
    }

    /*
    |----------------------------------------------------------------------
    | Relationships
    |----------------------------------------------------------------------
    */

    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    public function program()
    {
        return $this->belongsTo(Program::class)->withTrashed();
    }

    public function bankKasAsal()
    {
        return $this->belongsTo(BankKas::class, 'bank_kas_asal_id')->withTrashed();
    }

    public function bankKasTujuan()
    {
        return $this->belongsTo(BankKas::class, 'bank_kas_tujuan_id')->withTrashed();
    }

    public function jamaah()
    {
        return $this->belongsTo(Jamaah::class)->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(TransactionAttachment::class);
    }

    /*
    |----------------------------------------------------------------------
    | Scopes
    |----------------------------------------------------------------------
    */

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('nama', 'ilike', '%'.$search.'%')
                    ->orWhere('nomor_transaksi', 'ilike', '%'.$search.'%')
                    ->orWhere('deskripsi', 'ilike', '%'.$search.'%');
            });
        });
    }

    public function scopeByTipe($query, ?string $tipe)
    {
        return $query->when($tipe, fn ($query, $tipe) => $query->where('tipe', $tipe));
    }

    public function scopeByStatus($query, ?string $status)
    {
        return $query->when($status, fn ($query, $status) => $query->where('status', $status));
    }

    public function scopeByDateRange($query, ?string $from, ?string $to)
    {
        return $query
            ->when($from, fn ($query, $from) => $query->where('tanggal', '>=', $from))
            ->when($to, fn ($query, $to) => $query->where('tanggal', '<=', $to));
    }

    public function scopeByCategory($query, ?int $categoryId)
    {
        return $query->when($categoryId, fn ($query, $id) => $query->where('category_id', $id));
    }

    public function scopeByProgram($query, ?int $programId)
    {
        return $query->when($programId, fn ($query, $id) => $query->where('program_id', $id));
    }

    public function scopeByBankKas($query, ?int $bankKasId)
    {
        return $query->when($bankKasId, function ($query, $id) {
            $query->where(function ($query) use ($id) {
                $query->where('bank_kas_asal_id', $id)
                    ->orWhere('bank_kas_tujuan_id', $id);
            });
        });
    }
}
