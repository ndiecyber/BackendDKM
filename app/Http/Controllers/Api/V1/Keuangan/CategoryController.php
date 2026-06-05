<?php

namespace App\Http\Controllers\Api\V1\Keuangan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Keuangan\StoreCategoryRequest;
use App\Http\Requests\Keuangan\UpdateCategoryRequest;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of categories.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('keuangan.category.view');

        $categories = Category::query()
            ->search($request->search)
            ->when($request->tipe, fn ($query, $tipe) => $query->byTipe($tipe))
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->paginate($request->per_page ?? 15);

        return $this->successResponse($categories);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return $this->createdResponse($category, 'Kategori berhasil ditambahkan.');
    }

    /**
     * Display the specified category.
     */
    public function show(string $id): JsonResponse
    {
        Gate::authorize('keuangan.category.view');

        $category = Category::findOrFail($id);

        return $this->successResponse($category);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->update($request->validated());

        return $this->successResponse($category, 'Kategori berhasil diperbarui.');
    }

    /**
     * Soft delete the specified category.
     */
    public function destroy(string $id): JsonResponse
    {
        Gate::authorize('keuangan.category.delete');

        $category = Category::findOrFail($id);
        $category->delete();

        return $this->successResponse(null, 'Kategori berhasil dihapus.');
    }

    /**
     * Restore a soft-deleted category.
     */
    public function restore(string $id): JsonResponse
    {
        Gate::authorize('keuangan.category.delete');

        $category = Category::withTrashed()->findOrFail($id);

        if (! $category->trashed()) {
            return $this->errorResponse('Kategori tidak dalam kondisi terhapus.', 400);
        }

        $category->restore();

        return $this->successResponse(null, 'Kategori berhasil dipulihkan.');
    }
}
