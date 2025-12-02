<?php

namespace Modules\PrasaranaManagement\Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class PrasaranaMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create parent menu for Prasarana Management
        $menu = Menu::firstOrCreate(
            ['route' => 'prasarana.index'],
            [
                'label' => 'Manajemen Prasarana',
                'icon' => 'heroicon-o-building-office',
                'permission' => 'sarpras.manage',
                'order' => 11,
                'is_active' => true,
                'parent_id' => null,
                // Aktif untuk semua route prasarana dan kategori prasarana
                'active_routes' => ['prasarana.*', 'kategori-prasarana.*'],
            ]
        );

        $this->command?->info('Prasarana menu created successfully.');
    }
}
