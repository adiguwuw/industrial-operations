<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncidentCategoryController extends Controller
{
    /** Categories available when reporting an incident. */
    public function index(): JsonResponse
    {
        $categories = IncidentCategory::query()
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    /** Full category list for the admin management screen. */
    public function adminIndex(): JsonResponse
    {
        $categories = IncidentCategory::query()
            ->withCount('incidents')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'is_active', 'created_at', 'updated_at']);

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:incident_categories,name'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = IncidentCategory::create($validated);

        return response()->json(['data' => $category], 201);
    }

    public function update(Request $request, IncidentCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('incident_categories', 'name')->ignore($category->id, 'id'),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update($validated);

        return response()->json(['data' => $category->refresh()]);
    }

    public function destroy(IncidentCategory $category): JsonResponse
    {
        if ($category->incidents()->exists()) {
            return response()->json([
                'message' => 'Kategori ini sudah dipakai oleh insiden. Nonaktifkan kategori agar riwayat tetap utuh.',
            ], 409);
        }

        $category->delete();

        return response()->json([], 204);
    }
}
