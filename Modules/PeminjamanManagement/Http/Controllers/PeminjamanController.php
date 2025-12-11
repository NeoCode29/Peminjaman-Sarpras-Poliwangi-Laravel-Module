<?php

namespace Modules\PeminjamanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ukm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Http\Requests\StorePeminjamanRequest;
use Modules\PeminjamanManagement\Http\Requests\UpdatePeminjamanRequest;
use Modules\PeminjamanManagement\Services\PeminjamanService;
use Modules\PeminjamanManagement\Services\SlotConflictService;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\SaranaManagement\Entities\Sarana;

class PeminjamanController extends Controller
{
    public function __construct(
        private readonly PeminjamanService $peminjamanService,
        private readonly SlotConflictService $slotConflictService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Peminjaman::class);

        $filters = $request->only(['search', 'status', 'pickup_status', 'start_date', 'end_date']);

        // Non-admin users only see their own peminjaman
        $isAdmin = Auth::user()->hasPermissionTo('peminjaman.view');
        if (!$isAdmin) {
            $filters['user_id'] = Auth::id();
        }

        $peminjaman = $this->peminjamanService->getPeminjaman($filters, 15);

        // Build stats for matrix
        $stats = $this->buildPeminjamanStats($isAdmin ? null : Auth::id());

        return view('peminjamanmanagement::peminjaman.index', compact('peminjaman', 'filters', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Peminjaman::class);

        $user = Auth::user();
        $formData = $this->peminjamanService->getFormData($user->id);

        if (!$formData['canCreate']) {
            return redirect()->route('peminjaman.index')
                ->with('error', "Kuota peminjaman aktif telah tercapai (maksimal {$formData['maxActiveBorrowings']})");
        }

        $prasarana = Prasarana::where('status', 'tersedia')->get();
        $sarana = Sarana::where('jumlah_tersedia', '>', 0)->get();

        $ukms = [];
        if ($user->user_type === 'mahasiswa') {
            $ukms = Ukm::orderBy('nama')->get(['id', 'nama']);
        }

        return view('peminjamanmanagement::peminjaman.create', array_merge(
            compact('prasarana', 'sarana', 'ukms'),
            $formData
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePeminjamanRequest $request)
    {
        $this->authorize('create', Peminjaman::class);

        $validated = $request->validated();

        // Check quota
        if (!$this->peminjamanService->hasQuotaAvailable(Auth::id())) {
            return redirect()->back()
                ->with('error', 'Kuota peminjaman aktif telah tercapai.')
                ->withInput();
        }

        // Check conflicts (bypass for users with override permission)
        $conflict = $this->slotConflictService->checkConflicts($request);
        if ($conflict && !Auth::user()->can('peminjaman.override')) {
            return redirect()->back()
                ->with('error', $conflict)
                ->withInput();
        }

        try {
            $peminjaman = $this->peminjamanService->createPeminjaman(
                [
                    'user_id' => Auth::id(),
                    'prasarana_id' => $validated['prasarana_id'] ?? null,
                    'lokasi_custom' => $validated['lokasi_custom'] ?? null,
                    'jumlah_peserta' => $validated['jumlah_peserta'] ?? null,
                    'ukm_id' => $validated['ukm_id'] ?? null,
                    'event_name' => $validated['event_name'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'start_time' => $validated['start_time'] ?? null,
                    'end_time' => $validated['end_time'] ?? null,
                ],
                $validated['sarana_items'] ?? [],
                $request->file('surat')
            );

            // Sync konflik group
            $pendingConflicts = $this->slotConflictService->findPendingConflicts($peminjaman);
            $this->peminjamanService->syncKonflikGroup($peminjaman, $pendingConflicts);

            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('success', 'Pengajuan peminjaman berhasil dibuat dan sedang menunggu persetujuan.');
        } catch (\Exception $e) {
            \Log::error('Peminjaman create failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat membuat pengajuan peminjaman: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Peminjaman $peminjaman)
    {
        $this->authorize('view', $peminjaman);

        $peminjaman = $this->peminjamanService->findByIdWithRelations($peminjaman->id);

        // Build approval summaries
        $approvalData = $this->buildApprovalData($peminjaman);

        $serializedUnitOptions = [];
        if (auth()->user()?->can('adjustSarpras', $peminjaman)) {
            $serializedUnitOptions = $peminjaman->items
                ->filter(function ($item) {
                    return optional($item->sarana)->type === 'serialized';
                })
                ->mapWithKeys(function ($item) {
                    $activeAssignments = $item->units->where('status', 'active');
                    $assignedUnits = $activeAssignments->pluck('unit_id');

                    $unitCollection = \Modules\SaranaManagement\Entities\SaranaUnit::where('sarana_id', $item->sarana_id)
                        ->orderBy('unit_code')
                        ->get();

                    $units = $unitCollection
                        ->map(function ($unit) use ($assignedUnits) {
                            $isAssignedHere = $assignedUnits->contains($unit->id);

                            return [
                                'id' => $unit->id,
                                'unit_code' => $unit->unit_code,
                                'status' => $unit->unit_status,
                                'is_assigned_to_this' => $isAssignedHere,
                            ];
                        })
                        ->values();

                    return [$item->id => [
                        'max_selectable' => $item->qty_approved ?? $item->qty_requested,
                        'requested' => $item->qty_requested,
                        'approved' => $item->qty_approved,
                        'units' => $units->toArray(),
                        'selected_count' => $assignedUnits->count(),
                    ]];
                })
                ->toArray();
        }

        return view('peminjamanmanagement::peminjaman.show', array_merge(
            compact('peminjaman', 'serializedUnitOptions'),
            $approvalData
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Peminjaman $peminjaman)
    {
        $this->authorize('update', $peminjaman);

        if (!$peminjaman->isPending() && !$peminjaman->isApproved()) {
            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('error', 'Peminjaman yang sudah diproses tidak dapat diedit.');
        }

        $user = Auth::user();
        $formData = $this->peminjamanService->getFormData($user->id);

        $prasarana = Prasarana::where('status', 'tersedia')->get();
        $sarana = Sarana::where('jumlah_tersedia', '>', 0)->get();

        $ukms = [];
        if ($user->user_type === 'mahasiswa') {
            $ukms = Ukm::orderBy('nama')->get(['id', 'nama']);
        }

        return view('peminjamanmanagement::peminjaman.edit', array_merge(
            compact('peminjaman', 'prasarana', 'sarana', 'ukms'),
            $formData
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePeminjamanRequest $request, Peminjaman $peminjaman)
    {
        $this->authorize('update', $peminjaman);

        if (!$peminjaman->isPending() && !$peminjaman->isApproved()) {
            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('error', 'Peminjaman yang sudah diproses tidak dapat diedit.');
        }

        $validated = $request->validated();

        // Check conflicts (bypass for users with override permission)
        $conflict = $this->slotConflictService->checkConflicts($request, $peminjaman->id);
        if ($conflict && !Auth::user()->can('peminjaman.override')) {
            return redirect()->back()
                ->with('error', $conflict)
                ->withInput();
        }

        try {
            $peminjaman = $this->peminjamanService->updatePeminjaman(
                $peminjaman,
                [
                    'prasarana_id' => $validated['prasarana_id'] ?? null,
                    'lokasi_custom' => $validated['lokasi_custom'] ?? null,
                    'jumlah_peserta' => $validated['jumlah_peserta'] ?? null,
                    'ukm_id' => $validated['ukm_id'] ?? null,
                    'event_name' => $validated['event_name'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'start_time' => $validated['start_time'] ?? null,
                    'end_time' => $validated['end_time'] ?? null,
                ],
                $validated['sarana_items'] ?? [],
                $request->file('surat')
            );

            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('success', 'Peminjaman berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat memperbarui peminjaman.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Peminjaman $peminjaman)
    {
        $this->authorize('delete', $peminjaman);

        try {
            $this->peminjamanService->deletePeminjaman($peminjaman);

            return redirect()->route('peminjaman.index')
                ->with('success', 'Peminjaman berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menghapus peminjaman.');
        }
    }

    /**
     * Cancel peminjaman.
     */
    public function cancel(Request $request, Peminjaman $peminjaman)
    {
        $this->authorize('cancel', $peminjaman);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->peminjamanService->cancelPeminjaman(
                $peminjaman,
                Auth::id(),
                $request->input('reason')
            );

            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('success', 'Peminjaman berhasil dibatalkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat membatalkan peminjaman.');
        }
    }

    /**
     * Upload pickup photo by borrower.
     */
    public function uploadPickupPhoto(Request $request, Peminjaman $peminjaman)
    {
        $this->authorize('uploadPickupPhoto', $peminjaman);

        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        try {
            $file = $request->file('foto');
            $dir = 'peminjaman/pickup/' . date('Y/m');

            // Hapus foto lama jika ada
            if ($peminjaman->foto_pickup_path) {
                Storage::disk('public')->delete($peminjaman->foto_pickup_path);
            }

            $path = $file->store($dir, 'public');

            $peminjaman->update([
                'foto_pickup_path' => $path,
            ]);

            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('success', 'Foto pengambilan berhasil diunggah.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengunggah foto pengambilan: ' . $e->getMessage());
        }
    }

    /**
     * Upload return photo by borrower.
     */
    public function uploadReturnPhoto(Request $request, Peminjaman $peminjaman)
    {
        $this->authorize('uploadReturnPhoto', $peminjaman);

        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        try {
            $file = $request->file('foto');
            $dir = 'peminjaman/return/' . date('Y/m');

            // Hapus foto lama jika ada
            if ($peminjaman->foto_return_path) {
                Storage::disk('public')->delete($peminjaman->foto_return_path);
            }

            $path = $file->store($dir, 'public');

            $peminjaman->update([
                'foto_return_path' => $path,
            ]);

            return redirect()->route('peminjaman.show', $peminjaman)
                ->with('success', 'Foto pengembalian berhasil diunggah.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengunggah foto pengembalian: ' . $e->getMessage());
        }
    }

    /**
     * Build approval data for show view.
     */
    protected function buildApprovalData(Peminjaman $peminjaman): array
    {
        $approvalStatus = $peminjaman->approvalStatus;
        $globalStatus = optional($approvalStatus)->global_approval_status ?? 'pending';
        $overallStatus = optional($approvalStatus)->overall_status ?? 'pending';

        // Override approvals
        $overrideApprovals = $peminjaman->approvalWorkflow
            ->whereNotNull('overridden_at')
            ->sortByDesc('overridden_at')
            ->values();

        // Konflik members
        $konflikMembers = collect();
        if (!empty($peminjaman->konflik)) {
            $konflikMembers = Peminjaman::with('user')
                ->where('konflik', $peminjaman->konflik)
                ->where('id', '!=', $peminjaman->id)
                ->orderBy('created_at')
                ->get();
        }

        // Approval action summary
        $approvalActionSummary = [
            'has_pending' => $peminjaman->approvalWorkflow->where('status', 'pending')->isNotEmpty(),
            'global' => [],
            'prasarana' => [],
            'sarana' => [],
        ];

        $peminjaman->approvalWorkflow->groupBy('approval_type')->each(function ($group, $type) use (&$approvalActionSummary) {
            $approvalActionSummary[$type] = $group->where('status', 'pending')->map(function ($workflow) {
                return [
                    'id' => $workflow->id,
                    'name' => optional($workflow->approver)->name ?? '-',
                    'level' => $workflow->approval_level,
                    'created_at' => optional($workflow->created_at)->format('d/m/Y H:i'),
                    'approver_id' => $workflow->approver_id,
                    'reference_id' => $workflow->sarana_id ?? $workflow->prasarana_id,
                ];
            })->values()->all();
        });

        return [
            'globalStatus' => $globalStatus,
            'overallStatus' => $overallStatus,
            'overrideApprovals' => $overrideApprovals,
            'konflikMembers' => $konflikMembers,
            'approvalActionSummary' => $approvalActionSummary,
        ];
    }

    /**
     * Build peminjaman statistics for dashboard matrix.
     */
    protected function buildPeminjamanStats(?int $userId = null): array
    {
        $query = Peminjaman::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $total = (clone $query)->count();
        $pending = (clone $query)->where('status', Peminjaman::STATUS_PENDING)->count();
        $approved = (clone $query)->where('status', Peminjaman::STATUS_APPROVED)->count();
        $pickedUp = (clone $query)->where('status', Peminjaman::STATUS_PICKED_UP)->count();
        $returned = (clone $query)->where('status', Peminjaman::STATUS_RETURNED)->count();
        $cancelled = (clone $query)->where('status', Peminjaman::STATUS_CANCELLED)->count();

        // Overdue: picked_up, past end_date, not returned
        $overdue = (clone $query)
            ->where('status', Peminjaman::STATUS_PICKED_UP)
            ->whereNull('return_validated_at')
            ->whereDate('end_date', '<', now()->toDateString())
            ->count();

        // Belum diambil: approved tapi belum pickup
        $notPickedUp = (clone $query)
            ->where('status', Peminjaman::STATUS_APPROVED)
            ->whereNull('pickup_validated_at')
            ->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'picked_up' => $pickedUp,
            'returned' => $returned,
            'cancelled' => $cancelled,
            'overdue' => $overdue,
            'not_picked_up' => $notPickedUp,
        ];
    }
}
