<?php

namespace Modules\SaranaManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class SaranaPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Permissions untuk Sarana didefinisikan di core RolePermissionSeeder.
     * Seeder ini dibiarkan kosong agar tetap compatible dengan SaranaManagementDatabaseSeeder.
     */
    public function run(): void
    {
        // Permissions sarana.manage & sarana.view sudah dibuat dan di-assign
        // melalui Database\Seeders\RolePermissionSeeder.
        if (property_exists($this, 'command') && $this->command) {
            $this->command->info('SaranaPermissionSeeder: permissions dikelola oleh RolePermissionSeeder, tidak ada aksi tambahan.');
        }
    }
}
