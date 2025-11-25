<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\ChangePasswordRequest;
use App\Http\Requests\Profile\ProfileSetupRequest;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Models\Jurusan;
use App\Models\Position;
use App\Models\Prodi;
use App\Models\Unit;
use App\Services\ProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {
    }

    /**
     * Show profile setup form for new users
     */
    public function setup(): View|RedirectResponse
    {
        try {
            $user = Auth::user();

            Log::info('Profile setup accessed', [
                'user_id' => $user->id,
                'username' => $user->username,
                'profile_completed' => $user->profile_completed,
            ]);

            // Redirect if profile already completed
            if ($user->isProfileCompleted()) {
                Log::info('Profile already completed, redirecting to dashboard');

                return redirect()->route('dashboard')
                    ->with('info', 'Profil Anda sudah lengkap.');
            }

            // Get master data for dropdowns
            $jurusans = Jurusan::orderBy('nama_jurusan')->get();
            $prodis = Prodi::orderBy('nama_prodi')->get();
            $units = Unit::orderBy('nama')->get();
            $positions = Position::orderBy('nama')->get();

            return view('profile.setup', compact('user', 'jurusans', 'prodis', 'units', 'positions'));
        } catch (Throwable $e) {
            Log::error('Profile setup error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);

            return redirect()->route('login')
                ->withErrors(['error' => 'Terjadi kesalahan saat memuat halaman setup profil.']);
        }
    }

    /**
     * Complete profile setup
     */
    public function completeSetup(ProfileSetupRequest $request): RedirectResponse
    {
        $user = Auth::user();

        Log::info('Profile setup completion started', [
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        // Redirect if profile already completed
        if ($user->isProfileCompleted()) {
            return redirect()->route('dashboard')
                ->with('info', 'Profil Anda sudah lengkap.');
        }

        try {
            $validatedData = $request->validated();
            
            Log::info('Profile setup validated data', [
                'user_id' => $user->id,
                'data' => $validatedData,
            ]);

            $this->profileService->completeProfileSetup($user, $validatedData);

            return redirect()->route('dashboard')
                ->with('success', 'Profil berhasil dilengkapi! Selamat datang di sistem peminjaman sarana dan prasarana.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database specific errors
            Log::error('Profile setup database error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Check for duplicate entry error
            if ($e->getCode() == 23000) {
                $errorMessage = 'Data yang Anda masukkan sudah terdaftar di sistem.';
                
                // Check specific field duplicate
                if (str_contains($e->getMessage(), 'students_nim_unique')) {
                    $errorMessage = 'NIM yang Anda masukkan sudah terdaftar. Silakan gunakan NIM yang berbeda.';
                } elseif (str_contains($e->getMessage(), 'students_user_id_unique')) {
                    $errorMessage = 'Profil Anda sudah pernah dibuat sebelumnya.';
                }
                
                return redirect()->back()
                    ->withErrors(['error' => $errorMessage])
                    ->withInput();
            }

            // Generic database error
            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan database. Silakan coba lagi atau hubungi administrator.'])
                ->withInput();

        } catch (\InvalidArgumentException $e) {
            // Handle validation errors from service
            Log::warning('Profile setup validation error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();

        } catch (Throwable $e) {
            // Handle unexpected errors
            Log::error('Profile setup failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan yang tidak terduga. Silakan coba lagi atau hubungi administrator.'])
                ->withInput();
        }
    }

    /**
     * Show user profile
     */
    public function show(): View
    {
        $user = Auth::user();
        $profileData = $this->profileService->getProfileData($user);

        return view('profile.show', $profileData);
    }

    /**
     * Show profile edit form
     */
    public function edit(): View
    {
        $user = Auth::user();
        $profileData = $this->profileService->getProfileData($user);

        // Get master data for dropdowns
        $jurusans = Jurusan::orderBy('nama_jurusan')->get();
        $prodis = Prodi::orderBy('nama_prodi')->get();
        $units = Unit::orderBy('nama')->get();
        $positions = Position::orderBy('nama')->get();

        return view('profile.edit', array_merge($profileData, [
            'jurusans' => $jurusans,
            'prodis' => $prodis,
            'units' => $units,
            'positions' => $positions,
        ]));
    }

    /**
     * Update user profile
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();

        try {
            $this->profileService->updateProfile($user, $request->validated());

            return redirect()->route('profile.show')
                ->with('success', 'Profil berhasil diperbarui!');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database specific errors
            Log::error('Profile update database error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Check for duplicate entry error
            if ($e->getCode() == 23000) {
                $errorMessage = 'Data yang Anda masukkan sudah digunakan oleh user lain.';
                
                // Check specific field duplicate
                if (str_contains($e->getMessage(), 'users_email_unique')) {
                    $errorMessage = 'Email sudah digunakan oleh user lain.';
                } elseif (str_contains($e->getMessage(), 'staff_employees_nip_unique')) {
                    $errorMessage = 'NIP sudah digunakan oleh staff lain.';
                }
                
                return redirect()->back()
                    ->withErrors(['error' => $errorMessage])
                    ->withInput();
            }

            // Generic database error
            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan database. Silakan coba lagi atau hubungi administrator.'])
                ->withInput();

        } catch (Throwable $e) {
            Log::error('Profile update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan yang tidak terduga. Silakan coba lagi atau hubungi administrator.'])
                ->withInput();
        }
    }

    /**
     * Show change password form
     */
    public function changePassword(): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->isSsoUser()) {
            return redirect()->route('profile.show')
                ->with('info', 'Akun SSO dikelola oleh penyedia SSO. Ubah password melalui portal SSO.');
        }

        return view('profile.change-password', compact('user'));
    }

    /**
     * Update user password
     */
    public function updatePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $user = Auth::user();

        try {
            $this->profileService->updatePassword(
                $user,
                $request->input('current_password'),
                $request->input('password')
            );

            return redirect()->route('profile.password.edit')
                ->with('success', 'Password berhasil diperbarui.');
        } catch (\InvalidArgumentException $e) {
            // Handle validation errors from service (e.g., wrong current password)
            Log::warning('Password update validation error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput($request->except(['current_password', 'password', 'password_confirmation']));

        } catch (Throwable $e) {
            Log::error('Password update failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat mengubah password. Silakan coba lagi.'])
                ->withInput($request->except(['current_password', 'password', 'password_confirmation']));
        }
    }

    /**
     * Get prodis by jurusan (AJAX endpoint)
     */
    public function getProdisByJurusan(Request $request): JsonResponse
    {
        $prodis = Prodi::where('jurusan_id', $request->jurusan_id)
            ->orderBy('nama_prodi')
            ->get(['id', 'nama_prodi']);

        return response()->json($prodis);
    }
}
