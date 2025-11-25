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
            AdminUserSeeder::class,
            MenuSeeder::class,
            SystemSettingSeeder::class,
            NotificationSeeder::class,
        ]);
    }
}
