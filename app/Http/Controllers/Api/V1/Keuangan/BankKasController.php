<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Keuangan\AdjustBalanceRequest;
use App\Http\Requests\Keuangan\StoreBankKasRequest;
use App\Http\Requests\Keuangan\TransferAllRequest;
use App\Http\Requests\Keuangan\UpdateBankKasRequest;
use App\Models\BalanceAdjustment;
use App\Models\BankKas;
use App\Models\Program;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

#[Group('Keuangan - Bank & Kas')]
class BankKasController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of bank/kas accounts.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.bank_kas.view');

        $bankKas = BankKas::query()
            ->search($request->search)
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->when($request->tipe, fn ($query, $tipe) => $query->where('tipe', $tipe))
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($bankKas);
    }

    /**
     * Store a newly created bank/kas.
     */
    public function store(StoreBankKasRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('qr_image')) {
            $data['qr_image_path'] = $request->file('qr_image')->store('bank-kas/qr', 'public');
        }

        $data['saldo_terkini'] = $data['saldo_awal'] ?? 0;

        $bankKas = BankKas::create($data);

        return $this->createdResponse($bankKas, 'Bank/Kas berhasil ditambahkan.');
    }

    /**
     * Display the specified bank/kas.
     */
    public function show(string $id): JsonResponse
    {
        Gate::authorize('keuangan.bank_kas.view');

        $bankKas = BankKas::with('balanceAdjustments')->findOrFail($id);

        return $this->successResponse($bankKas);
    }

    /**
     * Update the specified bank/kas.
     */
    public function update(UpdateBankKasRequest $request, string $id): JsonResponse
    {
        $bankKas = BankKas::findOrFail($id);
        $data = $request->validated();

        if ($request->hasFile('qr_image')) {
            // Delete old QR image if exists
            if ($bankKas->qr_image_path) {
                Storage::disk('public')->delete($bankKas->qr_image_path);
            }
            $data['qr_image_path'] = $request->file('qr_image')->store('bank-kas/qr', 'public');
        }

        $bankKas->update($data);

        return $this->successResponse($bankKas, 'Bank/Kas berhasil diperbarui.');
    }

    /**
     * Soft delete the specified bank/kas.
     */
    public function destroy(string $id): JsonResponse
    {
        Gate::authorize('keuangan.bank_kas.delete');

        $bankKas = BankKas::findOrFail($id);
        $bankKas->delete();

        return $this->successResponse(null, 'Bank/Kas berhasil dihapus.');
    }

    /**
     * Restore a soft-deleted bank/kas.
     */
    public function restore(string $id): JsonResponse
    {
        Gate::authorize('keuangan.bank_kas.delete');

        $bankKas = BankKas::withTrashed()->findOrFail($id);

        if (! $bankKas->trashed()) {
            return $this->errorResponse('Bank/Kas tidak dalam kondisi terhapus.', 400);
        }

        $bankKas->restore();

        return $this->successResponse(null, 'Bank/Kas berhasil dipulihkan.');
    }

    /**
     * Adjust balance (rekonsiliasi/opname saldo).
     */
    public function adjust(AdjustBalanceRequest $request, string $id): JsonResponse
    {
        $bankKas = BankKas::findOrFail($id);

        $adjustment = DB::transaction(function () use ($bankKas, $request) {
            $saldoSebelum = $bankKas->saldo_terkini;
            $saldoSesudah = $request->saldo_sesudah;
            $selisih = $saldoSesudah - $saldoSebelum;

            $adjustment = BalanceAdjustment::create([
                'bank_kas_id' => $bankKas->id,
                'saldo_sebelum' => $saldoSebelum,
                'saldo_sesudah' => $saldoSesudah,
                'selisih' => $selisih,
                'tanggal' => $request->tanggal ?? now()->toDateString(),
                'deskripsi' => $request->deskripsi,
                'created_by' => $request->user()->id,
            ]);

            // Recalculate saldo_terkini
            $bankKas->hitungSaldoTerkini();

            return $adjustment;
        });

        return $this->createdResponse(
            $adjustment->load('bankKas:id,nama,saldo_terkini'),
            'Penyesuaian saldo berhasil dicatat.'
        );
    }

    /**
     * Get recent activities (adjustments and transfers) for this kas.
     */
    public function activities(Request $request, string $id): JsonResponse
    {
        Gate::authorize('keuangan.bank_kas.view');
        $bankKas = BankKas::findOrFail($id);

        $activities = Transaction::where('status', 'approved')
            ->where(function ($query) use ($id) {
                $query->where('bank_kas_asal_id', $id)
                    ->orWhere('bank_kas_tujuan_id', $id);
            })
            ->latest('tanggal')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(function ($item) use ($id) {
                $isPemasukan = $item->bank_kas_tujuan_id == $id;

                return [
                    'id' => 'trx_'.$item->id,
                    'type' => $item->tipe,
                    'tanggal' => $item->tanggal,
                    'deskripsi' => $item->deskripsi ?: $item->nama,
                    'nama' => $item->nama,
                    'nominal' => $item->nominal,
                    'is_pemasukan' => $isPemasukan,
                ];
            });

        return $this->successResponse($activities);
    }

    /**
     * Get balance composition by program for this kas.
     */
    public function programBalances(Request $request, string $id): JsonResponse
    {
        Gate::authorize('keuangan.bank_kas.view');
        $bankKas = BankKas::findOrFail($id);

        // Find all transactions affecting this bank_kas that have a program_id
        $transactions = Transaction::where('status', 'approved')
            ->whereNotNull('program_id')
            ->where(function ($query) use ($id) {
                $query->where('bank_kas_asal_id', $id)
                    ->orWhere('bank_kas_tujuan_id', $id);
            })->get();

        $balances = [];
        foreach ($transactions as $trx) {
            $programId = $trx->program_id;
            if (! isset($balances[$programId])) {
                $balances[$programId] = 0;
            }

            if ($trx->tipe === 'pemasukan' && $trx->bank_kas_tujuan_id == $id) {
                $balances[$programId] += $trx->nominal;
            } elseif ($trx->tipe === 'pengeluaran' && $trx->bank_kas_asal_id == $id) {
                $balances[$programId] -= $trx->nominal;
            } elseif ($trx->tipe === 'transfer') {
                if ($trx->bank_kas_tujuan_id == $id) {
                    $balances[$programId] += $trx->nominal;
                }
                if ($trx->bank_kas_asal_id == $id) {
                    $balances[$programId] -= $trx->nominal;
                    if ($trx->biaya_admin) {
                        $balances[$programId] -= $trx->biaya_admin;
                    }
                }
            }
        }

        $programIds = array_keys($balances);
        $programs = Program::whereIn('id', $programIds)->get()->keyBy('id');

        $result = [];
        $totalProgramSaldo = 0;
        foreach ($balances as $progId => $amount) {
            if ($amount != 0) {
                $program = $programs->get($progId);
                $result[] = [
                    'program_id' => $progId,
                    'nama_program' => $program ? $program->nama : 'Unknown',
                    'saldo' => $amount,
                ];
                $totalProgramSaldo += $amount;
            }
        }

        // Calculate remaining non-program balance (Umum)
        $saldoUmum = $bankKas->saldo_terkini - $totalProgramSaldo;
        if ($saldoUmum != 0 || empty($result)) {
            $result[] = [
                'program_id' => null,
                'nama_program' => 'Umum / Tanpa Program',
                'saldo' => $saldoUmum,
            ];
        }

        return $this->successResponse($result);
    }

    /**
     * Transfer all balances (Umum and Programs) to another Kas.
     */
    public function transferAll(TransferAllRequest $request, string $id, TransactionService $transactionService): JsonResponse
    {
        $bankKas = BankKas::findOrFail($id);
        $tujuanId = $request->validated('bank_kas_tujuan_id');
        $biayaAdmin = $request->validated('biaya_admin') ?? 0;
        $tanggal = $request->validated('tanggal') ?? date('Y-m-d');
        $deskripsi = $request->validated('deskripsi');

        // Calculate balances (duplicate logic from programBalances for internal use)
        $transactions = Transaction::where('status', 'approved')
            ->whereNotNull('program_id')
            ->where(function ($query) use ($id) {
                $query->where('bank_kas_asal_id', $id)
                    ->orWhere('bank_kas_tujuan_id', $id);
            })->get();

        $balances = [];
        foreach ($transactions as $trx) {
            $programId = $trx->program_id;
            if (! isset($balances[$programId])) {
                $balances[$programId] = 0;
            }
            if ($trx->tipe === 'pemasukan' && $trx->bank_kas_tujuan_id == $id) {
                $balances[$programId] += $trx->nominal;
            } elseif ($trx->tipe === 'pengeluaran' && $trx->bank_kas_asal_id == $id) {
                $balances[$programId] -= $trx->nominal;
            } elseif ($trx->tipe === 'transfer') {
                if ($trx->bank_kas_tujuan_id == $id) {
                    $balances[$programId] += $trx->nominal;
                }
                if ($trx->bank_kas_asal_id == $id) {
                    $balances[$programId] -= $trx->nominal;
                    if ($trx->biaya_admin) {
                        $balances[$programId] -= $trx->biaya_admin;
                    }
                }
            }
        }

        $totalProgramSaldo = 0;
        $activePrograms = [];
        foreach ($balances as $progId => $amount) {
            if ($amount > 0) {
                $activePrograms[$progId] = $amount;
                $totalProgramSaldo += $amount;
            }
        }
        $saldoUmum = $bankKas->saldo_terkini - $totalProgramSaldo;

        // Check if total balance is enough for admin fee
        if ($bankKas->saldo_terkini < $biayaAdmin) {
            return $this->errorResponse('Total saldo kas tidak mencukupi untuk membayar biaya admin.', 400);
        }

        // Distribute admin fee
        $adminFeeUmum = 0;
        $adminFeeProgram = [];

        if ($biayaAdmin > 0) {
            if ($saldoUmum >= $biayaAdmin) {
                $adminFeeUmum = $biayaAdmin;
            } else {
                $adminFeeUmum = max(0, $saldoUmum);
                $remainingFee = $biayaAdmin - $adminFeeUmum;

                // Find largest program
                arsort($activePrograms);
                $largestProgramId = array_key_first($activePrograms);

                if ($largestProgramId && $activePrograms[$largestProgramId] >= $remainingFee) {
                    $adminFeeProgram[$largestProgramId] = $remainingFee;
                } else {
                    return $this->errorResponse('Tidak ada program tunggal yang cukup untuk menutupi sisa biaya admin.', 400);
                }
            }
        }

        DB::transaction(function () use ($id, $tujuanId, $tanggal, $deskripsi, $saldoUmum, $adminFeeUmum, $activePrograms, $adminFeeProgram, $transactionService) {
            // 1. Transfer Kas Umum
            if ($saldoUmum > 0) {
                $transactionService->createTransfer([
                    'nama' => 'Mutasi Kas Umum (Pindahkan Semua)',
                    'deskripsi' => $deskripsi,
                    'nominal' => $saldoUmum - $adminFeeUmum,
                    'biaya_admin' => $adminFeeUmum > 0 ? $adminFeeUmum : null,
                    'bank_kas_asal_id' => $id,
                    'bank_kas_tujuan_id' => $tujuanId,
                    'program_id' => null,
                    'tanggal' => $tanggal,
                    'status' => 'approved',
                ]);
            }

            // 2. Transfer Programs
            foreach ($activePrograms as $progId => $amount) {
                $progAdminFee = $adminFeeProgram[$progId] ?? 0;
                $transactionService->createTransfer([
                    'nama' => 'Mutasi Alokasi Program (Pindahkan Semua)',
                    'deskripsi' => $deskripsi,
                    'nominal' => $amount - $progAdminFee,
                    'biaya_admin' => $progAdminFee > 0 ? $progAdminFee : null,
                    'bank_kas_asal_id' => $id,
                    'bank_kas_tujuan_id' => $tujuanId,
                    'program_id' => $progId,
                    'tanggal' => $tanggal,
                    'status' => 'approved',
                ]);
            }
        });

        return $this->successResponse(null, 'Berhasil memindahkan seluruh saldo dan program.');
    }

    /**
     * Display a listing of soft-deleted bank/kas.
     */
    public function trashed(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.bank_kas.view');

        $bankKas = BankKas::onlyTrashed()
            ->search($request->search)
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($bankKas);
    }

    /**
     * Force delete a soft-deleted bank/kas permanently.
     */
    public function forceDelete(string $id): JsonResponse
    {
        Gate::authorize('keuangan.bank_kas.delete');

        $bankKas = BankKas::onlyTrashed()->findOrFail($id);
        $bankKas->forceDelete();

        return $this->successResponse(null, 'Rekening / Kas berhasil dihapus permanen.');
    }
}
