<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

#[Group('Keuangan - Program')]
class ProgramController extends Controller
{
    use ApiResponse;

    /**
     * Get list of programs with financial summary.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.program.view');

        $programs = Program::query()
            ->when($request->search, function ($query, $search) {
                $query->where('nama', 'ilike', "%{$search}%")
                    ->orWhere('deskripsi', 'ilike', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->withSum(['transactions as pemasukan' => function ($query) {
                $query->where('status', 'approved')->where('tipe', 'pemasukan');
            }], 'nominal')
            ->withSum(['transactions as pengeluaran' => function ($query) {
                $query->where('status', 'approved')->where('tipe', 'pengeluaran');
            }], 'nominal')
            ->withCount(['transactions as jumlah_transaksi' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->latest()
            ->paginate($request->per_page ?? 15);

        // Append computed sisa_saldo to each program
        $programs->getCollection()->transform(function ($program) {
            $program->pemasukan = (float) ($program->pemasukan ?? 0);
            $program->pengeluaran = (float) ($program->pengeluaran ?? 0);
            $program->sisa_saldo = $program->pemasukan - $program->pengeluaran;

            return $program;
        });

        return $this->successResponse($programs);
    }

    /**
     * Create a new program.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.program.create');

        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:aktif,selesai',
        ]);

        $program = Program::create($validated);

        return $this->createdResponse($program, 'Program berhasil ditambahkan.');
    }

    /**
     * Get a program with financial summary.
     */
    public function show($id): JsonResponse
    {
        Gate::authorize('keuangan.program.view');

        $program = Program::withSum(['transactions as pemasukan' => function ($query) {
            $query->where('status', 'approved')->where('tipe', 'pemasukan');
        }], 'nominal')
            ->withSum(['transactions as pengeluaran' => function ($query) {
                $query->where('status', 'approved')->where('tipe', 'pengeluaran');
            }], 'nominal')
            ->withCount(['transactions as jumlah_transaksi' => function ($query) {
                $query->where('status', 'approved');
            }])
            ->findOrFail($id);

        $program->pemasukan = (float) ($program->pemasukan ?? 0);
        $program->pengeluaran = (float) ($program->pengeluaran ?? 0);
        $program->sisa_saldo = $program->pemasukan - $program->pengeluaran;

        return $this->successResponse($program);
    }

    /**
     * Update a program.
     */
    public function update(Request $request, $id): JsonResponse
    {
        Gate::authorize('keuangan.program.update');

        $program = Program::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'sometimes|required|in:aktif,selesai',
        ]);

        $program->update($validated);

        return $this->successResponse($program, 'Program berhasil diperbarui.');
    }

    /**
     * Soft delete a program.
     */
    public function destroy($id): JsonResponse
    {
        Gate::authorize('keuangan.program.delete');

        $program = Program::findOrFail($id);
        $program->delete();

        return $this->successResponse(null, 'Program berhasil dihapus.');
    }

    /**
     * Restore a deleted program.
     */
    public function restore($id): JsonResponse
    {
        Gate::authorize('keuangan.program.delete');

        $program = Program::withTrashed()->findOrFail($id);
        $program->restore();

        return $this->successResponse(null, 'Program berhasil dipulihkan.');
    }

    /**
     * Get physical balances of a program across bank/kas.
     */
    public function physicalBalances($id): JsonResponse
    {
        Gate::authorize('keuangan.program.view');
        $program = Program::findOrFail($id);
        
        $transactions = \App\Models\Transaction::where('program_id', $id)
            ->where('status', 'approved')
            ->get();
            
        $balances = [];
        foreach($transactions as $trx) {
            if ($trx->tipe === 'pemasukan') {
                $kasId = $trx->bank_kas_tujuan_id;
                if (!isset($balances[$kasId])) $balances[$kasId] = 0;
                $balances[$kasId] += $trx->nominal;
            } elseif ($trx->tipe === 'pengeluaran') {
                $kasId = $trx->bank_kas_asal_id;
                if (!isset($balances[$kasId])) $balances[$kasId] = 0;
                $balances[$kasId] -= $trx->nominal;
            } elseif ($trx->tipe === 'transfer') {
                $kasAsal = $trx->bank_kas_asal_id;
                $kasTujuan = $trx->bank_kas_tujuan_id;
                if (!isset($balances[$kasAsal])) $balances[$kasAsal] = 0;
                if (!isset($balances[$kasTujuan])) $balances[$kasTujuan] = 0;
                $balances[$kasAsal] -= $trx->nominal;
                $balances[$kasTujuan] += $trx->nominal;
            }
        }
        
        $bankKasIds = array_keys($balances);
        $bankKas = \App\Models\BankKas::whereIn('id', $bankKasIds)->get()->keyBy('id');
        
        $result = [];
        foreach($balances as $kasId => $amount) {
            if ($amount != 0) {
                $kas = $bankKas->get($kasId);
                $result[] = [
                    'bank_kas_id' => $kasId,
                    'nama_kas' => $kas ? $kas->nama : 'Unknown',
                    'tipe_kas' => $kas ? $kas->tipe : 'Unknown',
                    'saldo' => $amount
                ];
            }
        }
        
        return $this->successResponse($result);
    }

    /**
     * Rollover remaining funds from a program.
     */
    public function rollover(Request $request, $id): JsonResponse
    {
        Gate::authorize('keuangan.program.update');
        $sourceProgram = Program::findOrFail($id);
        
        $validated = $request->validate([
            'target_program_id' => 'nullable|exists:programs,id',
            'sources' => 'required|array',
            'sources.*.bank_kas_id' => 'required|exists:bank_kas,id',
            'sources.*.amount' => 'required|numeric|min:1',
            'deskripsi' => 'nullable|string'
        ]);
        
        $targetProgramId = $validated['target_program_id'] ?? null;
        $targetProgram = $targetProgramId ? Program::find($targetProgramId) : null;
        
        \Illuminate\Support\Facades\DB::transaction(function () use ($sourceProgram, $targetProgram, $targetProgramId, $validated, $request) {
            $transactionService = app(\App\Services\TransactionService::class);
            
            $targetName = $targetProgram ? $targetProgram->nama : 'Kas Umum';
            $desc = $validated['deskripsi'] ?? "Rollover sisa dana dari {$sourceProgram->nama} ke {$targetName}";
            
            foreach($validated['sources'] as $source) {
                $kas = \App\Models\BankKas::find($source['bank_kas_id']);
                $namaKas = $kas ? $kas->nama : 'Kas Unknown';
                
                // 1. Outflow from source program
                $transactionService->createExpense([
                    'nama' => "Rollover Keluar - {$namaKas} (Auto)",
                    'deskripsi' => $desc,
                    'tipe' => 'pengeluaran',
                    'bank_kas_asal_id' => $source['bank_kas_id'],
                    'nominal' => $source['amount'],
                    'program_id' => $sourceProgram->id,
                    'status' => 'approved',
                    'tanggal' => now()->toDateString(),
                    'created_by' => $request->user()->id,
                ]);
                
                // 2. Inflow to target program
                $transactionService->createIncome([
                    'nama' => "Rollover Masuk - {$namaKas} (Auto)",
                    'deskripsi' => $desc,
                    'tipe' => 'pemasukan',
                    'bank_kas_tujuan_id' => $source['bank_kas_id'],
                    'nominal' => $source['amount'],
                    'program_id' => $targetProgramId,
                    'status' => 'approved',
                    'tanggal' => now()->toDateString(),
                    'created_by' => $request->user()->id,
                ]);
            }
            
            $sourceProgram->update(['status' => 'selesai']);
        });
        
        return $this->successResponse(null, 'Sisa dana berhasil disalurkan.');
    }

    /**
     * Display a listing of soft-deleted programs.
     */
    public function trashed(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.program.view');

        $programs = Program::onlyTrashed()
            ->search($request->search)
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($programs);
    }

    /**
     * Force delete a soft-deleted program permanently.
     */
    public function forceDelete(string $id): JsonResponse
    {
        Gate::authorize('keuangan.program.delete');

        $program = Program::onlyTrashed()->findOrFail($id);
        $program->forceDelete();

        return $this->successResponse(null, 'Program berhasil dihapus permanen.');
    }
}
