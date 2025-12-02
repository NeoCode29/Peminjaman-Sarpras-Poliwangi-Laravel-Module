<?php

namespace Modules\MarkingManagement\Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MarkingMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Marking menu
        Menu::firstOrCreate(
            ['route' => 'marking.index'],
            [
                'label' => 'Marking',
                'route' => 'marking.index',
                'icon' => 'heroicon-o-bookmark',
                'permission' => 'marking.create',
                'order' => 20,
                'is_active' => true,
                'parent_id' => null,
            ]
        );

        // Clear menu cache
        if (method_exists(Menu::class, 'clearCache')) {
            Menu::clearCache();
        }

        $this->command->info('Marking menu seeded successfully.');
    }
}
