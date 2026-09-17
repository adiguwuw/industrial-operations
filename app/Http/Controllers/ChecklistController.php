<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\SafetyInspection;
use App\Models\SafetyInspectionItem;
use App\Models\Finding;
use App\Models\RiskAssessment;
use App\Models\Capa;
use App\Models\Task;

class ChecklistController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $checklist = Checklist::create($validated);

        return response()->json([
            'message' => 'Checklist created successfully.',
            'data' => $checklist,
        ], 201);
    }

    public function storeItem(Request $request, Checklist $checklist): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'question' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_required' => ['required', 'boolean'],
        ]);

        $item = $checklist->items()->create($validated);

        return response()->json([
            'message' => 'Checklist item created successfully.',
            'data' => $item,
        ], 201);
    }

    public function storeInspection(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'checklist_id' => ['required', 'exists:checklists,id'],
            'location' => ['required', 'string', 'max:255'],
            'inspection_date' => ['required', 'date'],
        ]);

        $checklist = Checklist::with('items')
            ->findOrFail($validated['checklist_id']);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => $validated['location'],
            'inspection_date' => $validated['inspection_date'],
            'status' => 'draft',
        ]);

        foreach ($checklist->items as $item) {
            $inspection->items()->create([
                'checklist_item_id' => $item->id,
                'question' => $item->question,
                'sort_order' => $item->sort_order,
                'is_required' => $item->is_required,
            ]);
        }

        return response()->json([
            'message' => 'Safety inspection created successfully.',
            'data' => $inspection->load('items'),
        ], 201);
    }

    public function updateInspectionItemResult(
        Request $request,
        SafetyInspection $inspection,
        SafetyInspectionItem $item
    ): JsonResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        abort_unless(
            (int) $item->safety_inspection_id === (int) $inspection->id,
            404
        );

        $validated = $request->validate([
            'result' => ['required', 'in:PASS,FAIL,N/A'],
        ]);

        $item->update([
            'result' => $validated['result'],
        ]);

        return response()->json([
            'message' => 'Inspection item result updated successfully.',
            'data' => $item->fresh(),
        ]);
    }

    public function storeFinding(
        Request $request,
        SafetyInspection $inspection,
        SafetyInspectionItem $item
    ): JsonResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        abort_unless(
            (int) $item->safety_inspection_id === (int) $inspection->id,
            404
        );

        abort_unless($item->result === 'FAIL', 422);

        $validated = $request->validate([
            'description' => ['required', 'string'],
        ]);

        $finding = Finding::create([
            'safety_inspection_id' => $inspection->id,
            'safety_inspection_item_id' => $item->id,
            'description' => $validated['description'],
        ]);

        return response()->json([
            'message' => 'Finding created successfully.',
            'data' => $finding,
        ], 201);
    }

    public function storeRiskAssessment(
        Request $request,
        Finding $finding
    ): JsonResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'hazard' => ['required', 'string'],
            'likelihood' => ['required', 'integer', 'min:1', 'max:5'],
            'severity' => ['required', 'integer', 'min:1', 'max:5'],
            'risk_level' => ['required', 'string', 'in:LOW,MEDIUM,HIGH,EXTREME'],
            'existing_controls' => ['nullable', 'string'],
            'assessment_date' => ['required', 'date'],
        ]);

        $riskAssessment = $finding->riskAssessments()->create($validated);

        return response()->json([
            'message' => 'Risk assessment created successfully.',
            'data' => $riskAssessment,
        ], 201);
    }

    public function storeCapa(
        Request $request,
        Finding $finding
    ): JsonResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'type' => ['required', 'in:corrective,preventive'],
            'action' => ['required', 'string'],
            'responsible_user_id' => ['required', 'exists:users,id'],
            'due_date' => ['required', 'date'],
        ]);

        $capa = $finding->capas()->create([
            ...$validated,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'CAPA created successfully.',
            'data' => $capa,
        ], 201);
    }

    public function updateCapaStatus(
        Request $request,
        Capa $capa
    ): JsonResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'status' => [
                'required',
                'in:open,in_progress,completed,verified',
            ],
        ]);

        $capa->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'CAPA status updated successfully.',
            'data' => $capa->fresh(),
        ]);
    }

    public function verifyCapa(
        Request $request,
        Capa $capa
    ): JsonResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        abort_unless($capa->status === 'completed', 422);

        $validated = $request->validate([
            'verification_notes' => ['required', 'string'],
        ]);

        $capa->update([
            'status' => 'verified',
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'verification_notes' => $validated['verification_notes'],
        ]);

        return response()->json([
            'message' => 'CAPA verified successfully.',
            'data' => $capa->fresh(),
        ]);
    }

    public function storeTask(
        Request $request,
        Capa $capa
    ): JsonResponse {
        abort_unless($request->user()->hasRole('admin'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['required', 'exists:users,id'],
            'due_date' => ['required', 'date'],
        ]);

        $task = $capa->tasks()->create([
            ...$validated,
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Task created successfully.',
            'data' => $task,
        ], 201);
    }
    
}