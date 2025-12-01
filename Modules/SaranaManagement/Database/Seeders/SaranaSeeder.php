<?php

namespace Modules\SaranaManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Entities\Sarana;

class SaranaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get categories
        $elektronik = KategoriSarana::where('nama', 'Elektronik')->first();
        $olahraga = KategoriSarana::where('nama', 'Olahraga')->first();
        $musik = KategoriSarana::where('nama', 'Alat Musik')->first();

        if (!$elektronik || !$olahraga || !$musik) {
            $this->command->error('Please run KategoriSaranaSeeder first!');
            return;
        }

        $saranas = [
            // Elektronik
            [
                'kode_sarana' => 'SRN-0001',
                'nama' => 'Laptop Asus ROG',
                'kategori_id' => $elektronik->id,
                'merk' => 'Asus',
                'spesifikasi' => 'Intel Core i7, RAM 16GB, SSD 512GB',
                'kondisi' => 'baik',
                'status_ketersediaan' => 'tersedia',
                'type' => 'pooled',
                'jumlah_total' => 5,
                'jumlah_tersedia' => 5,
                'jumlah_rusak' => 0,
                'jumlah_maintenance' => 0,
                'jumlah_hilang' => 0,
                'tahun_perolehan' => 2023,
                'nilai_perolehan' => 15000000,
                'lokasi_penyimpanan' => 'Ruang Lab Komputer',
                'keterangan' => 'Untuk keperluan praktikum dan peminjaman mahasiswa',
            ],
            [
                'kode_sarana' => 'SRN-0002',
                'nama' => 'Proyektor Epson EB-X06',
                'kategori_id' => $elektronik->id,
                'merk' => 'Epson',
                'spesifikasi' => '3600 lumens, XGA resolution',
                'kondisi' => 'baik',
                'status_ketersediaan' => 'tersedia',
                'type' => 'pooled',
                'jumlah_total' => 10,
                'jumlah_tersedia' => 10,
                'jumlah_rusak' => 0,
                'jumlah_maintenance' => 0,
                'jumlah_hilang' => 0,
                'tahun_perolehan' => 2022,
                'nilai_perolehan' => 5000000,
                'lokasi_penyimpanan' => 'Ruang Audio Visual',
                'keterangan' => 'Untuk keperluan presentasi dan acara',
            ],
            
            // Olahraga
            [
                'kode_sarana' => 'SRN-0003',
                'nama' => 'Bola Basket Molten',
                'kategori_id' => $olahraga->id,
                'merk' => 'Molten',
                'spesifikasi' => 'Size 7, Official size',
                'kondisi' => 'baik',
                'status_ketersediaan' => 'tersedia',
                'type' => 'pooled',
                'jumlah_total' => 20,
                'jumlah_tersedia' => 20,
                'jumlah_rusak' => 0,
                'jumlah_maintenance' => 0,
                'jumlah_hilang' => 0,
                'tahun_perolehan' => 2023,
                'nilai_perolehan' => 500000,
                'lokasi_penyimpanan' => 'Gudang Olahraga',
                'keterangan' => 'Untuk kegiatan olahraga mahasiswa',
            ],
            [
                'kode_sarana' => 'SRN-0004',
                'nama' => 'Raket Badminton Yonex',
                'kategori_id' => $olahraga->id,
                'merk' => 'Yonex',
                'spesifikasi' => 'Carbon fiber, lightweight',
                'kondisi' => 'baik',
                'status_ketersediaan' => 'tersedia',
                'type' => 'pooled',
                'jumlah_total' => 15,
                'jumlah_tersedia' => 15,
                'jumlah_rusak' => 0,
                'jumlah_maintenance' => 0,
                'jumlah_hilang' => 0,
                'tahun_perolehan' => 2023,
                'nilai_perolehan' => 1500000,
                'lokasi_penyimpanan' => 'Gudang Olahraga',
                'keterangan' => 'Untuk klub badminton',
            ],
            
            // Alat Musik
            [
                'kode_sarana' => 'SRN-0005',
                'nama' => 'Gitar Akustik Yamaha',
                'kategori_id' => $musik->id,
                'merk' => 'Yamaha',
                'spesifikasi' => 'C40, Nylon string',
                'kondisi' => 'baik',
                'status_ketersediaan' => 'tersedia',
                'type' => 'pooled',
                'jumlah_total' => 8,
                'jumlah_tersedia' => 8,
                'jumlah_rusak' => 0,
                'jumlah_maintenance' => 0,
                'jumlah_hilang' => 0,
                'tahun_perolehan' => 2022,
                'nilai_perolehan' => 1200000,
                'lokasi_penyimpanan' => 'Ruang Musik',
                'keterangan' => 'Untuk kegiatan seni dan budaya',
            ],
            [
                'kode_sarana' => 'SRN-0006',
                'nama' => 'Keyboard Casio CTK-3500',
                'kategori_id' => $musik->id,
                'merk' => 'Casio',
                'spesifikasi' => '61 keys, 400 tones',
                'kondisi' => 'baik',
                'status_ketersediaan' => 'tersedia',
                'type' => 'pooled',
                'jumlah_total' => 3,
                'jumlah_tersedia' => 3,
                'jumlah_rusak' => 0,
                'jumlah_maintenance' => 0,
                'jumlah_hilang' => 0,
                'tahun_perolehan' => 2023,
                'nilai_perolehan' => 2500000,
                'lokasi_penyimpanan' => 'Ruang Musik',
                'keterangan' => 'Untuk latihan musik mahasiswa',
            ],
        ];

        foreach ($saranas as $sarana) {
            Sarana::firstOrCreate(
                ['kode_sarana' => $sarana['kode_sarana']],
                $sarana
            );
        }

        $this->command->info('Sarana sample data created successfully.');
    }
}
