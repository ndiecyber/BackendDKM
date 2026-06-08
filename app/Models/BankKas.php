<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankKas extends Model
{
    use SoftDeletes;

    protected $table = 'bank_kas';

    protected $fillable = [
        'nama',
        'tipe',
        'nomor_rekening',
        'atas_nama',
        'deskripsi',
        'qr_image_path',
        'saldo_awal',
        'saldo_terkini',
        'status',
        'visibilitas_publik',
    ];

    protected function casts(): array
    {
        return [
            'saldo_awal' => 'decimal:2',
            'saldo_terkini' => 'decimal:2',
            'visibilitas_publik' => 'boolean',
        ];
    }

    /*
    |----------------------------------------------------------------------
    | Relationships
    |----------------------------------------------------------------------
    */

    public function transaksiMasuk()
    {
        return $this->hasMany(Transaction::class, 'bank_kas_tujuan_id');
    }

    public function transaksiKeluar()
    {
        return $this->hasMany(Transaction::class, 'bank_kas_asal_id');
    }

    public function balanceAdjustments()
    {
        return $this->hasMany(BalanceAdjustment::class);
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

    public function scopeSearch($query, ?string $search)
    {
        return $query->when($search, function ($query, $search) {
            $query->where(function ($query) use ($search) {
                $query->where('nama', 'ilike', '%'.$search.'%')
                    ->orWhere('nomor_rekening', 'ilike', '%'.$search.'%')
                    ->orWhere('atas_nama', 'ilike', '%'.$search.'%');
            });
        });
    }

    /*
    |----------------------------------------------------------------------
    | Methods
    |----------------------------------------------------------------------
    */

    /**
     * Recalculate saldo_terkini from saldo_awal + approved transactions + balance adjustments.
     */
    public function hitungSaldoTerkini(): void
    {
        $pemasukan = $this->transaksiMasuk()
            ->where('status', 'approved')
            ->sum('nominal');

        $pengeluaran = $this->transaksiKeluar()
            ->where('status', 'approved')
            ->sum('nominal');

        // Include biaya_admin from transfers originating from this account
        $biayaAdmin = $this->transaksiKeluar()
            ->where('status', 'approved')
            ->where('tipe', 'transfer')
            ->sum('biaya_admin');

        $this->setAttribute('saldo_terkini', $this->saldo_awal + $pemasukan - $pengeluaran - $biayaAdmin);
        $this->saveQuietly();
    }
}
