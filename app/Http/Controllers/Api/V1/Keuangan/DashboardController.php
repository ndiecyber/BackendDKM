<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\BankKas;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Dashboard overview: total saldo, ringkasan per bank/kas, transaksi terbaru.
     */
    public function overview(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.transaksi.view');

        // Total saldo from all active bank/kas
        $bankKasList = BankKas::aktif()->get(['id', 'nama', 'tipe', 'saldo_terkini']);
        $totalSaldo = $bankKasList->sum('saldo_terkini');

        // Latest transactions
        $latestTransactions = Transaction::query()
            ->where('status', 'approved')
            ->with(['category:id,nama', 'bankKasAsal:id,nama', 'bankKasTujuan:id,nama', 'jamaah:id,nama_lengkap'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // Summary for current month
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        $pemasukanBulanIni = Transaction::where('status', 'approved')
            ->where('tipe', 'pemasukan')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal');

        $pengeluaranBulanIni = Transaction::where('status', 'approved')
            ->where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->sum('nominal');

        return $this->successResponse([
            'total_saldo' => (float) $totalSaldo,
            'ringkasan_bank_kas' => $bankKasList,
            'pemasukan_bulan_ini' => (float) $pemasukanBulanIni,
            'pengeluaran_bulan_ini' => (float) $pengeluaranBulanIni,
            'selisih_bulan_ini' => (float) ($pemasukanBulanIni - $pengeluaranBulanIni),
            'transaksi_terbaru' => $latestTransactions,
        ]);
    }

    /**
     * Chart data: Pemasukan vs Pengeluaran per bulan (last 12 months).
     */
    public function chartPemasukanVsPengeluaran(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.transaksi.view');

        $months = (int) ($request->months ?? 12);
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth()->toDateString();

        $driver = DB::getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', tanggal)"
            : "TO_CHAR(tanggal, 'YYYY-MM')";

        $data = Transaction::query()
            ->where('status', 'approved')
            ->whereIn('tipe', ['pemasukan', 'pengeluaran'])
            ->where('tanggal', '>=', $startDate)
            ->select(
                DB::raw("{$monthExpr} as bulan"),
                'tipe',
                DB::raw('COALESCE(SUM(nominal), 0) as total')
            )
            ->groupBy('bulan', 'tipe')
            ->orderBy('bulan')
            ->get();

        // Pivot data by month
        $chartData = [];
        for ($i = 0; $i < $months; $i++) {
            $monthKey = Carbon::now()->subMonths($months - 1 - $i)->format('Y-m');
            $chartData[$monthKey] = [
                'bulan' => $monthKey,
                'pemasukan' => 0,
                'pengeluaran' => 0,
            ];
        }

        foreach ($data as $item) {
            if (isset($chartData[$item->bulan])) {
                $chartData[$item->bulan][$item->tipe] = (float) $item->total;
            }
        }

        return $this->successResponse(array_values($chartData));
    }

    /**
     * Chart data: Komposisi Pemasukan per Kategori (current month or custom).
     */
    public function chartKomposisiPemasukan(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.transaksi.view');

        $startDate = $request->tanggal_mulai ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $request->tanggal_akhir ?? Carbon::now()->endOfMonth()->toDateString();

        $data = Transaction::query()
            ->where('status', 'approved')
            ->where('tipe', 'pemasukan')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select('category_id', DB::raw('COALESCE(SUM(nominal), 0) as total'))
            ->groupBy('category_id')
            ->with('category:id,nama')
            ->get()
            ->map(fn ($item) => [
                'kategori' => $item->category?->nama ?? 'Tanpa Kategori',
                'total' => (float) $item->total,
            ]);

        return $this->successResponse($data);
    }

    /**
     * Chart data: Komposisi Pengeluaran per Kategori (current month or custom).
     */
    public function chartKomposisiPengeluaran(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.transaksi.view');

        $startDate = $request->tanggal_mulai ?? Carbon::now()->startOfMonth()->toDateString();
        $endDate = $request->tanggal_akhir ?? Carbon::now()->endOfMonth()->toDateString();

        $data = Transaction::query()
            ->where('status', 'approved')
            ->where('tipe', 'pengeluaran')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->select('category_id', DB::raw('COALESCE(SUM(nominal), 0) as total'))
            ->groupBy('category_id')
            ->with('category:id,nama')
            ->get()
            ->map(fn ($item) => [
                'kategori' => $item->category?->nama ?? 'Tanpa Kategori',
                'total' => (float) $item->total,
            ]);

        return $this->successResponse($data);
    }
}
