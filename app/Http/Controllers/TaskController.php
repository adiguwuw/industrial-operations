<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function updateStatus(
        Request $request,
        Task $task
    ): JsonResponse {
        abort_unless(
            $request->user()->hasRole('admin')
            || (int) $task->assigned_to === (int) $request->user()->id,
            403
        );

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,completed'],
        ]);

        $allowedTransitions = [
            'open' => ['open', 'in_progress'],
            'in_progress' => ['in_progress', 'completed'],
            'completed' => ['completed'],
        ];

        if (! in_array($validated['status'], $allowedTransitions[$task->status] ?? [], true)) {
            return response()->json([
                'message' => "Invalid task status transition from {$task->status} to {$validated['status']}.",
            ], 422);
        }

        $task->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Task status updated successfully.',
            'data' => $task->fresh(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasRole('admin') || $user->hasRole('employee'),
            403
        );

        $query = Task::with([
            'assignedUser:id,name,email',
            'capa:id,finding_id,action,status',
        ]);

        if (! $user->hasRole('admin')) {
            $query->where('assigned_to', $user->id);
        }

        $tasks = $query
            ->latest()
            ->get();

        return response()->json([
            'data' => $tasks,
        ]);
    }

    public function show(
        Request $request,
        Task $task
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user->hasRole('admin')
            || (
                $user->hasRole('employee')
                && (int) $task->assigned_to === (int) $user->id
            ),
            403
        );

        $task->load([
            'assignedUser:id,name,email',
            'capa:id,finding_id,action,status',
        ]);

        return response()->json([
            'data' => $task,
        ]);
    }
}
