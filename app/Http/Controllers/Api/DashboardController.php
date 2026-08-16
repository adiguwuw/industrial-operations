<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(
        DashboardService $dashboardService
    ): JsonResponse {
        return response()->json([
            'data' => $dashboardService->getOverview(),
        ]);
    }
}