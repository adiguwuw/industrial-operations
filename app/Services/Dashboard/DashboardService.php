<?php

namespace App\Services\Dashboard;

use App\Services\Incident\IncidentStatisticsService;

class DashboardService
{
    public function __construct(
        protected IncidentStatisticsService $incidentStatisticsService
    ) {}

    public function getOverview(): array
    {
        return [
            'incidents' => $this->incidentStatisticsService->getStatistics(),

            'categories' => $this->incidentStatisticsService->getByCategory(),

            'trend' => [
                'period' => 'monthly',
                'items' => $this->incidentStatisticsService->getTrends('monthly'),
            ],
        ];
    }
}