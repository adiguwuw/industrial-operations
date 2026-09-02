<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IncidentCategory;
use Illuminate\Http\JsonResponse;

class IncidentCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = IncidentCategory::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories,
        ]);
    }
}