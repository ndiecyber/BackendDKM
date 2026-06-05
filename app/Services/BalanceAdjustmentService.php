<?php

namespace App\Services;

use App\Models\BalanceAdjustment;
use App\Models\BankKas;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BalanceAdjustmentService
{
    /**
     * Create a new balance adjustment for a specific bank/kas.
     */
    public function createAdjustment(BankKas $bankKas, array $data, int $userId): BalanceAdjustment
    {
        return DB::transaction(function () use ($bankKas, $data, $userId) {
            $tanggal = Carbon::parse($data['tanggal']);
            $targetSaldo = (float) $data['target_saldo'];
            $deskripsi = $data['deskripsi'] ?? null;

            // Hitung saldo sistem persis pada akhir hari `$tanggal`
            $saldoSistem = $this->calculateSystemBalanceAt($bankKas, $tanggal);

            // Hitung selisih
            $selisih = $targetSaldo - $saldoSistem;

            $transactionId = null;

            if ($selisih != 0) {
                // Buat transaksi riil di jurnal agar Arus Kas seimbang
                $tipe = $selisih > 0 ? 'pemasukan' : 'pengeluaran';
                $nominal = abs($selisih);

                $prefix = $tipe === 'pemasukan' ? 'IN' : 'OUT';
                $dateStr = $tanggal->format('Ymd');
                // Nomor Transaksi auto generate sederhana
                $countToday = Transaction::whereDate('created_at', Carbon::today())->count() + 1;
                $nomorTransaksi = "{$prefix}-{$dateStr}-ADJ-".str_pad($countToday, 4, '0', STR_PAD_LEFT);

                $transactionData = [
                    'nomor_transaksi' => $nomorTransaksi,
                    'tipe' => $tipe,
                    'nama' => 'Penyesuaian Saldo (Auto)',
                    'deskripsi' => $deskripsi ?? 'Otomatis digenerate dari fitur rekonsiliasi.',
                    'nominal' => $nominal,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'category_id' => null, // Kategori akan dianggap sebagai null ("Penyesuaian Saldo" di ReportService)
                    'status' => 'approved',
                    'created_by' => $userId,
                ];

                if ($tipe === 'pemasukan') {
                    $transactionData['bank_kas_tujuan_id'] = $bankKas->id;
                } else {
                    $transactionData['bank_kas_asal_id'] = $bankKas->id;
                }

                $transaction = Transaction::create($transactionData);
                $transactionId = $transaction->id;
            }

            // Simpan riwayat penyesuaian
            $adjustment = BalanceAdjustment::create([
                'bank_kas_id' => $bankKas->id,
                'saldo_sebelum' => $saldoSistem,
                'saldo_sesudah' => $targetSaldo,
                'selisih' => $selisih,
                'tanggal' => $tanggal->format('Y-m-d'),
                'deskripsi' => $deskripsi,
                'transaction_id' => $transactionId,
                'created_by' => $userId,
            ]);

            // Selalu panggil hitungSaldoTerkini agar master balance ter-update
            $bankKas->hitungSaldoTerkini();

            return $adjustment;
        });
    }

    /**
     * Hitung saldo rekening tepat sampai akhir tanggal tertentu.
     */
    private function calculateSystemBalanceAt(BankKas $bankKas, Carbon $tanggal): float
    {
        // Pemasukan sampai tanggal tsb
        $pemasukan = Transaction::query()
            ->where('status', 'approved')
            ->where('tanggal', '<=', $tanggal->format('Y-m-d'))
            ->where('tipe', 'pemasukan')
            ->where('bank_kas_tujuan_id', $bankKas->id)
            ->sum('nominal');

        // Pengeluaran sampai tanggal tsb
        $pengeluaran = Transaction::query()
            ->where('status', 'approved')
            ->where('tanggal', '<=', $tanggal->format('Y-m-d'))
            ->where('tipe', 'pengeluaran')
            ->where('bank_kas_asal_id', $bankKas->id)
            ->sum('nominal');

        // Transfer Keluar (Termasuk biaya admin)
        $transferKeluarQuery = Transaction::query()
            ->where('status', 'approved')
            ->where('tanggal', '<=', $tanggal->format('Y-m-d'))
            ->where('tipe', 'transfer')
            ->where('bank_kas_asal_id', $bankKas->id);

        $transferKeluar = clone $transferKeluarQuery;
        $totalTransferKeluar = $transferKeluar->sum('nominal') + $transferKeluarQuery->sum('biaya_admin');

        // Transfer Masuk
        $transferMasuk = Transaction::query()
            ->where('status', 'approved')
            ->where('tanggal', '<=', $tanggal->format('Y-m-d'))
            ->where('tipe', 'transfer')
            ->where('bank_kas_tujuan_id', $bankKas->id)
            ->sum('nominal');

        return $bankKas->saldo_awal + $pemasukan + $transferMasuk - $pengeluaran - $totalTransferKeluar;
    }
}
