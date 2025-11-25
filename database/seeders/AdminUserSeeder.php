<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari role admin utama untuk sarpras
        $adminRole = Role::where('name', 'Admin Sarpras')->first();

        if (! $adminRole) {
            $this->command?->error('Role "Admin Sarpras" tidak ditemukan. Jalankan RolePermissionSeeder terlebih dahulu.');

            return;
        }

        // Cegah duplikasi admin berdasarkan username/email
        $admin = User::where('username', 'admin')
            ->orWhere('email', 'admin@poliwangi.ac.id')
            ->first();

        if (! $admin) {
            $admin = User::create([
                'name' => 'Administrator Sarpras',
                'username' => 'admin',
                'email' => 'admin@poliwangi.ac.id',
                'password' => Hash::make('admin123'),
                'phone' => '081234567890',
                'user_type' => 'staff',
                'status' => 'active',
                'role_id' => $adminRole->id,
                'profile_completed' => false,
                'profile_completed_at' => null,
            ]);

            $this->command?->info('Admin user baru dibuat.');
        } else {
            // Pastikan role_id dan status konsisten
            $admin->forceFill([
                'role_id' => $adminRole->id,
                'status' => 'active',
                'user_type' => 'staff',
            ])->save();

            $this->command?->info('Admin user sudah ada, data diperbarui.');
        }

        // Assign role Spatie
        $admin->assignRole($adminRole);

        $this->command?->info('Role "Admin Sarpras" terpasang pada user admin.');
        $this->command?->info('------------------------------');
        $this->command?->info('Login admin default:');
        $this->command?->info('Username: admin');
        $this->command?->info('Password: admin123');
        $this->command?->info('Email   : admin@poliwangi.ac.id');
    }
}
