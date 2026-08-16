<?php

namespace App\Services\Incident;

use App\Models\Incident;
use Illuminate\Support\Facades\DB;

class IncidentStatisticsService
{
    public function getStatistics(): array
    {
        return [
            'total' => Incident::count(),

            'status' => [
                'pending' => Incident::where('status', 'pending')->count(),
                'investigating' => Incident::where('status', 'investigating')->count(),
                'resolved' => Incident::where('status', 'resolved')->count(),
            ],

            'severity' => [
                'critical' => Incident::where('severity', 'critical')->count(),
                'high' => Incident::where('severity', 'high')->count(),
                'medium' => Incident::where('severity', 'medium')->count(),
                'low' => Incident::where('severity', 'low')->count(),
            ],
        ];
    }

    public function getByCategory(): array
    {
        return Incident::query()
            ->select(
                'category_id',
                DB::raw('COUNT(*) as total')
            )
            ->with('category:id,name')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get()
            ->map(function ($incident) {
                return [
                    'category_id' => $incident->category_id,
                    'category' => $incident->category?->name,
                    'total' => (int) $incident->total,
                ];
            })
            ->values()
            ->all();
    }

    public function getTrends(string $period = 'daily'): array
    {
        $format = match ($period) {
            'weekly' => '%x-W%v',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return Incident::query()
            ->select(
                DB::raw("DATE_FORMAT(reported_at, '{$format}') as period"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($item) => [
                'period' => $item->period,
                'total' => (int) $item->total,
            ])
            ->values()
            ->all();
    }
}