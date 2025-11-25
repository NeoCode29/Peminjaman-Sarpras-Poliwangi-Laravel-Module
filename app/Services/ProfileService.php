<?php

namespace App\Services;

use App\Events\UserAuditLogged;
use App\Models\User;
use App\Repositories\Interfaces\AuthRepositoryInterface;
use App\Repositories\Interfaces\StaffEmployeeRepositoryInterface;
use App\Repositories\Interfaces\StudentRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class ProfileService
{
    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly StudentRepositoryInterface $studentRepository,
        private readonly StaffEmployeeRepositoryInterface $staffRepository,
        private readonly DatabaseManager $database,
    ) {
    }

    /**
     * Complete profile setup for first-time users
     */
    public function completeProfileSetup(User $user, array $data): User
    {
        if ($user->isProfileCompleted()) {
            throw new InvalidArgumentException('Profil sudah lengkap.');
        }

        return $this->database->transaction(function () use ($user, $data) {
            // Update phone if not already set (for SSO users)
            if (empty($user->phone) && !empty($data['phone'])) {
                // Sanitize phone: keep only digits
                $phone = preg_replace('/[^0-9]/', '', $data['phone']);
                
                $user = $this->authRepository->update($user, [
                    'phone' => $phone,
                ]);
            }

            // Create type-specific data
            if ($user->user_type === 'mahasiswa') {
                $this->createStudentData($user, $data);
            } elseif ($user->user_type === 'staff') {
                $this->createStaffData($user, $data);
            }

            // Mark profile as completed
            $user->markProfileCompleted();

            Log::info('Profile setup completed', [
                'user_id' => $user->id,
                'username' => $user->username,
                'user_type' => $user->user_type,
            ]);

            $this->dispatchAuditEvent($user, 'profile.setup_completed', [
                'user_type' => $user->user_type,
                'completed_at' => now()->toIso8601String(),
            ]);

            return $user->fresh(['student', 'staffEmployee', 'role']);
        });
    }

    /**
     * Update user profile information
     */
    public function updateProfile(User $user, array $data): User
    {
        return $this->database->transaction(function () use ($user, $data) {
            // Update user basic info
            $user = $this->authRepository->update($user, [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);

            // Update type-specific data
            if ($user->user_type === 'mahasiswa') {
                $this->updateStudentData($user, $data);
            } elseif ($user->user_type === 'staff') {
                $this->updateStaffData($user, $data);
            }

            Log::info('Profile updated', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            $this->dispatchAuditEvent($user, 'profile.updated', [
                'updated_fields' => array_keys($data),
            ]);

            return $user->fresh(['student', 'staffEmployee', 'role']);
        });
    }

    /**
     * Update user password
     */
    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if ($user->isSsoUser()) {
            throw new RuntimeException('Password akun SSO tidak dapat diubah dari aplikasi ini.');
        }

        if (! Hash::check($currentPassword, $user->password)) {
            throw new InvalidArgumentException('Password lama tidak sesuai.');
        }

        $user->updatePassword($newPassword);

        Log::info('User password updated', [
            'user_id' => $user->id,
            'updated_by' => $user->id,
            'via' => 'profile',
        ]);

        $this->dispatchAuditEvent($user, 'profile.password_changed', [
            'changed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get comprehensive profile data for display
     */
    public function getProfileData(User $user): array
    {
        $user->load(['student.jurusan', 'student.prodi', 'staffEmployee.unit', 'staffEmployee.position', 'role']);

        $data = [
            'user' => $user,
            'is_sso_user' => $user->isSsoUser(),
            'specific_data' => $user->getSpecificData(),
            'has_specific_data' => $user->hasSpecificData(),
        ];

        if ($user->isStudent() && $user->student) {
            $data['student'] = $user->student;
            $data['jurusan'] = $user->student->jurusan;
            $data['prodi'] = $user->student->prodi;
        } elseif ($user->isEmployee() && $user->staffEmployee) {
            $data['staff'] = $user->staffEmployee;
            $data['unit'] = $user->staffEmployee->unit;
            $data['position'] = $user->staffEmployee->position;
        }

        return $data;
    }

    /**
     * Create student data during profile setup
     */
    private function createStudentData(User $user, array $data): void
    {
        // NIM from input (manual registration) or username (SSO)
        // Valid NIM format: 12 digits
        $nim = $data['nim'] ?? $user->username;
        
        // Extract angkatan from NIM (digits 3-4)
        $angkatan = null;
        if (strlen($nim) >= 4 && preg_match('/^\d{12}$/', $nim)) {
            $angkatanDigits = substr($nim, 2, 2);
            $angkatan = 2000 + (int) $angkatanDigits;
        }

        $this->studentRepository->create([
            'user_id' => $user->id,
            'nim' => $nim,
            'angkatan' => $angkatan,
            'jurusan_id' => $data['jurusan_id'],
            'prodi_id' => $data['prodi_id'],
            'status_mahasiswa' => 'aktif',
        ]);
    }

    /**
     * Create staff data during profile setup
     */
    private function createStaffData(User $user, array $data): void
    {
        $this->staffRepository->create([
            'user_id' => $user->id,
            'nip' => $data['nip'] ?? null,
            'unit_id' => $data['unit_id'],
            'position_id' => $data['position_id'],
        ]);
    }

    /**
     * Update student data
     */
    private function updateStudentData(User $user, array $data): void
    {
        $student = $this->studentRepository->findByUserId($user->id);

        if ($student && isset($data['jurusan_id'], $data['prodi_id'])) {
            $this->studentRepository->update($student, [
                'jurusan_id' => $data['jurusan_id'],
                'prodi_id' => $data['prodi_id'],
            ]);
        }
    }

    /**
     * Update staff data
     */
    private function updateStaffData(User $user, array $data): void
    {
        $staff = $this->staffRepository->findByUserId($user->id);

        if ($staff) {
            $updateData = [];

            if (isset($data['unit_id'])) {
                $updateData['unit_id'] = $data['unit_id'];
            }

            if (isset($data['position_id'])) {
                $updateData['position_id'] = $data['position_id'];
            }

            if (isset($data['nip'])) {
                $updateData['nip'] = $data['nip'];
            }

            if (! empty($updateData)) {
                $this->staffRepository->update($staff, $updateData);
            }
        }
    }

    /**
     * Dispatch audit event for profile actions
     */
    private function dispatchAuditEvent(User $user, string $action, array $metadata = []): void
    {
        event(new UserAuditLogged(
            action: $action,
            user: $user,
            attributes: Arr::except($user->toArray(), ['password']),
            original: Arr::except($user->getOriginal(), ['password']),
            performedBy: $user->getKey(),
            performedByType: User::class,
            context: 'profile_service',
            metadata: $metadata,
        ));
    }
}
