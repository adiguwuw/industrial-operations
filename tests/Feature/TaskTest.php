<?php

namespace Tests\Feature;

use App\Models\Capa;
use App\Models\Finding;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\SafetyInspection;
use App\Models\SafetyInspectionItem;
use App\Models\User;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_task_from_capa(): void
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

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Memindahkan material dari jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/capas/{$capa->id}/tasks",
                [
                    'title' => 'Membersihkan jalur evakuasi',
                    'description' => 'Memindahkan seluruh material yang menghalangi jalur evakuasi.',
                    'assigned_to' => $admin->id,
                    'due_date' => now()->addDays(7)->toDateString(),
                ]
            );

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'assigned_to' => $admin->id,
            'status' => 'open',
        ]);
    }

    public function test_employee_cannot_create_task_from_capa(): void
    {
        $admin = User::factory()->create();
        $employee = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');
        $employee->assignRole('employee');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson(
                "/api/capas/{$capa->id}/tasks",
                [
                    'title' => 'Membersihkan jalur evakuasi',
                    'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
                    'assigned_to' => $employee->id,
                    'due_date' => now()->addDays(7)->toDateString(),
                ]
            );

        $response->assertStatus(403);

        $this->assertDatabaseMissing('tasks', [
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
        ]);
    }

    public function test_admin_cannot_create_task_without_title(): void
    {
        $admin = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/capas/{$capa->id}/tasks",
                [
                    'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
                    'assigned_to' => $admin->id,
                    'due_date' => now()->addDays(7)->toDateString(),
                ]
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_admin_cannot_create_task_with_invalid_assigned_user(): void
    {
        $admin = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/capas/{$capa->id}/tasks",
                [
                    'title' => 'Membersihkan jalur evakuasi',
                    'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
                    'assigned_to' => 999999,
                    'due_date' => now()->addDays(7)->toDateString(),
                ]
            );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['assigned_to']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_admin_can_start_task(): void
    {
        $admin = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/tasks/{$task->id}/status",
                [
                    'status' => 'in_progress',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_assigned_employee_can_complete_task(): void
    {
        $admin = User::factory()->create();
        $employee = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');
        $employee->assignRole('employee');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->patchJson(
                "/api/tasks/{$task->id}/status",
                [
                    'status' => 'completed',
                ]
            );

        $response->assertStatus(200);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $employee->id,
            'status' => 'completed',
        ]);
    }

    public function test_other_employee_cannot_update_task_status(): void
    {
        $admin = User::factory()->create();
        $assignedEmployee = User::factory()->create();
        $otherEmployee = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');
        $assignedEmployee->assignRole('employee');
        $otherEmployee->assignRole('employee');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $assignedEmployee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $assignedEmployee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($otherEmployee, 'sanctum')
            ->patchJson(
                "/api/tasks/{$task->id}/status",
                [
                    'status' => 'completed',
                ]
            );

        $response->assertStatus(403);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $assignedEmployee->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_completed_task_cannot_be_reopened(): void
    {
        $admin = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/tasks/{$task->id}/status",
                [
                    'status' => 'in_progress',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function test_open_task_cannot_be_completed_directly(): void
    {
        $admin = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/tasks/{$task->id}/status",
                [
                    'status' => 'completed',
                ]
            );

        $response->assertStatus(422);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'open',
        ]);
    }

    public function test_admin_can_list_tasks(): void
    {
        $admin = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/tasks');

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'id' => $task->id,
            'title' => 'Membersihkan jalur evakuasi',
            'status' => 'open',
        ]);    
    }

    public function test_employee_can_only_list_assigned_tasks(): void
    {
        $admin = User::factory()->create();
        $employee = User::factory()->create();
        $otherEmployee = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');
        $employee->assignRole('employee');
        $otherEmployee->assignRole('employee');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $employeeTask = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Task milik employee',
            'description' => 'Task yang ditugaskan kepada employee.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $otherTask = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Task milik employee lain',
            'description' => 'Task yang ditugaskan kepada employee lain.',
            'assigned_to' => $otherEmployee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->getJson('/api/tasks');

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.0.id',
            $employeeTask->id
        );

        $response->assertJsonPath(
            'data.0.title',
            'Task milik employee'
        );

        $response->assertJsonCount(1, 'data');

        $this->assertDatabaseHas('tasks', [
            'id' => $otherTask->id,
            'title' => 'Task milik employee lain',
        ]);

        $this->assertNotEquals(
            $employeeTask->id,
            $otherTask->id
        );
    }

    public function test_user_without_allowed_role_cannot_list_tasks(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tasks');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_task_detail(): void
    {
        $admin = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $admin->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.id',
            $task->id
        );

        $response->assertJsonPath(
            'data.title',
            'Membersihkan jalur evakuasi'
        );

        $response->assertJsonPath(
            'data.status',
            'open'
        );

        $response->assertJsonPath(
            'data.assigned_to',
            $admin->id
        );
    }

    public function test_employee_can_view_assigned_task_detail(): void
    {
        $admin = User::factory()->create();
        $employee = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');
        $employee->assignRole('employee');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.id',
            $task->id
        );

        $response->assertJsonPath(
            'data.title',
            'Membersihkan jalur evakuasi'
        );

        $response->assertJsonPath(
            'data.assigned_to',
            $employee->id
        );
    }

    public function test_other_employee_cannot_view_task_detail(): void
    {
        $admin = User::factory()->create();
        $assignedEmployee = User::factory()->create();
        $otherEmployee = User::factory()->create();

        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $admin->assignRole('admin');
        $assignedEmployee->assignRole('employee');
        $otherEmployee->assignRole('employee');

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan',
            'description' => 'Checklist untuk pengujian Task.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Gudang',
            'inspection_date' => now()->toDateString(),
            'status' => 'completed',
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
            'description' => 'Jalur evakuasi terhalang material.',
        ]);

        $capa = Capa::create([
            'finding_id' => $finding->id,
            'type' => 'corrective',
            'action' => 'Membersihkan jalur evakuasi.',
            'responsible_user_id' => $assignedEmployee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $assignedEmployee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($otherEmployee, 'sanctum')
            ->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }
}