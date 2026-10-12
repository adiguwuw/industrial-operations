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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
            ->post("/api/tasks/{$task->id}/submit", [
                'before_photo' => UploadedFile::fake()->image('before.jpg'),
                'after_photo' => UploadedFile::fake()->image('after.jpg'),
                'completion_notes' => 'Jalur evakuasi sudah dibersihkan.',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $employee->id,
            'status' => 'pending_review',
            'completion_notes' => 'Jalur evakuasi sudah dibersihkan.',
        ]);
    }

    public function test_assigned_employee_can_complete_task_with_photo(): void
    {
        Storage::fake('public');

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
            'status' => 'in_progress',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'in_progress',
        ]);

        $beforePhoto = UploadedFile::fake()->image('before.jpg');
        $afterPhoto = UploadedFile::fake()->image('after.jpg');

        $response = $this->actingAs($employee, 'sanctum')
            ->post("/api/tasks/{$task->id}/submit", [
                'before_photo' => $beforePhoto,
                'after_photo' => $afterPhoto,
                'completion_notes' => 'Perbaikan sudah dilakukan.',
            ]);

        $response->assertOk();

        $task->refresh();

        $this->assertEquals('pending_review', $task->status);
        $this->assertNotNull($task->before_photo_path);
        $this->assertNotNull($task->after_photo_path);

        Storage::disk('public')->assertExists($task->before_photo_path);
        Storage::disk('public')->assertExists($task->after_photo_path);
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

    public function test_assigned_employee_cannot_complete_task_with_invalid_photo(): void
    {
        Storage::fake('public');

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
            'status' => 'in_progress',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'in_progress',
        ]);

        $invalidPhoto = UploadedFile::fake()->create(
            'task-evidence.pdf',
            100,
            'application/pdf'
        );

        $response = $this->actingAs($employee, 'sanctum')
            ->post(
                "/api/tasks/{$task->id}/status",
                [
                    '_method' => 'PATCH',
                    'status' => 'completed',
                    'photo' => $invalidPhoto,
                ]
            );

        $response->assertStatus(422);

        $task->refresh();

        $this->assertEquals('in_progress', $task->status);
        $this->assertNull($task->evidence_photo_path);
    }


    public function test_assigned_employee_cannot_complete_task_with_photo_larger_than_5_mb(): void
    {
        Storage::fake('public');

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
            'status' => 'in_progress',
        ]);

        $task = Task::create([
            'capa_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'description' => 'Memindahkan material yang menghalangi jalur evakuasi.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'in_progress',
        ]);

        $largePhoto = UploadedFile::fake()->create(
            'task-evidence.jpg',
            6 * 1024,
            'image/jpeg'
        );

        $response = $this->actingAs($employee, 'sanctum')
            ->post(
                "/api/tasks/{$task->id}/status",
                [
                    '_method' => 'PATCH',
                    'status' => 'completed',
                    'photo' => $largePhoto,
                ]
            );

        $response->assertStatus(422);

        $task->refresh();

        $this->assertEquals('in_progress', $task->status);
        $this->assertNull($task->evidence_photo_path);
    }

    public function test_admin_can_create_direct_task(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $employee = User::factory()->create();

        $admin->assignRole('admin');
        $employee->assignRole('employee');

        Storage::fake('public');

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/tasks', [
                'title' => 'Cek APAR area produksi',
                'description' => 'Memastikan seluruh APAR dalam kondisi baik.',
                'assigned_to' => $employee->id,
                'due_date' => now()->addDays(7)->toDateString(),
                'task_photo' => UploadedFile::fake()->image('task-photo.jpg'),
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'capa_id' => null,
            'title' => 'Cek APAR area produksi',
            'assigned_to' => $employee->id,
            'status' => 'open',
            'source_type' => 'direct',
            'source_id' => null,
        ]);
    }

    public function test_employee_cannot_create_direct_task(): void
    {
        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson('/api/tasks', [
                'title' => 'Cek APAR area produksi',
                'description' => 'Memastikan seluruh APAR dalam kondisi baik.',
                'assigned_to' => $employee->id,
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('tasks', [
            'title' => 'Cek APAR area produksi',
        ]);
    }

    public function test_admin_cannot_create_direct_task_without_title(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/tasks', [
                'description' => 'Memastikan seluruh APAR dalam kondisi baik.',
                'assigned_to' => $admin->id,
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'title',
        ]);
    }

    public function test_admin_cannot_create_direct_task_with_invalid_assignee(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/tasks', [
                'title' => 'Cek APAR area produksi',
                'description' => 'Memastikan seluruh APAR dalam kondisi baik.',
                'assigned_to' => 999999,
                'due_date' => now()->addDays(7)->toDateString(),
            ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'assigned_to',
        ]);
    }

    public function test_admin_cannot_create_direct_task_without_due_date(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/tasks', [
                'title' => 'Cek APAR area produksi',
                'description' => 'Memastikan seluruh APAR dalam kondisi baik.',
                'assigned_to' => $admin->id,
            ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'due_date',
        ]);
    }

    public function test_admin_can_create_direct_task_without_description(): void
    {
        Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $employee = User::factory()->create();

        $admin->assignRole('admin');
        $employee->assignRole('employee');

        $response = $this->actingAs($admin, 'sanctum')
        ->post('/api/tasks', [
            'title' => 'Cek APAR area produksi',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDays(7)->toDateString(),
            'task_photo' => UploadedFile::fake()->image('task-photo.jpg'),
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'capa_id' => null,
            'title' => 'Cek APAR area produksi',
            'description' => null,
            'assigned_to' => $employee->id,
            'status' => 'open',
            'source_type' => 'direct',
            'source_id' => null,
        ]);
    }

    public function test_admin_can_create_capa_task_with_source_metadata(): void
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
                    'assigned_to' => $admin->id,
                    'due_date' => now()->addDays(7)->toDateString(),
                ]
            );

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'capa_id' => $capa->id,
            'source_type' => 'capa',
            'source_id' => $capa->id,
            'title' => 'Membersihkan jalur evakuasi',
            'assigned_by' => $admin->id,
        ]);
    }

    public function test_supervisor_can_create_direct_task_with_task_photo(): void
    {
        Storage::fake('public');

        $supervisor = User::factory()->create();
        $employee = User::factory()->create();

        Role::create([
            'name' => 'supervisor',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $supervisor->assignRole('supervisor');
        $employee->assignRole('employee');

        $photo = UploadedFile::fake()->image('task-photo.jpg');

        $response = $this->actingAs($supervisor, 'sanctum')
            ->post('/api/tasks', [
                'title' => 'Perbaiki Mesin Produksi A',
                'description' => 'Perbaiki kondisi belt mesin produksi.',
                'task_photo' => $photo,
                'assigned_to' => $employee->id,
                'due_date' => now()->addDay()->toDateString(),
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Perbaiki Mesin Produksi A',
            'assigned_to' => $employee->id,
            'assigned_by' => $supervisor->id,
            'source_type' => 'direct',
        ]);

        $task = Task::where('title', 'Perbaiki Mesin Produksi A')->firstOrFail();

        Storage::disk('public')->assertExists($task->task_photo_path);

        $this->assertNotNull(
            Task::where('title', 'Perbaiki Mesin Produksi A')->value('task_photo_path')
        );
    }

    public function test_assigned_employee_can_start_open_task(): void
    {
        $employee = User::factory()->create();

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $employee->assignRole('employee');

        $task = Task::create([
            'title' => 'Periksa Mesin Produksi',
            'description' => 'Periksa kondisi mesin sebelum digunakan.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'open',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => 'in_progress',
            ]);

        $response->assertOk();

        $this->assertNotNull($task->fresh()->started_at);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $employee->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_employee_submits_task_for_review_with_before_and_after_photos(): void
    {
        Storage::fake('public');

        $employee = User::factory()->create();

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $employee->assignRole('employee');

        $task = Task::create([
            'title' => 'Perbaiki Mesin Produksi',
            'description' => 'Perbaiki kondisi belt mesin.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'in_progress',
        ]);

        $beforePhoto = UploadedFile::fake()->image('before.jpg');
        $afterPhoto = UploadedFile::fake()->image('after.jpg');

        $response = $this->actingAs($employee, 'sanctum')
            ->post("/api/tasks/{$task->id}/submit", [
                'before_photo' => $beforePhoto,
                'after_photo' => $afterPhoto,
                'completion_notes' => 'Belt mesin sudah diperbaiki.',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'pending_review',
            'completion_notes' => 'Belt mesin sudah diperbaiki.',
        ]);

        $task->refresh();

        Storage::disk('public')->assertExists($task->before_photo_path);
        Storage::disk('public')->assertExists($task->after_photo_path);
    }

    public function test_supervisor_can_validate_task_pending_review(): void
    {
        Storage::fake('public');

        $supervisor = User::factory()->create();
        $employee = User::factory()->create();

        Role::create([
            'name' => 'supervisor',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $supervisor->assignRole('supervisor');
        $employee->assignRole('employee');

        $task = Task::create([
            'capa_id' => null,
            'source_type' => 'direct',
            'source_id' => null,
            'title' => 'Perbaikan Mesin Produksi A',
            'description' => 'Pemeriksaan hasil pekerjaan.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'pending_review',
            'before_photo_path' => 'task-evidence/before/before.jpg',
            'after_photo_path' => 'task-evidence/after/after.jpg',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($supervisor, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}/review", [
                'decision' => 'validate',
                'validation_notes' => 'Pekerjaan sudah sesuai.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'validated');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'validated',
            'validated_by' => $supervisor->id,
            'validation_notes' => 'Pekerjaan sudah sesuai.',
        ]);

        $this->assertNotNull($task->fresh()->validated_at);
    }

    public function test_supervisor_can_request_task_revision(): void
    {
        $supervisor = User::factory()->create();
        $employee = User::factory()->create();

        Role::create([
            'name' => 'supervisor',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $supervisor->assignRole('supervisor');
        $employee->assignRole('employee');

       $task = Task::create([
            'capa_id' => null,
            'source_type' => 'direct',
            'source_id' => null,
            'title' => 'Perbaikan Mesin Produksi B',
            'description' => 'Pekerjaan menunggu revisi.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'pending_review',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($supervisor, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}/review", [
                'decision' => 'revise',
                'validation_notes' => 'Foto sesudah pekerjaan belum jelas.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
            'validation_notes' => 'Foto sesudah pekerjaan belum jelas.',
        ]);
    }

    public function test_employee_cannot_review_task(): void
    {
        $employee = User::factory()->create();

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $employee->assignRole('employee');

        $task = Task::create([
            'capa_id' => null,
            'source_type' => 'direct',
            'source_id' => null,
            'title' => 'Perbaikan Mesin Produksi C',
            'description' => 'Task untuk menguji akses review.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'pending_review',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}/review", [
                'decision' => 'validate',
                'validation_notes' => 'Pekerjaan sudah sesuai.',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'pending_review',
        ]);
    }

    public function test_supervisor_cannot_review_task_not_pending_review(): void
    {
        $supervisor = User::factory()->create();
        $employee = User::factory()->create();

        Role::create([
            'name' => 'supervisor',
            'guard_name' => 'web',
        ]);

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $supervisor->assignRole('supervisor');
        $employee->assignRole('employee');

        $task = Task::create([
            'capa_id' => null,
            'source_type' => 'direct',
            'source_id' => null,
            'title' => 'Perbaikan Mesin Produksi D',
            'description' => 'Task belum disubmit.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($supervisor, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}/review", [
                'decision' => 'validate',
                'validation_notes' => 'Pekerjaan sudah sesuai.',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_employee_cannot_complete_task_directly_without_review(): void
    {
        $employee = User::factory()->create();

        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $employee->assignRole('employee');

        $task = Task::create([
            'capa_id' => null,
            'source_type' => 'direct',
            'source_id' => null,
            'title' => 'Perbaikan Mesin Produksi',
            'description' => 'Task untuk menguji validasi supervisor.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_supervisor_can_list_tasks_for_review(): void
    {
        $supervisor = User::factory()->create();
        $employee = User::factory()->create();

        Role::firstOrCreate([
            'name' => 'supervisor',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $supervisor->assignRole('supervisor');
        $employee->assignRole('employee');

        $task = Task::create([
            'title' => 'Periksa alat pelindung diri',
            'description' => 'Memeriksa kelengkapan APD.',
            'assigned_to' => $employee->id,
            'assigned_by' => $supervisor->id,
            'due_date' => now()->addDays(3)->toDateString(),
            'status' => 'pending_review',
            'source_type' => 'direct',
        ]);

        $response = $this->actingAs($supervisor, 'sanctum')
            ->getJson('/api/tasks');

        $response->assertOk();

        $response->assertJsonFragment([
            'id' => $task->id,
            'title' => 'Periksa alat pelindung diri',
            'status' => 'pending_review',
        ]);
    }

    public function test_supervisor_can_view_task_detail_for_review(): void
    {
        $supervisor = User::factory()->create();
        $employee = User::factory()->create();

        Role::firstOrCreate([
            'name' => 'supervisor',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $supervisor->assignRole('supervisor');
        $employee->assignRole('employee');

        $task = Task::create([
            'title' => 'Periksa alat pelindung diri',
            'description' => 'Memeriksa kelengkapan APD.',
            'assigned_to' => $employee->id,
            'assigned_by' => $supervisor->id,
            'due_date' => now()->addDays(3)->toDateString(),
            'status' => 'pending_review',
            'source_type' => 'direct',
            'before_photo_path' => 'task-evidence/before/before.jpg',
            'after_photo_path' => 'task-evidence/after/after.jpg',
            'completion_notes' => 'APD sudah diperiksa dan ditata.',
        ]);

        $response = $this->actingAs($supervisor, 'sanctum')
            ->getJson("/api/tasks/{$task->id}");

        $response->assertOk();

        $response->assertJsonPath('data.id', $task->id);
        $response->assertJsonPath('data.status', 'pending_review');
        $response->assertJsonPath(
            'data.completion_notes',
            'APD sudah diperiksa dan ditata.'
        );
        $response->assertJsonPath(
            'data.before_photo_path',
            'task-evidence/before/before.jpg'
        );
        $response->assertJsonPath(
            'data.after_photo_path',
            'task-evidence/after/after.jpg'
        );
    }

    public function test_resubmitting_revised_task_clears_previous_review_metadata(): void
    {
        Storage::fake('public');

        $employee = User::factory()->create();

        Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $employee->assignRole('employee');

        $reviewer = User::factory()->create();

        $task = Task::create([
            'title' => 'Perbaikan Mesin Produksi',
            'description' => 'Task yang dikembalikan untuk revisi.',
            'assigned_to' => $employee->id,
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'in_progress',
            'validated_by' => $reviewer->id,
            'validated_at' => now()->subHour(),
            'validation_notes' => 'Bukti sebelumnya belum sesuai.',
        ]);

        $response = $this->actingAs($employee, 'sanctum')
            ->post("/api/tasks/{$task->id}/submit", [
                'before_photo' => UploadedFile::fake()->image('before.jpg'),
                'after_photo' => UploadedFile::fake()->image('after.jpg'),
                'completion_notes' => 'Revisi sudah diselesaikan.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'pending_review')
            ->assertJsonPath('data.validation_notes', null)
            ->assertJsonPath('data.validated_by', null)
            ->assertJsonPath('data.validated_at', null);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'pending_review',
            'validation_notes' => null,
            'validated_by' => null,
            'validated_at' => null,
        ]);
    }
}
