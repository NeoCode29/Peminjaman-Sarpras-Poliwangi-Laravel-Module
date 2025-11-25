<?php

namespace App\Http\Controllers;

use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class PermissionManagementController extends Controller
{
    public function __construct(private readonly PermissionService $permissionService)
    {
        $this->authorizeResource(Permission::class, 'permission');
    }

    public function index(Request $request): View
    {
        $filters = Arr::only($request->query(), [
            'search', 'category', 'guard_name', 'order_by', 'order_direction',
        ]);

        if ($request->filled('status')) {
            $filters['is_active'] = $request->boolean('status');
        }

        $perPage = (int) $request->integer('per_page', 20);

        $permissions = $this->permissionService->getPermissions($filters, $perPage);

        return view('permissions.index', [
            'permissions' => $permissions,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('permissions.create');
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $permission = $this->permissionService->createPermission($request->validated());

        return redirect()
            ->route('permission-management.index')
            ->with('success', 'Permission berhasil dibuat.');
    }

    public function show(Permission $permission): View
    {
        $permission->load('roles');

        return view('permissions.show', [
            'permission' => $permission,
        ]);
    }

    public function edit(Permission $permission): View
    {
        return view('permissions.edit', [
            'permission' => $permission,
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $this->permissionService->updatePermission($permission, $request->validated());

        return redirect()
            ->route('permission-management.index')
            ->with('success', 'Permission berhasil diperbarui.');
    }

    public function destroy(Permission $permission): JsonResponse
    {
        try {
            $this->permissionService->deletePermission($permission);

            return response()->json([
                'success' => true,
                'message' => 'Permission berhasil dihapus.',
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $throwable) {
            Log::error('Gagal menghapus permission', [
                'permission_id' => $permission->id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus permission.',
            ], 500);
        }
    }

    public function toggleStatus(Permission $permission): JsonResponse
    {
        $this->authorize('toggleStatus', $permission);

        try {
            $updatedPermission = $this->permissionService->toggleStatus($permission);

            return response()->json([
                'success' => true,
                'message' => 'Status permission berhasil diperbarui.',
                'data' => [
                    'id' => $updatedPermission->id,
                    'is_active' => $updatedPermission->is_active,
                ],
            ]);
        } catch (Throwable $throwable) {
            Log::error('Gagal mengubah status permission', [
                'permission_id' => $permission->id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah status permission.',
            ], 500);
        }
    }
}
