<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;

class CreateFirstAdmin extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Interactively create the first administrator account';

    public function handle(): int
    {
        $adminRole = $this->adminRole();

        if (! $adminRole) {
            $this->error('The admin role is missing. Run the RolePermissionSeeder first.');

            return self::FAILURE;
        }

        if ($this->adminExists($adminRole)) {
            $this->error('An admin account already exists. No changes were made.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Admin name'));

        if ($name === '') {
            $this->error('The admin name is required.');

            return self::FAILURE;
        }

        $email = strtolower(trim((string) $this->ask('Admin email')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('That email address is already registered. No changes were made.');

            return self::FAILURE;
        }

        $password = $this->secret('Password (minimum 8 characters)');

        if (strlen($password) < 8) {
            $this->error('The password must be at least 8 characters long.');

            return self::FAILURE;
        }

        $passwordConfirmation = $this->secret('Confirm password');

        if (! hash_equals($password, $passwordConfirmation)) {
            $this->error('The passwords do not match. No changes were made.');

            return self::FAILURE;
        }

        if (! $this->confirm("Create the first admin account for {$email}?", false)) {
            $this->warn('Canceled. No changes were made.');

            return self::SUCCESS;
        }

        $user = DB::transaction(function () use ($name, $email, $password): ?User {
            $adminRole = $this->adminRole(lockForUpdate: true);

            if (! $adminRole) {
                throw new RuntimeException('The admin role is missing. Run the RolePermissionSeeder first.');
            }

            if ($this->adminExists($adminRole)) {
                return null;
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);

            $user->assignRole($adminRole);

            return $user;
        });

        if (! $user) {
            $this->error('An admin account was created while this command was running. No new account was added.');

            return self::FAILURE;
        }

        $this->info("Admin account created for {$user->email}.");

        return self::SUCCESS;
    }

    private function adminRole(bool $lockForUpdate = false): ?Role
    {
        $query = Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function adminExists(Role $adminRole): bool
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereKey($adminRole->getKey()))
            ->exists();
    }
}