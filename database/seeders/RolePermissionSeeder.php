<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission = [
            'incident.view',
            'incident.view-own',
            'incident.create',
            'incident.update',
            'incident.investigate',
            'incident.resolve',
            'incident.comment',
        ];

        foreach ($permission as $permission) {
           Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web'
           ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $k3Officer = Role::firstOrCreate([
            'name' => 'k3-officer',
            'guard_name' => 'web',
        ]);

        $supervisor = Role::firstOrCreate([
            'name' => 'supervisor',
            'guard_name' => 'web',
        ]);

        $employee = Role::firstOrCreate([
            'name' => 'employee',
            'guard_name' => 'web',
        ]);

        $admin->givePermissionTo($permission);

        $k3Officer->givePermissionTo([
            'incident.view',
            'incident.create',
            'incident.update',
            'incident.investigate',
            'incident.resolve',
            'incident.comment',
        ]);

        $supervisor->givePermissionTo([
            'incident.view',
            'incident.investigate',
            'incident.resolve',
            'incident.comment',
        ]);

        $employee->givePermissionTo([
            'incident.view-own',
            'incident.create',
            'incident.comment',
        ]);
    }
}
