<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;
use iluminate\Support\Facades\Gate;

class IncidentPolicy
{

    public function index(
        DashboardService $dashboardService
    ): JsonResponse {
        Gate::authorize('statistics', Incident::class);

        return response()->json([
            'data' => $dashboardService->getOverview(),
        ]);
    }

    /**
     * Determine whether the user can view any incidents.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('incident.view-all')
            || $user->can('incident.view-own');
    }

    /**
     * Determine whether the user can view the incident.
     */
    public function view(User $user, Incident $incident): bool
    {
        if ($user->can('incident.view-all')) {
            return true;
        }

        if ($user->can('incident.view')) {
            return true;
        }

        return $user->can('incident.view-own')
            && $incident->user_id === $user->id;
    }

    /**
     * Determine whether the user can create incidents.
     */
    public function create(User $user): bool
    {
        return $user->can('incident.create');
    }

    /**
     * Determine whether the user can update the incident.
     */
    public function update(User $user, Incident $incident): bool
    {
        return false;
    }

    /**
     * Determine whether the user can investigate the incident.
     */
    public function investigate(User $user, Incident $incident): bool
    {
        return $user->can('incident.investigate');
    }

    /**
     * Determine whether the user can resolve the incident.
     */
    public function resolve(User $user, Incident $incident): bool
    {
        return $user->can('incident.resolve');
    }

    /**
     * Determine whether the user can comment on the incident.
     */
    public function comment(User $user, Incident $incident): bool
    {
        if ($user->can('incident.view-all')) {
            return $user->can('incident.comment');
        }

        return $user->can('incident.comment')
            && $incident->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the incident.
     */
    public function delete(User $user, Incident $incident): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the incident.
     */
    public function restore(User $user, Incident $incident): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the incident.
     */
    public function forceDelete(User $user, Incident $incident): bool
    {
        return false;
    }

    public function statistics(User $user): bool
    {
        return $user->can('incident.statistics');
    }
}