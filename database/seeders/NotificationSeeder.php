<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cari satu user target (prioritaskan admin)
        $user = User::where('username', 'admin')
            ->orWhere('email', 'admin@poliwangi.ac.id')
            ->first();

        if (! $user) {
            $this->command?->warn('User admin tidak ditemukan, notifikasi tidak dibuat. Jalankan AdminUserSeeder terlebih dahulu.');

            return;
        }

        // Hapus notifikasi lama untuk user ini agar seed konsisten saat diulang
        DatabaseNotification::where('notifiable_type', $user::class)
            ->where('notifiable_id', $user->id)
            ->delete();

        $now = now();

        $payloads = [
            [
                'title' => 'Pengajuan peminjaman ruangan disetujui',
                'message' => 'Ruangan Laboratorium A-101 pada tanggal 20 Nov 2025 pukul 09.00–11.00 telah disetujui oleh Admin Sarpras.',
                'category' => 'peminjaman',
                'priority' => 'high',
                'icon' => 'check-circle',
                'color' => 'success',
                'action_url' => route('dashboard'),
            ],
            [
                'title' => 'Pengajuan baru menunggu persetujuan',
                'message' => 'Terdapat pengajuan peminjaman baru untuk Ruang Meeting B-203 yang perlu Anda review.',
                'category' => 'approval',
                'priority' => 'normal',
                'icon' => 'information-circle',
                'color' => 'info',
                'action_url' => route('user-management.index'),
            ],
            [
                'title' => 'Peminjaman melewati batas waktu',
                'message' => 'Peminjaman perangkat proyektor oleh pengguna John Doe sudah melewati batas waktu pengembalian.',
                'category' => 'conflict',
                'priority' => 'urgent',
                'icon' => 'exclamation-triangle',
                'color' => 'danger',
                'action_url' => route('dashboard'),
            ],
            [
                'title' => 'Sinkronisasi data master berhasil',
                'message' => 'Data master Poliwangi berhasil disinkronisasi tanpa kendala pada pukul 06.30.',
                'category' => 'system',
                'priority' => 'low',
                'icon' => 'information-circle',
                'color' => 'info',
                'action_url' => null,
            ],
            [
                'title' => 'Perubahan role pengguna',
                'message' => 'Role pengguna "staff.laboran" telah diperbarui oleh admin.',
                'category' => 'system',
                'priority' => 'normal',
                'icon' => 'bell',
                'color' => 'info',
                'action_url' => route('role-management.index'),
            ],
        ];

        foreach ($payloads as $index => $data) {
            DatabaseNotification::create([
                'id' => Str::uuid()->toString(),
                'type' => 'app-notification',
                'notifiable_type' => $user::class,
                'notifiable_id' => $user->id,
                'data' => $data,
                'read_at' => $index < 2 ? null : $now, // 2 pertama belum dibaca
                'created_at' => $now->copy()->subMinutes(10 * $index),
                'updated_at' => $now,
            ]);
        }

        $this->command?->info('Sample notifikasi untuk user admin berhasil dibuat.');
    }
}
