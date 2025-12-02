<?php

namespace Modules\MarkingManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MarkingPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for marking
        $permissions = [
            'marking.manage',
            'marking.create',
            'marking.override',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        // Assign permissions to Admin Sarpras role
        $adminRole = Role::where('name', 'Admin Sarpras')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'marking.manage',
                'marking.create',
                'marking.override',
            ]);
        }

        // Assign create permission to Mahasiswa and Staff roles
        $mahasiswaRole = Role::where('name', 'Mahasiswa')->first();
        if ($mahasiswaRole) {
            $mahasiswaRole->givePermissionTo('marking.create');
        }

        $staffRole = Role::where('name', 'Staff')->first();
        if ($staffRole) {
            $staffRole->givePermissionTo('marking.create');
        }

        $this->command->info('Marking permissions seeded successfully.');
    }
}
