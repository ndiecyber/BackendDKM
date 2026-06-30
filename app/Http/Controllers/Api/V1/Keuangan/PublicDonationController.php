<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\BankKas;
use App\Models\Program;
use App\Models\Transaction;
use App\Services\Qurban\PaKasirService;
use App\Services\TransactionService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

#[Group('Public - Donasi')]
class PublicDonationController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private PaKasirService $paKasirService
    ) {}

    /**
     * Get active programs for donation
     */
    public function getPrograms()
    {
        $programs = Program::where('status', 'aktif')
            ->select('id', 'nama', 'deskripsi')
            ->get();

        return response()->json($programs);
    }

    /**
     * Submit a public donation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'nullable|string|max:255',
            'is_anonim' => 'nullable|boolean',
            'nominal' => 'required|numeric|min:10000',
            'program_id' => 'nullable|exists:programs,id',
            'deskripsi' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array|max:3',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $dompetPaKasir = BankKas::where('nama', 'Dompet PaKasir')->first();
        if (! $dompetPaKasir) {
            return response()->json(['message' => 'Kas penampung otomatis (Dompet PaKasir) belum dikonfigurasi oleh admin.'], 500);
        }

        // Prepare Description
        $namaDonatur = ! empty($validated['is_anonim']) ? 'Hamba Allah' : ($validated['nama'] ?? 'Hamba Allah');
        $deskripsiTeks = "Donatur: {$namaDonatur}";
        if (! empty($validated['deskripsi'])) {
            $deskripsiTeks .= ' | Pesan: '.$validated['deskripsi'];
        }

        $data = [
            'tipe' => 'pemasukan',
            'nama' => 'Donasi Publik (Online)',
            'deskripsi' => $deskripsiTeks,
            'nominal' => $validated['nominal'],
            'status' => 'pending',
            'program_id' => $validated['program_id'] ?? null,
            'bank_kas_tujuan_id' => $dompetPaKasir->id,
            'tanggal' => now()->toDateString(),
        ];

        try {
            DB::beginTransaction();

            $transaction = $this->transactionService->createIncome($data, $request->file('attachments', []));

            // Fallback for manual transfer (has attachments)
            if ($request->hasFile('attachments')) {
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Donasi manual berhasil dicatat, menunggu verifikasi.',
                    'data' => [
                        'nomor_transaksi' => $transaction->nomor_transaksi,
                    ],
                ], 201);
            }

            // Create PaKasir transaction
            $paKasirData = $this->paKasirService->createTransaction('qris', $transaction->nomor_transaksi, (int) $transaction->nominal);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Donasi berhasil dicatat.',
                'data' => [
                    'nomor_transaksi' => $transaction->nomor_transaksi,
                    'pakasir' => $paKasirData,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Public Donation Error: '.$e->getMessage());
            throw ValidationException::withMessages([
                'general' => 'Gagal memproses donasi: '.$e->getMessage(),
            ]);
        }
    }
}
