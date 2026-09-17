<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

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

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public function safetyInspections(): HasMany
    {
        return $this->hasMany(SafetyInspection::class);
    }
}