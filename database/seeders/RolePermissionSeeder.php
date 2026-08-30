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
            'incident.view-all',
            'incident.view-own',
            'incident.create',
            'incident.update',
            'incident.investigate',
            'incident.resolve',
            'incident.comment',
            'incident.statistics',
        ];

        foreach ($permission as $permissionName) {
           Permission::firstOrCreate([
            'name' => $permissionName,
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

        $admin->syncPermissions($permission);

        $k3Officer->syncPermissions([
            'incident.view',
            'incident.view-all',
            'incident.create',
            'incident.update',
            'incident.investigate',
            'incident.resolve',
            'incident.comment',
            'incident.statistics',
        ]);

        $supervisor->syncPermissions([
            'incident.view',
            'incident.investigate',
            'incident.resolve',
            'incident.comment',
            'incident.statistics',
        ]);

        $employee->syncPermissions([
            'incident.view-own',
            'incident.create',
            'incident.comment',
        ]);
    }
}
