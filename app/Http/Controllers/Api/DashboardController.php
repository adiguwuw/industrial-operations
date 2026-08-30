<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use App\Models\Incident;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index(
        DashboardService $dashboardService
    ): JsonResponse {
         Gate::authorize('statistics', Incident::class);

        return response()->json([
            'data' => $dashboardService->getOverview(),
        ]);
    }
}