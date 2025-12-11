<?php

namespace Modules\PeminjamanManagement\Policies;

use App\Models\User;
use Modules\PeminjamanManagement\Entities\Peminjaman;

class PeminjamanPolicy
{
    /**
     * Determine whether the user can view any peminjaman.
     */
    public function viewAny(User $user): bool
    {
        // Admin atau user dengan permission view bisa lihat semua
        // User biasa bisa akses index (akan difilter di controller)
        return $user->hasPermissionTo('peminjaman.view')
            || $user->hasPermissionTo('peminjaman.create')
            || $user->hasRole('Admin Sarpras');
    }

    /**
     * Determine whether the user can view the peminjaman.
     */
    public function view(User $user, Peminjaman $peminjaman): bool
    {
        // Admin atau user dengan permission view
        if ($user->hasPermissionTo('peminjaman.view') || $user->hasRole('Admin Sarpras')) {
            return true;
        }

        // Owner bisa lihat peminjaman sendiri
        return $peminjaman->user_id === $user->id;
    }

    /**
     * Determine whether the user can create peminjaman.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('peminjaman.create') || $user->hasRole('Admin Sarpras');
    }

    /**
     * Determine whether the user can update the peminjaman.
     */
    public function update(User $user, Peminjaman $peminjaman): bool
    {
        // Admin Sarpras bisa mengedit ketika peminjaman masih pending atau sudah approved
        if ($user->hasRole('Admin Sarpras')) {
            return $peminjaman->isPending() || $peminjaman->isApproved();
        }

        // Owner hanya bisa mengedit ketika status masih pending dan memiliki permission peminjaman.edit
        return $peminjaman->user_id === $user->id
            && $peminjaman->isPending()
            && $user->hasPermissionTo('peminjaman.edit');
    }

    /**
     * Determine whether the user can delete the peminjaman.
     */
    public function delete(User $user, Peminjaman $peminjaman): bool
    {
        // Admin bisa delete
        if ($user->hasRole('Admin Sarpras')) {
            return true;
        }

        // Owner bisa delete jika masih pending
        return $peminjaman->user_id === $user->id && $peminjaman->isPending();
    }

    /**
     * Determine whether the user can cancel the peminjaman.
     */
    public function cancel(User $user, Peminjaman $peminjaman): bool
    {
        // Sudah cancelled atau returned tidak bisa cancel lagi
        if ($peminjaman->isCancelled() || $peminjaman->isReturned()) {
            return false;
        }

        // Admin bisa cancel untuk peminjaman apa pun selama masih pending atau approved
        if ($user->hasRole('Admin Sarpras')) {
            return $peminjaman->isPending() || $peminjaman->isApproved();
        }

        // Owner hanya bisa cancel peminjaman miliknya sendiri ketika masih pending
        // dan (opsional) memiliki permission peminjaman.cancel
        return $peminjaman->user_id === $user->id
            && $peminjaman->isPending()
            && $user->hasPermissionTo('peminjaman.cancel');
    }

    /**
     * Determine whether the user can approve the peminjaman (global).
     */
    public function approveGlobal(User $user, Peminjaman $peminjaman): bool
    {
        if (!$peminjaman->isPending()) {
            return false;
        }

        return $user->hasPermissionTo('peminjaman.approve_global') || $user->hasRole('Admin Sarpras');
    }

    /**
     * Determine whether the user can reject the peminjaman (global).
     */
    public function rejectGlobal(User $user, Peminjaman $peminjaman): bool
    {
        if (!$peminjaman->isPending()) {
            return false;
        }

        return $user->hasPermissionTo('peminjaman.reject_global') || $user->hasRole('Admin Sarpras');
    }

    /**
     * Determine whether the user can approve specific sarpras.
     */
    public function approveSpecific(User $user, Peminjaman $peminjaman): bool
    {
        if (!$peminjaman->isPending()) {
            return false;
        }

        return $user->hasPermissionTo('peminjaman.approve_specific') || $user->hasRole('Admin Sarpras');
    }

    /**
     * Determine whether the user can reject specific sarpras.
     */
    public function rejectSpecific(User $user, Peminjaman $peminjaman): bool
    {
        if (!$peminjaman->isPending()) {
            return false;
        }

        return $user->hasPermissionTo('peminjaman.reject_specific') || $user->hasRole('Admin Sarpras');
    }

    /**
     * Determine whether the user can override another approver's decision.
     */
    public function override(User $user, Peminjaman $peminjaman): bool
    {
        if ($user->hasRole('Admin Sarpras')) {
            return true;
        }

        if ($user->hasPermissionTo('peminjaman.override')) {
            return true;
        }

        // Check if user has higher approval level
        if (!$user->hasPermissionTo('peminjaman.approve_global')
            && !$user->hasPermissionTo('peminjaman.approve_specific')) {
            return false;
        }

        return $this->userCanOverrideWorkflow($user->id, $peminjaman);
    }

    /**
     * Check if user can override workflow based on approval level.
     */
    protected function userCanOverrideWorkflow(int $userId, Peminjaman $peminjaman): bool
    {
        $workflows = $peminjaman->approvalWorkflow()
            ->whereNotNull('approver_id')
            ->get(['approver_id', 'approval_level', 'approval_type', 'sarana_id', 'prasarana_id']);

        $userAssignments = $workflows->where('approver_id', $userId);

        if ($userAssignments->isEmpty()) {
            return false;
        }

        foreach ($userAssignments as $assignment) {
            $canOverride = $workflows->contains(function ($other) use ($assignment) {
                if ($other->approver_id === $assignment->approver_id) {
                    return false;
                }

                if ($other->approval_type !== $assignment->approval_type) {
                    return false;
                }

                if ($assignment->approval_type === 'sarana' && $other->sarana_id !== $assignment->sarana_id) {
                    return false;
                }

                if ($assignment->approval_type === 'prasarana' && $other->prasarana_id !== $assignment->prasarana_id) {
                    return false;
                }

                return $assignment->approval_level < $other->approval_level;
            });

            if ($canOverride) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can validate pickup.
     */
    public function validatePickup(User $user, Peminjaman $peminjaman): bool
    {
        if ($peminjaman->isCancelled() || $peminjaman->isReturned() || $peminjaman->isRejected()) {
            return false;
        }

        $approvalStatus = $peminjaman->approvalStatus;
        $globalApproved = optional($approvalStatus)->global_approval_status === 'approved';

        if (!$globalApproved) {
            return false;
        }

        return $user->hasPermissionTo('peminjaman.validate_pickup') || $user->hasRole('Admin Sarpras');
    }

    /**
     * Determine whether the user can validate return.
     */
    public function validateReturn(User $user, Peminjaman $peminjaman): bool
    {
        if (!$peminjaman->isPickedUp()) {
            return false;
        }

        return $user->hasPermissionTo('peminjaman.validate_return') || $user->hasRole('Admin Sarpras');
    }

    /**
     * Determine whether the user can adjust sarpras (assign units, etc).
     */
    public function adjustSarpras(User $user, Peminjaman $peminjaman): bool
    {
        if ($peminjaman->isCancelled() || $peminjaman->isReturned() || $peminjaman->isRejected()) {
            return false;
        }

        $approvalStatus = $peminjaman->approvalStatus;
        $globalApproved = optional($approvalStatus)->global_approval_status === 'approved';

        if (!$globalApproved) {
            return false;
        }

        return $user->hasPermissionTo('peminjaman.adjust_sarpras') || $user->hasRole('Admin Sarpras');
    }

    /**
     * Determine whether the user (borrower) can upload pickup photo.
     */
    public function uploadPickupPhoto(User $user, Peminjaman $peminjaman): bool
    {
        if ($peminjaman->isCancelled() || $peminjaman->isReturned()) {
            return false;
        }

        return $peminjaman->user_id === $user->id;
    }

    /**
     * Determine whether the user (borrower) can upload return photo.
     */
    public function uploadReturnPhoto(User $user, Peminjaman $peminjaman): bool
    {
        if ($peminjaman->isCancelled() || $peminjaman->isReturned()) {
            return false;
        }

        // Foto pengembalian hanya relevan setelah pengambilan dilakukan
        if (!$peminjaman->isPickedUp()) {
            return false;
        }

        return $peminjaman->user_id === $user->id;
    }
}
