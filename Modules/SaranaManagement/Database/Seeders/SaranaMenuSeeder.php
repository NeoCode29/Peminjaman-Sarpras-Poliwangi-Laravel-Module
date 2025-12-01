<?php

namespace Modules\SaranaManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class SaranaMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create parent menu for Sarana Management
        $saranaMenu = Menu::firstOrCreate(
            ['route' => 'sarana.index'],
            [
                'label' => 'Manajemen Sarana',
                'icon' => 'heroicon-o-cube',
                'permission' => 'sarpras.manage',
                'order' => 10,
                'is_active' => true,
                'parent_id' => null,
                // Aktif untuk semua route sarana dan kategori sarana
                'active_routes' => ['sarana.*', 'kategori-sarana.*'],
            ]
        );

        $this->command->info('Sarana menu created successfully.');
    }
}
