<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            PoliwangiMasterDataSeeder::class,
            UkmSeeder::class,
            AdminUserSeeder::class,
            RoleDummyUsersSeeder::class,
            MenuSeeder::class,
            SystemSettingSeeder::class,
            
            // Module Seeders
            \Modules\SaranaManagement\Database\Seeders\SaranaManagementDatabaseSeeder::class,
            \Modules\PrasaranaManagement\Database\Seeders\PrasaranaManagementDatabaseSeeder::class,
            \Modules\MarkingManagement\Database\Seeders\MarkingManagementDatabaseSeeder::class,
            \Modules\PeminjamanManagement\Database\Seeders\PeminjamanManagementDatabaseSeeder::class,
        ]);
    }
}
