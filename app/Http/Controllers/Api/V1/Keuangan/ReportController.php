<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ReportService $reportService
    ) {}

    /**
     * Buku Kas Umum — chronological cash flow with running balance.
     */
    public function bukuKasUmum(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.laporan.view');

        $filters = $request->only([
            'periode', 'tanggal_mulai', 'tanggal_akhir',
            'category_id', 'bank_kas_id',
        ]);

        $data = $this->reportService->getBukuKasUmum($filters);

        $totalMasuk = $data->sum('masuk');
        $totalKeluar = $data->sum('keluar');

        return $this->successResponse([
            'entries' => $data,
            'summary' => [
                'total_masuk' => $totalMasuk,
                'total_keluar' => $totalKeluar,
                'selisih' => $totalMasuk - $totalKeluar,
                'jumlah_transaksi' => $data->count(),
            ],
        ]);
    }

    /**
     * Rekapitulasi per Kategori.
     */
    public function rekapKategori(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.laporan.view');

        $filters = $request->only([
            'periode', 'tanggal_mulai', 'tanggal_akhir',
            'bank_kas_id',
        ]);

        $data = $this->reportService->getRekapKategori($filters);

        return $this->successResponse($data);
    }

    /**
     * Export Buku Kas Umum as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        Gate::authorize('keuangan.laporan.export');

        $filters = $request->only([
            'periode', 'tanggal_mulai', 'tanggal_akhir',
            'category_id', 'bank_kas_id',
        ]);

        return $this->reportService->exportCsv($filters);
    }

    /**
     * Export Buku Kas Umum as PDF.
     */
    public function exportPdf(Request $request)
    {
        Gate::authorize('keuangan.laporan.export');

        $filters = $request->only([
            'periode', 'tanggal_mulai', 'tanggal_akhir',
            'category_id', 'bank_kas_id',
        ]);

        $pdf = $this->reportService->exportPdf($filters);

        return $pdf->download('laporan-keuangan-'.now()->format('Y-m-d').'.pdf');
    }
}
