<?php

namespace Modules\SaranaManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class SaranaManagementDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Model::unguard();

        // Seed permissions first
        $this->call(SaranaPermissionSeeder::class);
        
        // Seed menu
        $this->call(SaranaMenuSeeder::class);
        
        // Seed sample data
        $this->call(KategoriSaranaSeeder::class);
        $this->call(SaranaSeeder::class);

        Model::reguard();
    }
}
