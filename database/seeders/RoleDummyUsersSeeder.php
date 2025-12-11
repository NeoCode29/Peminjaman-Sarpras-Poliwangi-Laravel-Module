<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RoleDummyUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::query()->get();

        if ($roles->isEmpty()) {
            $this->command?->warn('Tidak ada role yang ditemukan. Jalankan RolePermissionSeeder terlebih dahulu.');

            return;
        }

        foreach ($roles as $role) {
            // Lewati role Admin Sarpras karena sudah dibuat oleh AdminUserSeeder
            if ($role->name === 'Admin Sarpras') {
                continue;
            }

            $username = 'dummy_' . Str::slug($role->name, '_');
            $email = $username . '@example.test';

            $user = User::query()
                ->where('role_id', $role->id)
                ->where(function ($q) use ($username, $email) {
                    $q->where('username', $username)
                      ->orWhere('email', $email);
                })
                ->first();

            if (! $user) {
                $userType = $role->name === 'Peminjam Mahasiswa' ? 'mahasiswa' : 'staff';

                $user = User::create([
                    'name' => 'Dummy ' . $role->display_name,
                    'username' => $username,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'phone' => '080000000000',
                    'user_type' => $userType,
                    'status' => 'active',
                    'role_id' => $role->id,
                    'profile_completed' => false,
                    'profile_completed_at' => null,
                ]);

                $this->command?->info("Dummy user untuk role '{$role->name}' dibuat: {$username} / {$email}");
            } else {
                $this->command?->info("Dummy user untuk role '{$role->name}' sudah ada, dilewati.");
            }

            // Pastikan mapping ke role Spatie
            $user->assignRole($role);
        }
    }
}
