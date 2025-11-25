<?php

namespace App\Http\Controllers;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Models\Role;
use App\Services\PermissionService;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RoleManagementController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly PermissionService $permissionService
    ) {
        $this->authorizeResource(Role::class, 'role');
    }

    public function index(Request $request): View
    {
        $filters = Arr::only($request->query(), [
            'search', 'guard_name', 'category', 'order_by', 'order_direction',
        ]);

        if ($request->filled('status')) {
            $filters['is_active'] = $request->boolean('status');
        }

        if ($request->filled('protected')) {
            $filters['protected'] = $request->boolean('protected');
        }

        $perPage = (int) $request->integer('per_page', 15);

        $roles = $this->roleService->getRoles($filters, $perPage);

        return view('roles.index', [
            'roles' => $roles,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('roles.create', [
            'permissions' => $this->permissionService->getActivePermissionsGrouped()->flatten(),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roleService->createRole($request->validated());

        return redirect()
            ->route('role-management.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    public function show(Role $role): View
    {
        $role->load(['permissions', 'users']);

        return view('roles.show', [
            'role' => $role,
        ]);
    }

    public function edit(Role $role): View
    {
        $role->load(['permissions']);

        return view('roles.edit', [
            'role' => $role,
            'permissions' => $this->permissionService->getActivePermissionsGrouped()->flatten(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->updateRole($role, $request->validated());

        return redirect()
            ->route('role-management.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Request $request, Role $role)
    {
        try {
            $this->roleService->deleteRole($role);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role berhasil dihapus.',
                ]);
            }

            return redirect()
                ->route('role-management.index')
                ->with('success', 'Role berhasil dihapus.');
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return redirect()
                ->route('role-management.index')
                ->withErrors($exception->getMessage());
        } catch (Throwable $throwable) {
            Log::error('Gagal menghapus role', [
                'role_id' => $role->id,
                'error' => $throwable->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat menghapus role.',
                ], 500);
            }

            return redirect()
                ->route('role-management.index')
                ->withErrors('Terjadi kesalahan saat menghapus role.');
        }
    }

    public function toggleStatus(Role $role): JsonResponse
    {
        $this->authorize('toggleStatus', $role);

        try {
            $updatedRole = $this->roleService->toggleStatus($role);

            return response()->json([
                'success' => true,
                'message' => 'Status role berhasil diperbarui.',
                'data' => [
                    'id' => $updatedRole->id,
                    'is_active' => $updatedRole->is_active,
                ],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $throwable) {
            Log::error('Gagal mengubah status role', [
                'role_id' => $role->id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah status role.',
            ], 500);
        }
    }
}
