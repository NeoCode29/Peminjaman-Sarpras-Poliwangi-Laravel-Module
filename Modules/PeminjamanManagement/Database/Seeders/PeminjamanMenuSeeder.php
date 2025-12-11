<?php

namespace Modules\PeminjamanManagement\Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class PeminjamanMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Single top-level menu: Peminjaman
        Menu::updateOrCreate(
            ['route' => 'peminjaman.index'],
            [
                'label' => 'Peminjaman',
                'icon' => 'heroicon-o-clipboard-document-list',
                'permission' => 'peminjaman.view',
                'active_routes' => [
                    // Hanya route yang benar-benar terkait halaman peminjaman,
                    // tidak termasuk laporan (peminjaman.reports.*) atau export PDF
                    'peminjaman.index',
                    'peminjaman.create',
                    'peminjaman.show',
                    'peminjaman.edit',
                    'peminjaman.cancel',
                    'peminjaman.approvals.*',
                    'peminjaman.approval.*',
                    'peminjaman.validate-*',
                    'peminjaman.upload-*',
                    'peminjaman.assign-units',
                ],
                'order' => 40,
                'is_active' => true,
                'parent_id' => null,
            ]
        );

        // Clear menu cache
        Menu::clearCache();
    }
}
