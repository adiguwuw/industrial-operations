<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Checklist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\SafetyInspection;
use App\Models\SafetyInspectionItem;
use App\Models\RiskAssessment;
use App\Models\Finding;

class ChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_checklist(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/checklists', [
                'name' => 'Pemeriksaan Keselamatan Area Produksi',
                'description' => 'Checklist pemeriksaan rutin area produksi',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('checklists', [
            'name' => 'Pemeriksaan Keselamatan Area Produksi',
        ]);
    }

    public function test_employee_cannot_create_checklist(): void
    {
        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $employee = User::factory()->create();

        $employee->assignRole('employee');

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson('/api/checklists', [
                'name' => 'Checklist Tidak Sah',
                'description' => 'Employee mencoba membuat checklist',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('checklists', [
            'name' => 'Checklist Tidak Sah',
        ]);
    }

    public function test_admin_cannot_create_checklist_without_name(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/checklists', [
                'description' => 'Checklist tanpa nama',
            ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'name',
        ]);

        $this->assertDatabaseMissing('checklists', [
            'description' => 'Checklist tanpa nama',
        ]);
    }

    public function test_admin_can_add_item_to_checklist(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Pemeriksaan Keselamatan Area Produksi',
            'description' => 'Checklist pemeriksaan rutin area produksi',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/checklists/{$checklist->id}/items", [
                'question' => 'APAR tersedia dan mudah diakses?',
                'sort_order' => 1,
                'is_required' => true,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('checklist_items', [
            'checklist_id' => $checklist->id,
            'question' => 'APAR tersedia dan mudah diakses?',
            'sort_order' => 1,
            'is_required' => true,
        ]);
    }

    public function test_admin_cannot_add_checklist_item_without_question(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Pemeriksaan Keselamatan Area Produksi',
            'description' => 'Checklist pemeriksaan rutin area produksi',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/checklists/{$checklist->id}/items", [
                'sort_order' => 1,
                'is_required' => true,
            ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'question',
        ]);

        $this->assertDatabaseCount('checklist_items', 0);
    }

    public function test_admin_cannot_add_checklist_item_with_invalid_sort_order(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Pemeriksaan Keselamatan Area Produksi',
            'description' => 'Checklist pemeriksaan rutin area produksi',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/checklists/{$checklist->id}/items", [
                'question' => 'APAR tersedia dan mudah diakses?',
                'sort_order' => 0,
                'is_required' => true,
            ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'sort_order',
        ]);

        $this->assertDatabaseCount('checklist_items', 0);
    }

    public function test_admin_can_create_safety_inspection(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Pemeriksaan Keselamatan Area Produksi',
            'description' => 'Checklist pemeriksaan rutin area produksi',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/inspections', [
                'checklist_id' => $checklist->id,
                'location' => 'Area Produksi A',
                'inspection_date' => '2026-09-17',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('safety_inspections', [
                'checklist_id' => $checklist->id,
                'location' => 'Area Produksi A',
                'status' => 'draft',
            ]);

        $this->assertDatabaseHas('safety_inspections', [
                'checklist_id' => $checklist->id,
                'inspection_date' => '2026-09-17 00:00:00',
            ]);
    }

    public function test_creating_inspection_creates_snapshot_of_checklist_items(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Pemeriksaan Keselamatan Area Produksi',
            'description' => 'Checklist pemeriksaan rutin area produksi',
        ]);

        $checklist->items()->createMany([
            [
                'question' => 'APAR tersedia dan mudah diakses?',
                'sort_order' => 1,
                'is_required' => true,
            ],
            [
                'question' => 'Jalur evakuasi tidak terhalang?',
                'sort_order' => 2,
                'is_required' => true,
            ],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/inspections', [
                'checklist_id' => $checklist->id,
                'location' => 'Area Produksi A',
                'inspection_date' => '2026-09-17',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseCount('safety_inspection_items', 2);

        $this->assertDatabaseHas('safety_inspection_items', [
            'question' => 'APAR tersedia dan mudah diakses?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $this->assertDatabaseHas('safety_inspection_items', [
            'question' => 'Jalur evakuasi tidak terhalang?',
            'sort_order' => 2,
            'is_required' => true,
        ]);
    }

    public function test_admin_can_record_inspection_item_result(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Pemeriksaan Keselamatan Area Produksi',
            'description' => 'Checklist pemeriksaan rutin area produksi',
        ]);

        $checklist->items()->create([
            'question' => 'APAR tersedia dan mudah diakses?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Area Produksi A',
            'inspection_date' => '2026-09-17',
            'status' => 'draft',
        ]);

        $checklistItem = $checklist->items()->first();

        $inspectionItem = $inspection->items()->create([
            'checklist_item_id' => $checklistItem->id,
            'question' => $checklistItem->question,
            'sort_order' => $checklistItem->sort_order,
            'is_required' => $checklistItem->is_required,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/inspections/{$inspection->id}/items/{$inspectionItem->id}",
                [
                    'result' => 'PASS',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('safety_inspection_items', [
            'id' => $inspectionItem->id,
            'result' => 'PASS',
        ]);
    }

    public function test_admin_cannot_record_invalid_inspection_item_result(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Pemeriksaan Keselamatan Area Produksi',
        ]);

        $checklistItem = $checklist->items()->create([
            'question' => 'APAR tersedia?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Area Produksi A',
            'inspection_date' => '2026-09-17',
            'status' => 'draft',
        ]);

        $inspectionItem = $inspection->items()->create([
            'checklist_item_id' => $checklistItem->id,
            'question' => $checklistItem->question,
            'sort_order' => $checklistItem->sort_order,
            'is_required' => $checklistItem->is_required,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/inspections/{$inspection->id}/items/{$inspectionItem->id}",
                [
                    'result' => 'INVALID',
                ]
            );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'result',
        ]);

        $this->assertDatabaseMissing('safety_inspection_items', [
            'id' => $inspectionItem->id,
            'result' => 'INVALID',
        ]);
    }

    public function test_admin_can_create_finding_from_failed_inspection_item(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Pemeriksaan Keselamatan Area Produksi',
        ]);

        $checklistItem = $checklist->items()->create([
            'question' => 'Jalur evakuasi tidak terhalang?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Area Produksi A',
            'inspection_date' => '2026-09-17',
            'status' => 'draft',
        ]);

        $inspectionItem = $inspection->items()->create([
            'checklist_item_id' => $checklistItem->id,
            'question' => $checklistItem->question,
            'sort_order' => $checklistItem->sort_order,
            'is_required' => $checklistItem->is_required,
            'result' => 'FAIL',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/inspections/{$inspection->id}/items/{$inspectionItem->id}/findings",
                [
                    'description' => 'Jalur evakuasi terhalang material produksi.',
                ]
            );

        $response->assertStatus(201);

        $this->assertDatabaseHas('findings', [
            'safety_inspection_id' => $inspection->id,
            'safety_inspection_item_id' => $inspectionItem->id,
            'description' => 'Jalur evakuasi terhalang material produksi.',
        ]);
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
}