<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\BalanceAdjustment;
use App\Models\BankKas;
use App\Services\BalanceAdjustmentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

#[Group('Keuangan - Bank & Kas')]
class BalanceAdjustmentController extends Controller
{
    private BalanceAdjustmentService $adjustmentService;

    public function __construct(BalanceAdjustmentService $adjustmentService)
    {
        $this->adjustmentService = $adjustmentService;
    }

    /**
     * Get history of balance adjustments for a specific bank/kas.
     */
    public function index(Request $request, BankKas $bankKas)
    {
        $perPage = $request->integer('per_page', 15);

        $adjustments = BalanceAdjustment::query()
            ->where('bank_kas_id', $bankKas->id)
            ->with(['createdBy:id,name', 'transaction:id,nomor_transaksi'])
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        return response()->json($adjustments);
    }

    /**
     * Create a new balance adjustment (Opname Saldo / Reconciliation).
     */
    public function store(Request $request, BankKas $bankKas)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'target_saldo' => 'required|numeric|min:0',
            'deskripsi' => 'nullable|string|max:1000',
        ]);

        try {
            $adjustment = $this->adjustmentService->createAdjustment($bankKas, $validated, $request->user()->id);

            return response()->json([
                'message' => 'Penyesuaian saldo berhasil dilakukan.',
                'data' => $adjustment->load('transaction:id,nomor_transaksi,tipe,nominal'),
                'saldo_terkini_baru' => $bankKas->fresh()->saldo_terkini,
            ], 201);
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'general' => 'Gagal melakukan penyesuaian saldo: '.$e->getMessage(),
            ]);
        }
    }
}
