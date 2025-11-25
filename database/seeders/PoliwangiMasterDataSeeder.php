<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Position;
use App\Models\Prodi;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PoliwangiMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seed data khusus untuk Poliwangi (Jurusan, Prodi, Units, Positions)
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding data Poliwangi...');

        DB::transaction(function () {
            // Clear existing data first
            $this->clearExistingData();

            // Seed Jurusan
            $this->seedJurusan();

            // Seed Prodi
            $this->seedProdi();

            // Seed Units
            $this->seedUnits();

            // Seed Positions
            $this->seedPositions();

            // Display summary
            $this->displaySummary();
        });

        $this->command->info('✅ Data Poliwangi berhasil di-seed!');
    }

    /**
     * Clear existing data
     */
    private function clearExistingData(): void
    {
        $this->command->info('🗑️  Clearing existing data...');

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Clear in reverse order due to foreign key constraints
        Prodi::query()->delete();
        Jurusan::query()->delete();
        Position::query()->delete();
        Unit::query()->delete();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Seed Jurusan Poliwangi
     */
    private function seedJurusan(): void
    {
        $this->command->info('📚 Seeding 5 jurusan...');

        $jurusanData = [
            [
                'id' => 1,
                'nama_jurusan' => 'TEKNIK SIPIL',
                'deskripsi' => 'Jurusan yang mempelajari teknik sipil dan konstruksi',
            ],
            [
                'id' => 2,
                'nama_jurusan' => 'TEKNIK MESIN',
                'deskripsi' => 'Jurusan yang mempelajari teknik mesin dan manufaktur',
            ],
            [
                'id' => 3,
                'nama_jurusan' => 'BISNIS & INFORMATIKA',
                'deskripsi' => 'Jurusan yang mempelajari bisnis digital dan teknologi informasi',
            ],
            [
                'id' => 4,
                'nama_jurusan' => 'PARIWISATA',
                'deskripsi' => 'Jurusan yang mempelajari pariwisata dan perhotelan',
            ],
            [
                'id' => 5,
                'nama_jurusan' => 'PERTANIAN',
                'deskripsi' => 'Jurusan yang mempelajari pertanian dan agribisnis',
            ],
        ];

        foreach ($jurusanData as $data) {
            Jurusan::create($data);
            $this->command->info("   ✓ {$data['nama_jurusan']}");
        }
    }

    /**
     * Seed Prodi Poliwangi
     */
    private function seedProdi(): void
    {
        $this->command->info('🎓 Seeding 19 program studi...');

        $prodiData = [
            // TEKNIK SIPIL (ID: 1) - 4 prodi
            [
                'id' => 1,
                'nama_prodi' => 'D3 Teknik Sipil',
                'jurusan_id' => 1,
                'jenjang' => 'D3',
                'deskripsi' => 'Program studi D3 Teknik Sipil',
            ],
            [
                'id' => 2,
                'nama_prodi' => 'Manajemen Konstruksi',
                'jurusan_id' => 1,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Manajemen Konstruksi',
            ],
            [
                'id' => 3,
                'nama_prodi' => 'Teknologi Rekayasa Konstruksi Jalan & Jembatan',
                'jurusan_id' => 1,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknologi Rekayasa Konstruksi Jalan & Jembatan',
            ],
            [
                'id' => 4,
                'nama_prodi' => 'Teknologi Rekayasa Konstruksi Bangunan Gedung',
                'jurusan_id' => 1,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknologi Rekayasa Konstruksi Bangunan Gedung',
            ],

            // TEKNIK MESIN (ID: 2) - 3 prodi
            [
                'id' => 5,
                'nama_prodi' => 'Teknik Manufaktur Kapal',
                'jurusan_id' => 2,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknik Manufaktur Kapal',
            ],
            [
                'id' => 6,
                'nama_prodi' => 'Teknologi Rekayasa Otomotif',
                'jurusan_id' => 2,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknologi Rekayasa Otomotif',
            ],
            [
                'id' => 7,
                'nama_prodi' => 'Teknologi Rekayasa Manufaktur',
                'jurusan_id' => 2,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknologi Rekayasa Manufaktur',
            ],

            // BISNIS & INFORMATIKA (ID: 3) - 3 prodi
            [
                'id' => 8,
                'nama_prodi' => 'Bisnis Digital',
                'jurusan_id' => 3,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Bisnis Digital',
            ],
            [
                'id' => 9,
                'nama_prodi' => 'Teknologi Rekayasa Komputer',
                'jurusan_id' => 3,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknologi Rekayasa Komputer',
            ],
            [
                'id' => 10,
                'nama_prodi' => 'Teknologi Rekayasa Perangkat Lunak',
                'jurusan_id' => 3,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknologi Rekayasa Perangkat Lunak',
            ],

            // PARIWISATA (ID: 4) - 3 prodi
            [
                'id' => 11,
                'nama_prodi' => 'Destinasi Pariwisata',
                'jurusan_id' => 4,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Destinasi Pariwisata',
            ],
            [
                'id' => 12,
                'nama_prodi' => 'Pengelolaan Perhotelan',
                'jurusan_id' => 4,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Pengelolaan Perhotelan',
            ],
            [
                'id' => 13,
                'nama_prodi' => 'Manajemen Bisnis Pariwisata',
                'jurusan_id' => 4,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Manajemen Bisnis Pariwisata',
            ],

            // PERTANIAN (ID: 5) - 6 prodi
            [
                'id' => 14,
                'nama_prodi' => 'Agribisnis',
                'jurusan_id' => 5,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Agribisnis',
            ],
            [
                'id' => 15,
                'nama_prodi' => 'Teknologi Produksi Ternak',
                'jurusan_id' => 5,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknologi Produksi Ternak',
            ],
            [
                'id' => 16,
                'nama_prodi' => 'Teknologi Pengolahan Hasil Ternak',
                'jurusan_id' => 5,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknologi Pengolahan Hasil Ternak',
            ],
            [
                'id' => 17,
                'nama_prodi' => 'Teknologi Produksi Tanaman Pangan',
                'jurusan_id' => 5,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknologi Produksi Tanaman Pangan',
            ],
            [
                'id' => 18,
                'nama_prodi' => 'Pengembangan Produk Agroindustry',
                'jurusan_id' => 5,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Pengembangan Produk Agroindustry',
            ],
            [
                'id' => 19,
                'nama_prodi' => 'Teknologi Budi Daya Perikanan / Teknologi Akuakultur',
                'jurusan_id' => 5,
                'jenjang' => 'D4',
                'deskripsi' => 'Program studi Teknologi Budi Daya Perikanan / Teknologi Akuakultur',
            ],
        ];

        foreach ($prodiData as $data) {
            Prodi::create($data);
            $this->command->info("   ✓ {$data['nama_prodi']} ({$data['jenjang']})");
        }
    }

    /**
     * Seed Units Poliwangi
     */
    private function seedUnits(): void
    {
        $this->command->info('🏢 Seeding 22 units...');

        $unitData = [
            // Fakultas
            ['nama' => 'Fakultas Teknik'],
            ['nama' => 'Fakultas Ekonomi dan Bisnis'],
            ['nama' => 'Fakultas Pariwisata'],
            ['nama' => 'Fakultas Pertanian'],

            // Jurusan
            ['nama' => 'Jurusan Teknik Sipil'],
            ['nama' => 'Jurusan Teknik Mesin'],
            ['nama' => 'Jurusan Bisnis & Informatika'],
            ['nama' => 'Jurusan Pariwisata'],
            ['nama' => 'Jurusan Pertanian'],

            // Bagian Administrasi
            ['nama' => 'Bagian Umum dan Keuangan'],
            ['nama' => 'Bagian Akademik'],
            ['nama' => 'Bagian Kemahasiswaan'],
            ['nama' => 'Bagian IT'],

            // Laboratorium
            ['nama' => 'Laboratorium Teknik Sipil'],
            ['nama' => 'Laboratorium Teknik Mesin'],
            ['nama' => 'Laboratorium Komputer'],
            ['nama' => 'Laboratorium Pariwisata'],
            ['nama' => 'Laboratorium Pertanian'],

            // Unit Pendukung
            ['nama' => 'Perpustakaan'],
            ['nama' => 'Gedung Serbaguna'],
            ['nama' => 'Aula Utama'],
            ['nama' => 'Ruang Meeting'],
        ];

        foreach ($unitData as $data) {
            Unit::create($data);
            $this->command->info("   ✓ {$data['nama']}");
        }
    }

    /**
     * Seed Positions Poliwangi
     */
    private function seedPositions(): void
    {
        $this->command->info('👥 Seeding 22 positions...');

        $positionData = [
            // Dosen
            ['nama' => 'Dosen'],
            ['nama' => 'Dosen Tetap'],
            ['nama' => 'Dosen Tidak Tetap'],
            ['nama' => 'Dosen Pengajar'],
            ['nama' => 'Dosen Pembimbing'],

            // Kepala
            ['nama' => 'Kepala Jurusan'],
            ['nama' => 'Kepala Bagian'],
            ['nama' => 'Kepala Laboratorium'],
            ['nama' => 'Kepala Perpustakaan'],

            // Administrasi
            ['nama' => 'Administrasi'],
            ['nama' => 'Staff Administrasi'],
            ['nama' => 'Sekretaris Jurusan'],
            ['nama' => 'Sekretaris Bagian'],

            // Teknis
            ['nama' => 'Teknisi'],
            ['nama' => 'Teknisi Lab'],
            ['nama' => 'Teknisi IT'],
            ['nama' => 'Teknisi Sarpras'],

            // Pendukung
            ['nama' => 'Pustakawan'],
            ['nama' => 'Security'],
            ['nama' => 'Cleaning Service'],
            ['nama' => 'Driver'],
            ['nama' => 'Gardener'],
        ];

        foreach ($positionData as $data) {
            Position::create($data);
            $this->command->info("   ✓ {$data['nama']}");
        }
    }

    /**
     * Display summary of seeded data
     */
    private function displaySummary(): void
    {
        $this->command->info('');
        $this->command->info('📊 SUMMARY DATA POLIWANGI:');
        $this->command->info('========================');

        // Count jurusan
        $jurusanCount = Jurusan::count();
        $this->command->info("📚 Total Jurusan: {$jurusanCount}");

        // Count prodi
        $prodiCount = Prodi::count();
        $this->command->info("🎓 Total Prodi: {$prodiCount}");

        // Count by jenjang
        $d3Count = Prodi::where('jenjang', 'D3')->count();
        $d4Count = Prodi::where('jenjang', 'D4')->count();
        $this->command->info("📈 Distribusi Jenjang: D3 ({$d3Count}), D4 ({$d4Count})");

        // Count units
        $unitCount = Unit::count();
        $this->command->info("🏢 Total Units: {$unitCount}");

        // Count positions
        $positionCount = Position::count();
        $this->command->info("👥 Total Positions: {$positionCount}");

        // Count by jurusan
        $this->command->info('');
        $this->command->info('📋 Detail per Jurusan:');
        $jurusanList = Jurusan::withCount('prodis')->get();
        foreach ($jurusanList as $jurusan) {
            $this->command->info("   • {$jurusan->nama_jurusan}: {$jurusan->prodis_count} prodi");
        }

        $this->command->info('');
        $this->command->info('✅ Data siap digunakan untuk setup profil mahasiswa dan staff!');
    }
}
