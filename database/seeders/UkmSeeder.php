<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UkmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ukm = [
            ['nama' => 'MPM', 'deskripsi' => 'Majelis Permusyawaratan Mahasiswa'],
            ['nama' => 'BEM', 'deskripsi' => 'Badan Eksekutif Mahasiswa'],
            ['nama' => 'HMJ SIPIL', 'deskripsi' => 'Himpunan Mahasiswa Jurusan Teknik Sipil'],
            ['nama' => 'HMJ MESIN', 'deskripsi' => 'Himpunan Mahasiswa Jurusan Teknik Mesin'],
            ['nama' => 'HMJ TI', 'deskripsi' => 'Himpunan Mahasiswa Jurusan Teknologi Informasi'],
            ['nama' => 'HMJ TANI', 'deskripsi' => 'Himpunan Mahasiswa Jurusan Pertanian'],
            ['nama' => 'HMJ PARIWISATA', 'deskripsi' => 'Himpunan Mahasiswa Jurusan Pariwisata'],
            ['nama' => 'FORBIM', 'deskripsi' => 'Forum Bimbingan dan Konseling'],
            ['nama' => 'GENIWANGI', 'deskripsi' => 'Generasi Seni Budaya Banyuwangi'],
            ['nama' => 'PERS', 'deskripsi' => 'Unit Kegiatan Mahasiswa Pers Kampus'],
            ['nama' => 'KWU', 'deskripsi' => 'Kewirausahaan'],
            ['nama' => 'KSR', 'deskripsi' => 'Korps Sukarela'],
            ['nama' => 'OLAHRAGA', 'deskripsi' => 'Unit Kegiatan Mahasiswa Olahraga'],
            ['nama' => 'MAPALA', 'deskripsi' => 'Mahasiswa Pecinta Alam'],
            ['nama' => 'RPB', 'deskripsi' => 'Riset, Pengabdian dan Bahasa'],
            ['nama' => 'RACANA', 'deskripsi' => 'Gerakan Pramuka Racana'],
            ['nama' => 'MENWA', 'deskripsi' => 'Resimen Mahasiswa'],
            ['nama' => 'IMAM', 'deskripsi' => 'Ikatan Mahasiswa Muslim'],
            ['nama' => 'MEDSEN', 'deskripsi' => 'Media Seni'],
        ];

        foreach ($ukm as $data) {
            DB::table('ukm')->insert([
                'nama' => $data['nama'],
                'deskripsi' => $data['deskripsi'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('UKM seeded successfully: ' . count($ukm) . ' records');
    }
}
