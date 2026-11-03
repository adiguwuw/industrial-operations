<?php

namespace App\Services\Incident;

use App\Models\Incident;
use Illuminate\Support\Facades\DB;

class IncidentStatisticsService
{
    public function getStatistics(): array
    {
        $statistics = Incident::query()
            ->selectRaw(
                'COUNT(*) as total,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as pending,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as investigating,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as resolved,
                COALESCE(SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END), 0) as critical,
                COALESCE(SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END), 0) as high,
                COALESCE(SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END), 0) as medium,
                COALESCE(SUM(CASE WHEN severity = ? THEN 1 ELSE 0 END), 0) as low',
                [
                    'pending',
                    'investigating',
                    'resolved',
                    'critical',
                    'high',
                    'medium',
                    'low',
                ]
            )
            ->first();

        return [
            'total' => (int) $statistics->total,
            'status' => [
                'pending' => (int) $statistics->pending,
                'investigating' => (int) $statistics->investigating,
                'resolved' => (int) $statistics->resolved,
            ],
            'severity' => [
                'critical' => (int) $statistics->critical,
                'high' => (int) $statistics->high,
                'medium' => (int) $statistics->medium,
                'low' => (int) $statistics->low,
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