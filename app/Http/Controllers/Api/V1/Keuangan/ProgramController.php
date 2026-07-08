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
}
