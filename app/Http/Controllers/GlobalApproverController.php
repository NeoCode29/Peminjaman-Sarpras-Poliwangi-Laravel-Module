<?php

namespace App\Http\Controllers;

use App\Http\Requests\GlobalApprover\StoreGlobalApproverRequest;
use App\Http\Requests\GlobalApprover\UpdateGlobalApproverRequest;
use App\Models\GlobalApprover;
use App\Services\GlobalApproverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class GlobalApproverController extends Controller
{
    public function __construct(
        private readonly GlobalApproverService $globalApproverService
    ) {
        $this->authorizeResource(GlobalApprover::class, 'global_approver');
    }

    /**
     * Display a listing of global approvers.
     */
    public function index(Request $request): View
    {
        $filters = Arr::only($request->query(), [
            'search', 'approval_level', 'is_active',
        ]);

        $perPage = $request->integer('per_page', 15);

        $data = $this->globalApproverService->getDataForSettingsPage($filters, $perPage);

        return view('settings.global-approvers', $data);
    }

    /**
     * Store a newly created global approver.
     */
    public function store(StoreGlobalApproverRequest $request): RedirectResponse
    {
        try {
            $this->globalApproverService->createGlobalApprover($request->validated());

            return redirect()
                ->route('settings.global-approvers.index')
                ->with('success', 'Global approver berhasil ditambahkan.');
        } catch (RuntimeException $exception) {
            return redirect()
                ->back()
                ->withErrors(['error' => $exception->getMessage()])
                ->withInput();
        } catch (Throwable $throwable) {
            Log::error('Gagal menambahkan global approver', [
                'error' => $throwable->getMessage(),
                'data' => $request->validated(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat menambahkan global approver.'])
                ->withInput();
        }
    }

    /**
     * Update the specified global approver.
     */
    public function update(UpdateGlobalApproverRequest $request, GlobalApprover $globalApprover): RedirectResponse
    {
        try {
            $this->globalApproverService->updateGlobalApprover($globalApprover, $request->validated());

            return redirect()
                ->route('settings.global-approvers.index')
                ->with('success', 'Global approver berhasil diperbarui.');
        } catch (RuntimeException $exception) {
            return redirect()
                ->back()
                ->withErrors(['error' => $exception->getMessage()])
                ->withInput();
        } catch (Throwable $throwable) {
            Log::error('Gagal memperbarui global approver', [
                'id' => $globalApprover->id,
                'error' => $throwable->getMessage(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'Terjadi kesalahan saat memperbarui global approver.'])
                ->withInput();
        }
    }

    /**
     * Remove the specified global approver.
     */
    public function destroy(GlobalApprover $globalApprover): RedirectResponse
    {
        try {
            $this->globalApproverService->deleteGlobalApprover($globalApprover);

            return redirect()
                ->route('settings.global-approvers.index')
                ->with('success', 'Global approver berhasil dihapus.');
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('settings.global-approvers.index')
                ->withErrors(['error' => $exception->getMessage()]);
        } catch (Throwable $throwable) {
            Log::error('Gagal menghapus global approver', [
                'id' => $globalApprover->id,
                'error' => $throwable->getMessage(),
            ]);

            return redirect()
                ->route('settings.global-approvers.index')
                ->withErrors(['error' => 'Terjadi kesalahan saat menghapus global approver.']);
        }
    }

    /**
     * Toggle active status of global approver.
     */
    public function toggleStatus(GlobalApprover $globalApprover): JsonResponse
    {
        $this->authorize('toggleStatus', $globalApprover);

        try {
            $updatedApprover = $this->globalApproverService->toggleActive($globalApprover);

            return response()->json([
                'success' => true,
                'message' => 'Status global approver berhasil diperbarui.',
                'data' => [
                    'id' => $updatedApprover->id,
                    'is_active' => $updatedApprover->is_active,
                    'status_label' => $updatedApprover->status_label,
                ],
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $throwable) {
            Log::error('Gagal mengubah status global approver', [
                'id' => $globalApprover->id,
                'error' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengubah status global approver.',
            ], 500);
        }
    }
}
