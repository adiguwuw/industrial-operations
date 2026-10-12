<?php

namespace Tests\Feature;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\SafetyInspection;
use App\Models\SafetyInspectionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InspectionTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function createInspection(): SafetyInspection
    {
        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan Produksi',
            'description' => 'Checklist untuk pengujian inspection.',
        ]);

        $checklistItem = ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $inspection = SafetyInspection::create([
            'checklist_id' => $checklist->id,
            'location' => 'Area Produksi',
            'inspection_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        SafetyInspectionItem::create([
            'safety_inspection_id' => $inspection->id,
            'checklist_item_id' => $checklistItem->id,
            'question' => $checklistItem->question,
            'sort_order' => 1,
            'is_required' => true,
        ]);

        return $inspection;
    }

    public function test_admin_can_list_inspections(): void
    {
        $admin = $this->createAdmin();

        $inspection = $this->createInspection();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/inspections');

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.0.id',
            $inspection->id
        );

        $response->assertJsonPath(
            'data.0.location',
            'Area Produksi'
        );

        $response->assertJsonPath(
            'data.0.status',
            'draft'
        );

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'checklist_id',
                    'location',
                    'inspection_date',
                    'status',
                    'items',
                ],
            ],
        ]);
    }

    public function test_admin_can_view_inspection_detail(): void
    {
        $admin = $this->createAdmin();

        $inspection = $this->createInspection();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/inspections/{$inspection->id}");

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.id',
            $inspection->id
        );

        $response->assertJsonPath(
            'data.location',
            'Area Produksi'
        );

        $response->assertJsonPath(
            'data.checklist.id',
            $inspection->checklist_id
        );

        $response->assertJsonStructure([
            'data' => [
                'id',
                'checklist_id',
                'location',
                'inspection_date',
                'status',
                'checklist',
                'items',
            ],
        ]);
    }

    public function test_employee_cannot_list_inspections(): void
    {
        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $response = $this->actingAs($employee, 'sanctum')
            ->getJson('/api/inspections');

        $response->assertStatus(403);
    }

    public function test_employee_cannot_view_inspection_detail(): void
    {
        Role::create([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $inspection = $this->createInspection();

        $response = $this->actingAs($employee, 'sanctum')
            ->getJson("/api/inspections/{$inspection->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_create_safety_inspection_with_image(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan Produksi',
            'description' => 'Checklist untuk pengujian inspection.',
        ]);

        $checklist->items()->create([
            'question' => 'Apakah jalur evakuasi aman?',
            'sort_order' => 1,
            'is_required' => true,
        ]);

        $image = UploadedFile::fake()->image(
            'inspection.jpg',
            1200,
            800
        );

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/inspections', [
                'checklist_id' => $checklist->id,
                'location' => 'Area Produksi',
                'inspection_date' => '2026-09-23',
                'image' => $image,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('safety_inspections', [
            'checklist_id' => $checklist->id,
            'location' => 'Area Produksi',
            'status' => 'draft',
        ]);

        $inspection = SafetyInspection::latest('id')->first();

        $this->assertNotNull($inspection->image_path);

        Storage::disk('public')->assertExists(
            $inspection->image_path
        );
    }

    public function test_admin_cannot_create_safety_inspection_with_invalid_image(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan Produksi',
        ]);

        $file = UploadedFile::fake()->create(
            'document.pdf',
            100,
            'application/pdf'
        );

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/inspections', [
                'checklist_id' => $checklist->id,
                'location' => 'Area Produksi',
                'inspection_date' => '2026-09-23',
                'image' => $file,
            ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'image',
        ]);

        $this->assertDatabaseCount(
            'safety_inspections',
            0
        );
    }

    public function test_admin_cannot_create_safety_inspection_with_image_larger_than_5_mb(): void
    {
        Storage::fake('public');

        $admin = $this->createAdmin();

        $checklist = Checklist::create([
            'name' => 'Checklist Keselamatan Produksi',
        ]);

        $image = UploadedFile::fake()->create(
            'inspection.jpg',
            6 * 1024,
            'image/jpeg'
        );

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/inspections', [
                'checklist_id' => $checklist->id,
                'location' => 'Area Produksi',
                'inspection_date' => '2026-09-23',
                'image' => $image,
            ]);

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'image',
        ]);

        $this->assertDatabaseCount(
            'safety_inspections',
            0
        );
    }
}