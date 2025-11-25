<?php

namespace App\Services;

use App\Events\UserAuditLogged;
use App\Models\User;
use App\Repositories\Interfaces\AuthRepositoryInterface;
use App\Repositories\Interfaces\OAuthTokenRepositoryInterface;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OAuthService
{
    private readonly array $config;

    public function __construct(
        private readonly AuthRepositoryInterface $authRepository,
        private readonly OAuthTokenRepositoryInterface $tokenRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly DatabaseManager $database,
    ) {
        $this->config = config('services.oauth_server', []);
    }

    public function isEnabled(): bool
    {
        return filter_var($this->config['sso_enable'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    public function loginOrRegisterFromSso(array $ssoUser, array $tokenData): User
    {
        if (empty($ssoUser['username']) || empty($ssoUser['name'])) {
            throw new InvalidArgumentException('SSO response tidak lengkap (username atau name kosong).');
        }

        $ssoId = $ssoUser['id'] ?? $ssoUser['username'];
        $provider = $this->config['provider'] ?? 'poliwangi';

        // Check if user exists by SSO ID
        $user = User::where('sso_id', $ssoId)->first();

        if (! $user) {
            // Check if user exists by username or email
            $user = User::where('username', $ssoUser['username'])
                ->orWhere('email', $ssoUser['email'] ?? '')
                ->first();
        }

        if ($user) {
            // Update minimal fields for existing user
            $user->last_sso_login = now();
            $user->sso_data = json_encode($ssoUser);
            $user->sso_id = $ssoId;
            $user->sso_provider = $provider;
            $user->save();

            Log::info('Existing SSO user logged in', [
                'user_id' => $user->id,
                'username' => $user->username,
                'sso_id' => $ssoId,
            ]);
        } else {
            // Create new user
            $user = $this->createUserFromSso($ssoUser, $ssoId, $provider);

            Log::info('User created from SSO', [
                'user_id' => $user->id,
                'username' => $user->username,
                'sso_id' => $ssoId,
            ]);
        }

        // Login user
        Auth::login($user);

        // Store OAuth token
        $this->storeToken($user, $tokenData);

        return $user;
    }

    private function createUserFromSso(array $ssoUser, string $ssoId, string $provider): User
    {
        $email = $ssoUser['email'] ?? sprintf('%s@%s.synthetic', Str::lower($ssoUser['username']), $provider);
        $userType = $this->determineUserType($ssoUser);
        
        // Extract phone from SSO data if available (try multiple possible keys)
        $phone = $ssoUser['phone'] ?? 
                 $ssoUser['mobile'] ?? 
                 $ssoUser['handphone'] ?? 
                 $ssoUser['phone_number'] ?? 
                 $ssoUser['contact'] ?? 
                 $ssoUser['no_hp'] ?? 
                 null;
        
        // Sanitize phone: keep only digits and validate minimum length
        if ($phone) {
            $phone = preg_replace('/[^0-9]/', '', $phone);
            // Validate phone: must be at least 10 digits
            if (strlen($phone) < 10 || strlen($phone) > 15) {
                $phone = null; // Invalid phone, set to null
            }
        }

        $user = User::create([
            'name' => $ssoUser['name'],
            'username' => Str::lower($ssoUser['username']),
            'email' => Str::lower($email),
            'phone' => $phone,
            'password' => Hash::make(Str::random(40)),
            'sso_id' => $ssoId,
            'sso_provider' => $provider,
            'sso_data' => json_encode($ssoUser),
            'user_type' => $userType,
            'status' => 'active',
            'profile_completed' => false,
            'password_changed_at' => now(),
            'last_sso_login' => now(),
        ]);

        $this->assignDefaultRole($user);

        return $user;
    }

    private function determineUserType(array $ssoData): string
    {
        if (isset($ssoData['staff'])) {
            return match ((int) $ssoData['staff']) {
                3, 4, 0 => 'staff',
                default => 'mahasiswa',
            };
        }

        $email = Str::lower($ssoData['email'] ?? '');
        $username = $ssoData['username'] ?? '';

        if (str_contains($email, '@student.') || preg_match('/^\d{12}$/', $username)) {
            return 'mahasiswa';
        }

        if (str_contains($email, '@poliwangi.') || str_contains($email, '@poltek.')) {
            return 'staff';
        }

        return 'mahasiswa';
    }

    private function assignDefaultRole(User $user): void
    {
        try {
            // Assign role based on user_type
            $roleName = match ($user->user_type) {
                'staff' => 'Peminjam Staff',
                'mahasiswa' => 'Peminjam Mahasiswa',
                default => 'Peminjam Mahasiswa',
            };

            $role = $this->roleRepository->findByName($roleName);

            if ($role) {
                $user->assignRole($role);
                $user->role_id = $role->id;
                $user->save();

                Log::info('Role assigned to SSO user', [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'role_name' => $roleName,
                    'user_type' => $user->user_type,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to assign role to SSO user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function storeToken(User $user, array $tokenData): void
    {
        // Delete existing token
        $this->tokenRepository->deleteByUserId($user->id);

        // Create new token
        $this->tokenRepository->create([
            'user_id' => $user->id,
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'] ?? null,
            'expires_in' => $tokenData['expires_in'],
            'token_type' => 'Bearer',
            'scope' => $tokenData['scope'] ?? null,
        ]);
    }

    public function logout(User $user): void
    {
        $this->tokenRepository->deleteByUserId($user->id);
    }
}
