<?php

namespace Darejer\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstallCommand extends Command
{
    protected $signature = 'darejer:install {--fresh : Drop and recreate all Darejer roles/permissions}';

    protected $description = 'Install Darejer — seed roles, permissions, and publish assets.';

    public function handle(): int
    {
        $this->info('Installing Darejer...');

        $this->call('vendor:publish', ['--tag' => 'darejer-config', '--force' => false]);

        if (! class_exists(Role::class)) {
            $this->warn('Spatie Permissions not installed — skipping role/permission seeding.');
            $this->warn('Install with: composer require spatie/laravel-permission');

            return self::SUCCESS;
        }

        $this->seedRolesAndPermissions();

        $this->info('✓ Darejer installed successfully.');
        $this->line('');
        $this->line('  Next steps:');
        $this->line('  1. Add <info>HasRoles</info> and <info>HasDarejerPermissions</info> to your User model.');
        $this->line('  2. Define your navigation in <info>AppServiceProvider::boot()</info>.');

        return self::SUCCESS;
    }

    protected function seedRolesAndPermissions(): void
    {
        $roleModel = Role::class;
        $permModel = Permission::class;

        $this->info('Seeding roles and permissions...');

        $roles = [
            'super-admin' => 'Full access to everything — bypasses all permission checks.',
            'admin' => 'Full access within configured permissions.',
            'manager' => 'Can view, create and update. Cannot delete.',
            'viewer' => 'Read-only access.',
        ];

        foreach ($roles as $name => $description) {
            $roleModel::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
            );
            $this->line("  Role: <info>{$name}</info> — {$description}");
        }

        $permissions = [
            'darejer.access',
            'darejer.settings',

            'users.viewAny', 'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.viewAny', 'roles.create', 'roles.update', 'roles.delete',
        ];

        foreach ($permissions as $perm) {
            $permModel::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $this->line("  Permission: <info>{$perm}</info>");
        }

        $adminRole = $roleModel::findByName('admin', 'web');
        $adminRole->syncPermissions($permModel::all());

        $viewerRole = $roleModel::findByName('viewer', 'web');
        $viewerPerms = $permModel::where('name', 'like', '%.viewAny')
            ->orWhere('name', 'like', '%.view')
            ->get();
        $viewerRole->syncPermissions($viewerPerms);

        $this->info('✓ Roles and permissions seeded.');
    }
}
