<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing menus
        Menu::query()->delete();

        $menus = [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'heroicon-o-home',
                'permission' => null,
                'active_routes' => null,
                'order' => 1,
                'is_active' => true,
            ],
            [
                'label' => 'Manajemen User',
                'route' => 'user-management.index',
                'icon' => 'heroicon-o-users',
                'permission' => 'user.manage',
                'active_routes' => [
                    'user-management.*',
                    'role-management.*',
                    'permission-management.*',
                ],
                'order' => 2,
                'is_active' => true,
            ],
            [
                'label' => 'Notifikasi',
                'route' => 'notifications.index',
                'icon' => 'heroicon-o-bell',
                'permission' => null,
                'active_routes' => [
                    'notifications.*',
                ],
                'order' => 80,
                'is_active' => true,
            ],
            [
                'label' => 'Profile',
                'route' => 'profile.show',
                'icon' => 'heroicon-o-user-circle',
                'permission' => null, // All users can access profile
                'active_routes' => [
                    'profile.*',
                ],
                'order' => 90,
                'is_active' => true,
            ],
            [
                'label' => 'Settings',
                'route' => 'settings.index',
                'icon' => 'heroicon-o-cog-6-tooth',
                'permission' => 'system.settings',
                'active_routes' => [
                    'settings.*',
                ],
                'order' => 99,
                'is_active' => true,
            ],
        ];

        foreach ($menus as $menuData) {
            Menu::create($menuData);
        }

        $this->command->info('✅ Menu seeded successfully!');
    }
}
