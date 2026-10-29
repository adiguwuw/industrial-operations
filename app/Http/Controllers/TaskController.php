<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            'status' => ['required', 'in:open,in_progress'],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $allowedTransitions = [
            'open' => ['open', 'in_progress'],
            'in_progress' => ['in_progress'],
            'pending_review' => [],
            'validated' => [],
        ];

        if (! in_array($validated['status'], $allowedTransitions[$task->status] ?? [], true)) {
            return response()->json([
                'message' => "Invalid task status transition from {$task->status} to {$validated['status']}.",
            ], 422);
        }

        $updates = [
            'status' => $validated['status'],
        ];

        if (
            $task->status === 'open'
            && $validated['status'] === 'in_progress'
        ) {
            $updates['started_at'] = now();
        }

        $task->update($updates);

        return response()->json([
            'message' => 'Task status updated successfully.',
            'data' => $task->fresh(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user->hasAnyRole(['admin', 'supervisor', 'employee']),
            403
        );

        $query = Task::with([
            'assignedUser:id,name,email',
            'capa:id,finding_id,action,status',
        ]);

        if ($user->hasRole('employee')) {
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
            $user->hasAnyRole(['admin', 'supervisor'])
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

    public function store(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()->hasAnyRole(['admin', 'supervisor']),
            403
        );

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['required', 'exists:users,id'],
            'due_date' => ['required', 'date'],
            'task_photo' => [
                'required',
                'image',
                'mimes:jpeg,png,webp',
                'max:5120',
            ],
        ]);

        try {
            $taskPhotoPath = $request->file('task_photo')->store(
                'task-photos',
                'public'
            );

            if ($taskPhotoPath === false) {
                Log::error('Task photo storage returned false.', [
                    'user_id' => $request->user()->id,
                    'disk' => 'public',
                ]);

                return response()->json([
                    'message' => 'Failed to store task photo.',
                ], 500);
            }
        } catch (\Throwable $exception) {
            Log::error('Task photo upload failed.', [
                'user_id' => $request->user()->id,
                'disk' => 'public',
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to store task photo.',
            ], 500);
        }

        $task = Task::create([
            'capa_id' => null,
            'source_type' => 'direct',
            'source_id' => null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'assigned_to' => $validated['assigned_to'],
            'assigned_by' => $request->user()->id,
            'due_date' => $validated['due_date'],
            'status' => 'open',
            'task_photo_path' => $taskPhotoPath,
        ]);

        return response()->json([
            'message' => 'Task created successfully.',
            'data' => $task,
        ], 201);
    }

    public function submit(Request $request, Task $task): JsonResponse
    {
        abort_unless(
            $request->user()->hasRole('employee')
            && (int) $task->assigned_to === (int) $request->user()->id,
            403
        );

        abort_unless($task->status === 'in_progress', 422);

        $validated = $request->validate([
            'before_photo' => [
                'required',
                'image',
                'mimes:jpeg,png,webp',
                'max:5120',
            ],
            'after_photo' => [
                'required',
                'image',
                'mimes:jpeg,png,webp',
                'max:5120',
            ],
            'completion_notes' => ['required', 'string', 'max:5000'],
        ]);

        $beforePhotoPath = $request->file('before_photo')
            ->store('task-evidence/before', 'public');

        $afterPhotoPath = $request->file('after_photo')
            ->store('task-evidence/after', 'public');

       $task->update([
            'before_photo_path' => $beforePhotoPath,
            'after_photo_path' => $afterPhotoPath,
            'completion_notes' => $validated['completion_notes'],
            'submitted_at' => now(),
            'status' => 'pending_review',
            'validated_by' => null,
            'validated_at' => null,
            'validation_notes' => null,
        ]);

        return response()->json([
            'message' => 'Task submitted for review.',
            'data' => $task->fresh(),
        ]);
    }

    public function review(Request $request, Task $task): JsonResponse
    {
        abort_unless(
            $request->user()->hasAnyRole(['admin', 'supervisor']),
            403
        );

        abort_unless($task->status === 'pending_review', 422);

        $validated = $request->validate([
            'decision' => ['required', 'in:validate,revise'],
            'validation_notes' => ['required', 'string', 'max:5000'],
        ]);

        if ($validated['decision'] === 'validate') {
            $task->update([
                'status' => 'validated',
                'validated_by' => $request->user()->id,
                'validated_at' => now(),
                'validation_notes' => $validated['validation_notes'],
            ]);
        } else {
            $task->update([
                'status' => 'in_progress',
                'validation_notes' => $validated['validation_notes'],
            ]);
        }

        return response()->json([
            'message' => $validated['decision'] === 'validate'
                ? 'Task validated successfully.'
                : 'Task revision requested.',
            'data' => $task->fresh(),
        ]);
    }
}
