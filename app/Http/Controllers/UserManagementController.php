<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\BlockUserRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
        private readonly RoleService $roleService
    ) {
        $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request): View
    {
        $filters = Arr::only($request->query(), [
            'search', 'role_id', 'status', 'user_type', 'order_by', 'order_direction',
        ]);

        $perPage = (int) $request->integer('per_page', 15);

        $users = $this->userService->getUsers($filters, $perPage);

        return view('users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => $this->roleService->getActiveRoles(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => $this->roleService->getActiveRoles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->userService->createUser($request->validated());

        return redirect()
            ->route('user-management.index')
            ->with('success', 'User berhasil dibuat.');
    }

    public function show(User $user): View
    {
        $user->load([
            'role.permissions',
            'student.jurusan',
            'student.prodi',
            'staffEmployee.unit',
            'staffEmployee.position'
        ]);

        return view('users.show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user): View
    {
        $user->load(['roles']);

        return view('users.edit', [
            'user' => $user,
            'roles' => $this->roleService->getActiveRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->userService->updateUser($user, $request->validated());

        return redirect()
            ->route('user-management.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        try {
            $this->userService->deleteUser($user);

            return redirect()
                ->route('user-management.index')
                ->with('success', 'User berhasil dihapus.');
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('user-management.show', $user)
                ->withErrors($exception->getMessage());
        } catch (Throwable $throwable) {
            Log::error('Gagal menghapus user', [
                'user_id' => $user->id,
                'error' => $throwable->getMessage(),
            ]);

            return redirect()
                ->route('user-management.show', $user)
                ->withErrors('Terjadi kesalahan saat menghapus user.');
        }
    }

    public function toggleStatus(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        try {
            $updatedUser = $this->userService->toggleStatus($user);

            return response()->json([
                'success' => true,
                'message' => 'Status user berhasil diperbarui.',
                'data' => [
                    'id' => $updatedUser->id,
                    'status' => $updatedUser->status,
                ],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $throwable) {
            Log::error('Gagal mengubah status user', [
                'user_id' => $user->id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah status user.',
            ], 500);
        }
    }

    public function block(User $user, BlockUserRequest $request): RedirectResponse
    {
        $this->authorize('block', $user);

        $blockedUntil = $request->input('blocked_until');
        $reason = $request->input('blocked_reason');

        try {
            $updatedUser = $this->userService->blockUser($user, $blockedUntil, $reason);
            return redirect()
                ->route('user-management.show', $updatedUser)
                ->with('success', 'User berhasil diblokir.');
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('user-management.show', $user)
                ->withErrors($exception->getMessage());
        } catch (Throwable $throwable) {
            Log::error('Gagal memblokir user', [
                'user_id' => $user->id,
                'error' => $throwable->getMessage(),
            ]);

            return redirect()
                ->route('user-management.show', $user)
                ->withErrors('Terjadi kesalahan saat memblokir user.');
        }
    }

    public function unblock(User $user): RedirectResponse
    {
        $this->authorize('unblock', $user);

        try {
            $updatedUser = $this->userService->unblockUser($user);
            return redirect()
                ->route('user-management.show', $updatedUser)
                ->with('success', 'User berhasil dibuka blokirnya.');
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('user-management.show', $user)
                ->withErrors($exception->getMessage());
        } catch (Throwable $throwable) {
            Log::error('Gagal membuka blokir user', [
                'user_id' => $user->id,
                'error' => $throwable->getMessage(),
            ]);

            return redirect()
                ->route('user-management.show', $user)
                ->withErrors('Terjadi kesalahan saat membuka blokir user.');
        }
    }

    public function changePassword(ChangePasswordRequest $request, User $user): RedirectResponse
    {
        try {
            $this->userService->changePassword($user, $request->validated());

            return redirect()
                ->route('user-management.show', $user)
                ->with('success', 'Password user berhasil diperbarui.');
        } catch (Throwable $throwable) {
            Log::error('Gagal mengubah password user', [
                'user_id' => $user->id,
                'error' => $throwable->getMessage(),
            ]);

            return redirect()
                ->route('user-management.show', $user)
                ->withErrors('Terjadi kesalahan saat mengubah password user.');
        }
    }
}
