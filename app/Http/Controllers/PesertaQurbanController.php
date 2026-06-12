<?php

namespace App\Http\Controllers;

use App\Models\PesertaQurban;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PesertaQurbanController extends Controller
{
    public function index(): JsonResponse
    {
        $peserta = PesertaQurban::with(['targetQurban', 'user'])
            ->withSum(['setorans as total_tabungan' => function ($query) {
                $query->where('status', 'Disetujui');
            }], 'nominal')
            ->get();

        return response()->json([
            'message' => 'Berhasil mengambil data peserta qurban',
            'data' => $peserta
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'target_qurban_id' => 'required|exists:target_qurbans,id',
        ]);

        $peserta = PesertaQurban::create($validated);

        return response()->json([
            'message' => 'Pendaftaran peserta qurban berhasil',
            'data' => $peserta
        ], 201);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string'
        ]);

        $peserta = PesertaQurban::findOrFail($id);
        $peserta->status = $validated['status'];
        $peserta->save();

        return response()->json([
            'message' => 'Status peserta berhasil diperbarui',
            'data' => $peserta
        ], 200);
    }

    public function destroy($id): JsonResponse
    {
        $peserta = PesertaQurban::findOrFail($id);
        $peserta->delete();

        return response()->json([
            'message' => 'Data peserta berhasil dihapus'
        ], 200);
    }
}