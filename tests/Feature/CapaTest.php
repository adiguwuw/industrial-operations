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

class CapaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_capa_from_finding(): void
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
                "/api/findings/{$finding->id}/capas",
                [
                    'type' => 'corrective',
                    'action' => 'Memindahkan material dan memastikan jalur evakuasi tetap bebas.',
                    'responsible_user_id' => $admin->id,
                    'due_date' => now()->addDays(7)->toDateString(),
                ]
            );

        $response->assertStatus(201);

        $this->assertDatabaseHas('capas', [
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Memindahkan material dan memastikan jalur evakuasi tetap bebas.',
            'responsible_user_id' => $admin->id,
            'status' => 'open',
        ]);
    }

    public function test_employee_cannot_create_capa_from_finding(): void
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
                "/api/findings/{$finding->id}/capas",
                [
                    'type' => 'corrective',
                    'action' => 'Memindahkan material dari jalur evakuasi.',
                    'responsible_user_id' => $employee->id,
                    'due_date' => now()->addDays(7)->toDateString(),
                ]
            );

        $response->assertStatus(403);
    }

    public function test_admin_cannot_create_capa_with_invalid_values(): void
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
                "/api/findings/{$finding->id}/capas",
                [
                    'type' => 'invalid',
                    'action' => '',
                    'responsible_user_id' => 999999,
                    'due_date' => 'invalid-date',
                ]
            );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'type',
            'action',
            'responsible_user_id',
            'due_date',
        ]);
    }

    public function test_admin_can_start_capa(): void
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

        $capa = $finding->capas()->create([
            'type' => 'corrective',
            'action' => 'Memindahkan material dari jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/capas/{$capa->id}/status",
                [
                    'status' => 'in_progress',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('capas', [
            'id' => $capa->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_employee_cannot_update_capa_status(): void
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

        $capa = $finding->capas()->create([
            'type' => 'corrective',
            'action' => 'Memindahkan material dari jalur evakuasi.',
            'responsible_user_id' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->patchJson(
                "/api/capas/{$capa->id}/status",
                [
                    'status' => 'in_progress',
                ]
            );

        $response->assertStatus(403);
    }

    public function test_admin_can_complete_capa(): void
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

        $capa = $finding->capas()->create([
            'type' => 'corrective',
            'action' => 'Memindahkan material dari jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/capas/{$capa->id}/status",
                [
                    'status' => 'completed',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('capas', [
            'id' => $capa->id,
            'status' => 'completed',
        ]);
    }

    public function test_admin_can_verify_completed_capa(): void
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

        $capa = $finding->capas()->create([
            'type' => 'corrective',
            'action' => 'Memindahkan material dari jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/capas/{$capa->id}/verify",
                [
                    'verification_notes' => 'Jalur evakuasi sudah bebas dari material.',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('capas', [
            'id' => $capa->id,
            'status' => 'verified',
            'verified_by' => $admin->id,
            'verification_notes' => 'Jalur evakuasi sudah bebas dari material.',
        ]);
    }

    public function test_admin_cannot_verify_capa_before_completed(): void
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

        $capa = $finding->capas()->create([
            'type' => 'corrective',
            'action' => 'Memindahkan material dari jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/capas/{$capa->id}/verify",
                [
                    'verification_notes' => 'Mencoba verifikasi sebelum action selesai.',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('capas', [
            'id' => $capa->id,
            'status' => 'in_progress',
        ]);
    }
}