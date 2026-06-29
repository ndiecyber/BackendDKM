<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;

/**
 * @tags Keuangan - Program
 */
class ProgramController extends Controller
{
    /**
     * Get list of programs
     * 
     * Retrieve all programs (kegiatan/campaign) used for fund accounting.
     */
    public function index(Request $request)
    {
        $programs = Program::query()
            ->when($request->search, function ($query, $search) {
                $query->where('nama', 'ilike', "%{$search}%")
                    ->orWhere('deskripsi', 'ilike', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($programs);
    }

    /**
     * Create a new program
     * 
     * Create a new program bucket for fund accounting.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:aktif,selesai',
        ]);

        $program = Program::create($validated);

        return response()->json($program, 201);
    }

    /**
     * Get a program
     * 
     * Retrieve details of a specific program.
     */
    public function show($id)
    {
        $program = Program::findOrFail($id);
        
        return response()->json($program);
    }

    /**
     * Update a program
     * 
     * Update details of an existing program.
     */
    public function update(Request $request, $id)
    {
        $program = Program::findOrFail($id);

        $validated = $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'deskripsi' => 'nullable|string',
            'tanggal_mulai' => 'nullable|date',
            'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
            'status' => 'sometimes|required|in:aktif,selesai',
        ]);

        $program->update($validated);

        return response()->json($program);
    }

    /**
     * Delete a program
     * 
     * Soft delete a program.
     */
    public function destroy($id)
    {
        $program = Program::findOrFail($id);
        $program->delete();

        return response()->json(['message' => 'Program deleted successfully']);
    }

    /**
     * Restore a deleted program
     * 
     * Restore a previously soft-deleted program.
     */
    public function restore($id)
    {
        $program = Program::withTrashed()->findOrFail($id);
        $program->restore();

        return response()->json(['message' => 'Program restored successfully']);
    }
}
