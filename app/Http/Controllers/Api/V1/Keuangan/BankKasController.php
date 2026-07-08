<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Keuangan\AdjustBalanceRequest;
use App\Http\Requests\Keuangan\StoreBankKasRequest;
use App\Http\Requests\Keuangan\UpdateBankKasRequest;
use App\Models\BalanceAdjustment;
use App\Models\BankKas;
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
        
        $adjustments = \App\Models\BalanceAdjustment::where('bank_kas_id', $id)
            ->latest('tanggal')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => 'adj_' . $item->id,
                    'type' => 'adjustment',
                    'tanggal' => $item->tanggal,
                    'deskripsi' => $item->deskripsi,
                    'nominal' => $item->selisih,
                    'is_pemasukan' => $item->selisih > 0,
                ];
            });
            
        $transfers = \App\Models\Transaction::where('tipe', 'transfer')
            ->where(function ($query) use ($id) {
                $query->where('bank_kas_asal_id', $id)
                      ->orWhere('bank_kas_tujuan_id', $id);
            })
            ->latest('tanggal')
            ->limit(20)
            ->get()
            ->map(function ($item) use ($id) {
                $isPemasukan = $item->bank_kas_tujuan_id == $id;
                return [
                    'id' => 'trx_' . $item->id,
                    'type' => 'transfer',
                    'tanggal' => $item->tanggal,
                    'deskripsi' => $item->deskripsi ?: 'Mutasi Kas',
                    'nominal' => $item->nominal,
                    'is_pemasukan' => $isPemasukan,
                ];
            });
            
        $activities = $adjustments->concat($transfers)
            ->sortByDesc('tanggal')
            ->values()
            ->take(20);
            
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
        $transactions = \App\Models\Transaction::where('status', 'approved')
            ->whereNotNull('program_id')
            ->where(function($query) use ($id) {
                $query->where('bank_kas_asal_id', $id)
                      ->orWhere('bank_kas_tujuan_id', $id);
            })->get();
            
        $balances = [];
        foreach($transactions as $trx) {
            $programId = $trx->program_id;
            if (!isset($balances[$programId])) {
                $balances[$programId] = 0;
            }
            
            if ($trx->tipe === 'pemasukan' && $trx->bank_kas_tujuan_id == $id) {
                $balances[$programId] += $trx->nominal;
            } elseif ($trx->tipe === 'pengeluaran' && $trx->bank_kas_asal_id == $id) {
                $balances[$programId] -= $trx->nominal;
            } elseif ($trx->tipe === 'transfer') {
                if ($trx->bank_kas_tujuan_id == $id) $balances[$programId] += $trx->nominal;
                if ($trx->bank_kas_asal_id == $id) $balances[$programId] -= $trx->nominal;
            }
        }
        
        $programIds = array_keys($balances);
        $programs = \App\Models\Program::whereIn('id', $programIds)->get()->keyBy('id');
        
        $result = [];
        foreach($balances as $progId => $amount) {
            if ($amount != 0) {
                $program = $programs->get($progId);
                $result[] = [
                    'program_id' => $progId,
                    'nama_program' => $program ? $program->nama : 'Unknown',
                    'saldo' => $amount
                ];
            }
        }
        
        return $this->successResponse($result);
    }
}
