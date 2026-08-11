<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;


class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate([
            'email' => 'admin@indops.test'
        ], [
            'name' => 'Indops admin',
            'password' => bcrypt('password'),
        ]);

        $admin->syncRoles(['admin']);

        $k3Officer = User::updateOrCreate([
            'email' => 'k3indops@test'
        ], [
            'name' => 'Indops K3 Officer',
            'password' => bcrypt('password'),
        ]);

        $k3Officer->syncRoles(['k3-officer']);

        $supervisor = User::updateOrCreate([
            'email' => 'supervisor@indops.test'
        ], [
            'name' => 'Indops Supervisor',
            'password' => bcrypt('password'),
        ]);

        $supervisor->syncRoles(['supervisor']);

        $employee = User::updateOrCreate([
            'email' => 'employee@indops.test'
        ], [
            'name' => 'Indops Employee',
            'password' => bcrypt('password'),
        ]);

        $employee->syncRoles(['employee']);
    }
}
