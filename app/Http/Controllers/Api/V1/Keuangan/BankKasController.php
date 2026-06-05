<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Keuangan\AdjustBalanceRequest;
use App\Http\Requests\Keuangan\StoreBankKasRequest;
use App\Http\Requests\Keuangan\UpdateBankKasRequest;
use App\Models\BalanceAdjustment;
use App\Models\BankKas;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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
}
