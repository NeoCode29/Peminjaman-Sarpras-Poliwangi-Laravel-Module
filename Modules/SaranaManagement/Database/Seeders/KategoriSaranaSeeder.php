<?php

namespace Modules\SaranaManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SaranaManagement\Entities\KategoriSarana;

class KategoriSaranaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoris = [
            [
                'nama' => 'Elektronik',
                'deskripsi' => 'Peralatan elektronik seperti laptop, proyektor, dll',
            ],
            [
                'nama' => 'Olahraga',
                'deskripsi' => 'Peralatan olahraga seperti bola, raket, dll',
            ],
            [
                'nama' => 'Alat Musik',
                'deskripsi' => 'Peralatan musik seperti gitar, keyboard, dll',
            ],
            [
                'nama' => 'Furniture',
                'deskripsi' => 'Furniture seperti meja, kursi, lemari',
            ],
            [
                'nama' => 'Alat Tulis',
                'deskripsi' => 'Alat tulis dan perlengkapan kantor',
            ],
        ];

        foreach ($kategoris as $kategori) {
            KategoriSarana::firstOrCreate(
                ['nama' => $kategori['nama']],
                [
                    'deskripsi' => $kategori['deskripsi'],
                ]
            );
        }

        $this->command->info('Kategori sarana sample data created successfully.');
    }
}
