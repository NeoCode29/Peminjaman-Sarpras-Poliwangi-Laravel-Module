<?php

namespace Modules\MarkingManagement\Database\Seeders;

use Illuminate\Database\Seeder;

class MarkingManagementDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            MarkingPermissionSeeder::class,
            MarkingMenuSeeder::class,
        ]);
    }
}
