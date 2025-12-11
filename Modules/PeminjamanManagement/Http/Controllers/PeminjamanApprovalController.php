<?php

namespace Modules\PeminjamanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Http\Requests\ApprovalActionRequest;
use Modules\PeminjamanManagement\Services\PeminjamanApprovalService;
use Modules\PeminjamanManagement\Services\PickupReturnService;

class PeminjamanApprovalController extends Controller
{
    public function __construct(
        private readonly PeminjamanApprovalService $approvalService,
        private readonly PickupReturnService $pickupReturnService
    ) {
        $this->middleware('auth');
    }

    /**
     * Process approval action.
     */
    public function processApproval(ApprovalActionRequest $request, Peminjaman $peminjaman)
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $approvalType = $validated['approval_type'];
        $notes = $validated['notes'] ?? $validated['reason'] ?? null;
        $conflictDecision = $request->input('conflict_decision');

        try {
            switch ($approvalType) {
                case 'global':
                    $this->authorize($action === 'approve' ? 'approveGlobal' : 'rejectGlobal', $peminjaman);

                    if ($action === 'approve') {
                        $this->approvalService->approveGlobal($peminjaman->id, Auth::id(), $notes, $conflictDecision);
                    } else {
                        $this->approvalService->rejectGlobal($peminjaman->id, Auth::id(), $notes);
                    }
                    break;

                case 'sarana':
                    $this->authorize($action === 'approve' ? 'approveSpecific' : 'rejectSpecific', $peminjaman);

                    $saranaId = $validated['sarana_id'];
                    if ($action === 'approve') {
                        $this->approvalService->approveSpecificSarana($peminjaman->id, $saranaId, Auth::id(), $notes);
                    } else {
                        $this->approvalService->rejectSpecificSarana($peminjaman->id, $saranaId, Auth::id(), $notes);
                    }
                    break;

                case 'prasarana':
                    $this->authorize($action === 'approve' ? 'approveSpecific' : 'rejectSpecific', $peminjaman);

                    $prasaranaId = $validated['prasarana_id'];
                    if ($action === 'approve') {
                        $this->approvalService->approveSpecificPrasarana($peminjaman->id, $prasaranaId, Auth::id(), $notes);
                    } else {
                        $this->approvalService->rejectSpecificPrasarana($peminjaman->id, $prasaranaId, Auth::id(), $notes);
                    }
                    break;
            }

            $message = $action === 'approve' ? 'Peminjaman berhasil disetujui.' : 'Peminjaman berhasil ditolak.';

            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Override approval.
     */
    public function override(Request $request, Peminjaman $peminjaman)
    {
        $this->authorize('override', $peminjaman);

        $request->validate([
            'workflow_id' => ['required', 'exists:peminjaman_approval_workflow,id'],
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $workflow = $peminjaman->approvalWorkflow()->findOrFail($request->workflow_id);

            $this->approvalService->overrideWorkflow(
                $workflow,
                $request->action,
                $request->reason
            );

            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('success', 'Override berhasil dilakukan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Validate pickup.
     */
    public function validatePickup(Request $request, Peminjaman $peminjaman)
    {
        $this->authorize('validatePickup', $peminjaman);

        $request->validate([
            'unit_assignments' => ['nullable', 'array'],
            'unit_assignments.*' => ['array'],
            'unit_assignments.*.*' => ['integer', 'exists:sarana_units,id'],
        ]);

        try {
            $this->pickupReturnService->validatePickup(
                $peminjaman,
                Auth::id(),
                null,
                $request->input('unit_assignments', []),
                $request->input('pooled_pickup_items', [])
            );

            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('success', 'Pengambilan berhasil divalidasi.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Validate return.
     */
    public function validateReturn(Request $request, Peminjaman $peminjaman)
    {
        $this->authorize('validateReturn', $peminjaman);

        $request->validate([
            'unit_assignments' => ['nullable', 'array'],
            'unit_assignments.*' => ['array'],
            'unit_assignments.*.*' => ['integer', 'exists:sarana_units,id'],
            'pooled_return_items' => ['nullable', 'array'],
            'pooled_return_items.*' => ['integer', 'exists:peminjaman_items,id'],
        ]);

        try {
            $this->pickupReturnService->validateReturn(
                $peminjaman,
                Auth::id(),
                null,
                $request->input('unit_assignments', []),
                $request->input('pooled_return_items', [])
            );

            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('success', 'Pengembalian berhasil divalidasi.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Assign units to peminjaman item.
     */
    public function assignUnits(Request $request, Peminjaman $peminjaman)
    {
        $this->authorize('adjustSarpras', $peminjaman);

        $request->validate([
            'item_id' => ['required', 'exists:peminjaman_items,id'],
            'unit_ids' => ['required', 'array'],
            'unit_ids.*' => ['integer', 'exists:sarana_units,id'],
        ]);

        try {
            $item = $peminjaman->items()->findOrFail($request->item_id);

            $this->pickupReturnService->updateUnitAssignments(
                $item,
                $request->unit_ids,
                Auth::id()
            );

            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('success', 'Unit berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Get pending approvals for current user.
     */
    public function pendingApprovals()
    {
        $pendingApprovals = $this->approvalService->getPendingApprovals(Auth::id());

        return view('peminjamanmanagement::peminjaman.pending-approvals', compact('pendingApprovals'));
    }
}
