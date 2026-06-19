<?php

namespace App\Http\Controllers\Api\V1\WebProfile;

use App\Http\Controllers\Controller;
use App\Models\BankKas;
use App\Models\Transaction;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

#[Group('Profil Web - Ringkasan & Statistik')]
class FinanceSummaryController extends Controller
{
    /**
     * Get finance summary for web profile frontend widget.
     * Accessible publicly or by web admins.
     */
    public function index(Request $request): JsonResponse
    {
        // Get total saldo akhir
        $totalSaldoAkhir = BankKas::aktif()->sum('saldo_terkini');

        // Get current month date range
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // Calculate pemasukan and pengeluaran for current month
        $pemasukanBulanIni = Transaction::where('status', 'approved')
            ->where('tipe', 'pemasukan')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal');

        $pengeluaranBulanIni = Transaction::where('status', 'approved')
            ->where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal');

        // Saldo Awal = Saldo Akhir - (Pemasukan Bulan Ini - Pengeluaran Bulan Ini)
        $saldoAwal = $totalSaldoAkhir - ($pemasukanBulanIni - $pengeluaranBulanIni);

        // Format period string (e.g. "Mei 2026")
        // Note: Carbon translate works best if app locale is set to 'id'
        $periode = Carbon::now()->translatedFormat('F Y');

        return response()->json([
            'status' => 'success',
            'data' => [
                'periode' => $periode,
                'saldo_awal' => (float) $saldoAwal,
                'pemasukan_bulan_ini' => (float) $pemasukanBulanIni,
                'pengeluaran_bulan_ini' => (float) $pengeluaranBulanIni,
                'saldo_akhir' => (float) $totalSaldoAkhir,
            ],
        ]);
    }
}
