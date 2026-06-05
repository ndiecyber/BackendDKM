<?php

namespace App\Services;

use App\Models\Transaction;
use Barryvdh\DomPDF\PDF;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Get Buku Kas Umum (chronological cash flow with running balance).
     */
    public function getBukuKasUmum(array $filters): Collection
    {
        $query = Transaction::query()
            ->where('status', 'approved')
            ->byCategory($filters['category_id'] ?? null)
            ->byBankKas($filters['bank_kas_id'] ?? null);

        $this->applyPeriodFilter($query, $filters);

        $transactions = $query
            ->with(['category:id,nama', 'bankKasAsal:id,nama', 'bankKasTujuan:id,nama', 'jamaah:id,nama_lengkap'])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        // Calculate running balance
        $saldoAwal = $this->getSaldoAwalForPeriod($filters);
        $runningBalance = $saldoAwal;

        return $transactions->map(function ($trx) use (&$runningBalance) {
            $masuk = 0;
            $keluar = 0;

            if ($trx->tipe === 'pemasukan') {
                $masuk = $trx->nominal;
            } elseif ($trx->tipe === 'pengeluaran') {
                $keluar = $trx->nominal;
            } elseif ($trx->tipe === 'transfer') {
                // Transfer doesn't affect overall balance but show movement
                // Display as keluar from asal perspective
                $keluar = $trx->nominal + ($trx->biaya_admin ?? 0);
            }

            $runningBalance = $runningBalance + $masuk - $keluar;

            return [
                'tanggal' => $trx->tanggal->format('Y-m-d'),
                'nomor_transaksi' => $trx->nomor_transaksi,
                'nama' => $trx->nama,
                'kategori' => $trx->category?->nama ?? '-',
                'tipe' => $trx->tipe,
                'masuk' => $masuk,
                'keluar' => $keluar,
                'saldo' => $runningBalance,
                'jamaah' => $trx->jamaah?->nama_lengkap ?? '-',
                'bank_kas_asal' => $trx->bankKasAsal?->nama ?? '-',
                'bank_kas_tujuan' => $trx->bankKasTujuan?->nama ?? '-',
            ];
        });
    }

    /**
     * Get Rekapitulasi per Kategori.
     */
    public function getRekapKategori(array $filters): array
    {
        $query = Transaction::query()
            ->where('status', 'approved')
            ->byBankKas($filters['bank_kas_id'] ?? null);

        $this->applyPeriodFilter($query, $filters);

        $rekapPemasukan = (clone $query)
            ->where('tipe', 'pemasukan')
            ->select('category_id', DB::raw('COALESCE(SUM(nominal), 0) as total'), DB::raw('COUNT(*) as jumlah_transaksi'))
            ->groupBy('category_id')
            ->with('category:id,nama')
            ->get()
            ->map(fn($item) => [
                'kategori' => $item->category?->nama ?? 'Tanpa Kategori',
                'category_id' => $item->category_id,
                'total' => (float) $item->total,
                'jumlah_transaksi' => $item->jumlah_transaksi,
            ]);

        $rekapPengeluaran = (clone $query)
            ->where('tipe', 'pengeluaran')
            ->select('category_id', DB::raw('COALESCE(SUM(nominal), 0) as total'), DB::raw('COUNT(*) as jumlah_transaksi'))
            ->groupBy('category_id')
            ->with('category:id,nama')
            ->get()
            ->map(fn($item) => [
                'kategori' => $item->category?->nama ?? 'Tanpa Kategori',
                'category_id' => $item->category_id,
                'total' => (float) $item->total,
                'jumlah_transaksi' => $item->jumlah_transaksi,
            ]);

        return [
            'pemasukan' => [
                'detail' => $rekapPemasukan,
                'total' => $rekapPemasukan->sum('total'),
            ],
            'pengeluaran' => [
                'detail' => $rekapPengeluaran,
                'total' => $rekapPengeluaran->sum('total'),
            ],
            'selisih' => $rekapPemasukan->sum('total') - $rekapPengeluaran->sum('total'),
        ];
    }

    /**
     * Export Buku Kas Umum as CSV streamed response.
     */
    public function exportCsv(array $filters): StreamedResponse
    {
        $data = $this->getBukuKasUmum($filters);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="buku-kas-umum-' . now()->format('Y-m-d') . '.csv"',
        ];

        return new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'Tanggal',
                'No. Transaksi',
                'Nama',
                'Kategori',
                'Tipe',
                'Masuk',
                'Keluar',
                'Saldo',
                'Jamaah/Pihak',
                'Bank/Kas Asal',
                'Bank/Kas Tujuan',
            ]);

            // Data rows
            foreach ($data as $row) {
                fputcsv($handle, [
                    $row['tanggal'],
                    $row['nomor_transaksi'],
                    $row['nama'],
                    $row['kategori'],
                    $row['tipe'],
                    $row['masuk'],
                    $row['keluar'],
                    $row['saldo'],
                    $row['jamaah'],
                    $row['bank_kas_asal'],
                    $row['bank_kas_tujuan'],
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Export Buku Kas Umum as PDF.
     */
    public function exportPdf(array $filters): PDF
    {
        $data = $this->getBukuKasUmum($filters);
        $rekap = $this->getRekapKategori($filters);

        $periodLabel = $this->getPeriodLabel($filters);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('reports.buku-kas-pdf', [
            'data' => $data,
            'rekap' => $rekap,
            'periodLabel' => $periodLabel,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf;
    }

    /**
     * Apply period filters to query.
     */
    private function applyPeriodFilter($query, array $filters): void
    {
        // Custom date range takes priority
        if (!empty($filters['tanggal_mulai']) && !empty($filters['tanggal_akhir'])) {
            $query->byDateRange($filters['tanggal_mulai'], $filters['tanggal_akhir']);

            return;
        }

        $periode = $filters['periode'] ?? null;

        if (!$periode) {
            return;
        }

        $now = Carbon::now();

        match ($periode) {
            'mingguan' => $query->byDateRange(
                $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                $now->copy()->endOfWeek(Carbon::SUNDAY)->toDateString()
            ),
            'bulanan' => $query->byDateRange(
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString()
            ),
            'tahunan' => $query->byDateRange(
                $now->copy()->startOfYear()->toDateString(),
                $now->copy()->endOfYear()->toDateString()
            ),
            default => null,
        };
    }

    /**
     * Calculate saldo awal (opening balance) for the given period.
     * This is the sum of all approved transactions before the period start.
     */
    private function getSaldoAwalForPeriod(array $filters): float
    {
        $startDate = null;

        if (!empty($filters['tanggal_mulai'])) {
            $startDate = $filters['tanggal_mulai'];
        } elseif (!empty($filters['periode'])) {
            $now = Carbon::now();
            $startDate = match ($filters['periode']) {
                'mingguan' => $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString(),
                'bulanan' => $now->copy()->startOfMonth()->toDateString(),
                'tahunan' => $now->copy()->startOfYear()->toDateString(),
                default => null,
            };
        }

        if (!$startDate) {
            // If no period filter, start from 0 (show all-time)
            return 0;
        }

        $query = Transaction::query()
            ->where('status', 'approved')
            ->where('tanggal', '<', $startDate)
            ->byBankKas($filters['bank_kas_id'] ?? null);

        $pemasukan = (clone $query)->where('tipe', 'pemasukan')->sum('nominal');
        $pengeluaran = (clone $query)->where('tipe', 'pengeluaran')->sum('nominal');

        return $pemasukan - $pengeluaran;
    }

    /**
     * Get human-readable label for the current period filter.
     */
    private function getPeriodLabel(array $filters): string
    {
        if (!empty($filters['label_periode'])) {
            return $filters['label_periode'];
        }

        if (!empty($filters['tanggal_mulai']) && !empty($filters['tanggal_akhir'])) {
            $startDate = Carbon::parse($filters['tanggal_mulai']);
            $endDate = Carbon::parse($filters['tanggal_akhir']);
            return $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        }

        $periode = $filters['periode'] ?? null;
        $now = Carbon::now();

        switch ($periode) {
            case 'mingguan':
                $startDate = $now->copy()->startOfWeek(Carbon::MONDAY);
                $endDate = $now->copy()->endOfWeek(Carbon::SUNDAY);
                return 'Pekan ' . $now->weekOfYear . ' (' . $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y') . ')';
            case 'bulanan':
                $bulanIndo = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                return 'Bulan ' . $bulanIndo[$now->month - 1] . ' ' . $now->year;
            case 'tahunan':
                return 'Tahun ' . $now->year;
            default:
                return 'Semua Periode';
        }
    }
}
