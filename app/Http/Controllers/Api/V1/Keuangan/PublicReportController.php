<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\BankKas;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

#[Group('Keuangan - Public')]
class PublicReportController extends Controller
{
    use ApiResponse;

    /**
     * Get monthly financial report for public landing page
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        $bulan = (int) ($request->month ?? Carbon::now()->month);
        $tahun = (int) ($request->year ?? Carbon::now()->year);

        $targetDate = Carbon::createFromDate($tahun, $bulan, 1);
        $startOfMonth = $targetDate->copy()->startOfMonth()->toDateString();
        $endOfMonth = $targetDate->copy()->endOfMonth()->toDateString();

        $totalSaldoAwalKas = BankKas::where('status', 'aktif')->sum('saldo_awal');

        // Net transactions before this month
        $pemasukanSebelumnya = Transaction::where('status', 'approved')
            ->where('tipe', 'pemasukan')
            ->where('tanggal', '<', $startOfMonth)
            ->sum('nominal');

        $pengeluaranSebelumnya = Transaction::where('status', 'approved')
            ->where('tipe', 'pengeluaran')
            ->where('tanggal', '<', $startOfMonth)
            ->sum('nominal');

        $saldoAwalBulan = $totalSaldoAwalKas + $pemasukanSebelumnya - $pengeluaranSebelumnya;

        // Transactions this month
        $pemasukanBulanIni = Transaction::where('status', 'approved')
            ->where('tipe', 'pemasukan')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal');

        $pengeluaranBulanIni = Transaction::where('status', 'approved')
            ->where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal');

        $saldoAkhirBulan = $saldoAwalBulan + $pemasukanBulanIni - $pengeluaranBulanIni;

        // Detail transactions
        $transactions = Transaction::where('status', 'approved')
            ->whereIn('tipe', ['pemasukan', 'pengeluaran'])
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->orderBy('tanggal', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->successResponse([
            'periode' => ['month' => $bulan, 'year' => $tahun],
            'saldo_awal' => (float) $saldoAwalBulan,
            'pemasukan' => (float) $pemasukanBulanIni,
            'pengeluaran' => (float) $pengeluaranBulanIni,
            'saldo_akhir' => (float) $saldoAkhirBulan,
            'transactions' => $transactions,
        ]);
    }
}
