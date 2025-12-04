<?php

namespace Modules\PeminjamanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ukm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $filters = $request->only(['search', 'status', 'start_date', 'end_date']);

        // Non-admin users only see their own peminjaman
        if (!Auth::user()->hasPermissionTo('peminjaman.view')) {
            $filters['user_id'] = Auth::id();
        }

        $peminjaman = $this->peminjamanService->getPeminjaman($filters, 15);

        return view('peminjamanmanagement::peminjaman.index', compact('peminjaman', 'filters'));
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

        // Check conflicts
        $conflict = $this->slotConflictService->checkConflicts($request);
        if ($conflict) {
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

        return view('peminjamanmanagement::peminjaman.show', array_merge(
            compact('peminjaman'),
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

        // Check conflicts
        $conflict = $this->slotConflictService->checkConflicts($request, $peminjaman->id);
        if ($conflict) {
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
}
