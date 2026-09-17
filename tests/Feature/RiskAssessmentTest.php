<?php

namespace Tests\Feature;

use App\Models\Finding;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\SafetyInspection;
use App\Models\SafetyInspectionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RiskAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_risk_assessment_from_finding(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $checklist = Checklist::create([
            'name' => 'Inspeksi Keselamatan Produksi',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi bebas dari hambatan?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Area Produksi',
            'inspection_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $inspectionItem = SafetyInspectionItem::create([
            'safety_inspection_id' => $inspection->id,
            'checklist_item_id' => $checklistItem->id,
            'question' => $checklistItem->question,
            'sort_order' => 1,
            'is_required' => true,
            'result' => 'FAIL',
        ]);

        $finding = Finding::create([
            'safety_inspection_id' => $inspection->id,
            'safety_inspection_item_id' => $inspectionItem->id,
            'description' => 'Jalur evakuasi terhalang material produksi.',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/findings/{$finding->id}/risk-assessments",
                [
                    'hazard' => 'Terhambatnya evakuasi saat keadaan darurat',
                    'likelihood' => 3,
                    'severity' => 4,
                    'risk_level' => 'HIGH',
                    'existing_controls' => 'Penandaan jalur evakuasi',
                    'assessment_date' => now()->toDateString(),
                ]
            );

        $response->assertStatus(201);

        $this->assertDatabaseHas('risk_assessments', [
            'finding_id' => $finding->id,
            'hazard' => 'Terhambatnya evakuasi saat keadaan darurat',
            'likelihood' => 3,
            'severity' => 4,
            'risk_level' => 'HIGH',
            'existing_controls' => 'Penandaan jalur evakuasi',
        ]);
    }

    public function test_employee_cannot_create_risk_assessment_from_finding(): void
    {
        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $checklist = Checklist::create([
            'name' => 'Inspeksi Keselamatan Produksi',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi bebas dari hambatan?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Area Produksi',
            'inspection_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $inspectionItem = SafetyInspectionItem::create([
            'safety_inspection_id' => $inspection->id,
            'checklist_item_id' => $checklistItem->id,
            'question' => $checklistItem->question,
            'sort_order' => 1,
            'is_required' => true,
            'result' => 'FAIL',
        ]);

        $finding = Finding::create([
            'safety_inspection_id' => $inspection->id,
            'safety_inspection_item_id' => $inspectionItem->id,
            'description' => 'Jalur evakuasi terhalang material produksi.',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson(
                "/api/findings/{$finding->id}/risk-assessments",
                [
                    'hazard' => 'Terhambatnya evakuasi saat keadaan darurat',
                    'likelihood' => 3,
                    'severity' => 4,
                    'risk_level' => 'HIGH',
                    'existing_controls' => 'Penandaan jalur evakuasi',
                    'assessment_date' => now()->toDateString(),
                ]
            );

        $response->assertStatus(403);
    }

    public function test_admin_cannot_create_risk_assessment_with_invalid_values(): void
    {
        $adminRole = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $checklist = Checklist::create([
            'name' => 'Inspeksi Keselamatan Produksi',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi bebas dari hambatan?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Area Produksi',
            'inspection_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $inspectionItem = SafetyInspectionItem::create([
            'safety_inspection_id' => $inspection->id,
            'checklist_item_id' => $checklistItem->id,
            'question' => $checklistItem->question,
            'sort_order' => 1,
            'is_required' => true,
            'result' => 'FAIL',
        ]);

        $finding = Finding::create([
            'safety_inspection_id' => $inspection->id,
            'safety_inspection_item_id' => $inspectionItem->id,
            'description' => 'Jalur evakuasi terhalang material produksi.',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/findings/{$finding->id}/risk-assessments",
                [
                    'hazard' => 'Terhambatnya evakuasi',
                    'likelihood' => 6,
                    'severity' => 0,
                    'risk_level' => 'INVALID',
                    'existing_controls' => 'Penandaan jalur evakuasi',
                    'assessment_date' => now()->toDateString(),
                ]
            );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'likelihood',
            'severity',
            'risk_level',
        ]);
    }
}