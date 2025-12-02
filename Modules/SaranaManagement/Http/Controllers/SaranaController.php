<?php

namespace Modules\SaranaManagement\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaUnit;
use Modules\SaranaManagement\Http\Requests\StoreSaranaRequest;
use Modules\SaranaManagement\Http\Requests\UpdateSaranaRequest;
use Modules\SaranaManagement\Services\SaranaService;
use Modules\SaranaManagement\Services\KategoriSaranaService;
use Modules\SaranaManagement\Services\SaranaApproverService;
use App\Models\User;

class SaranaController extends Controller
{
    public function __construct(
        private readonly SaranaService $saranaService,
        private readonly KategoriSaranaService $kategoriService,
        private readonly SaranaApproverService $saranaApproverService,
    ) {
        $this->middleware('auth');
        $this->middleware('profile.completed');
        
        // Auto-authorize all resource methods
        $this->authorizeResource(Sarana::class, 'sarana');
    }

    /**
     * Display a listing of saranas
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'kategori_id', 'kondisi', 'status_ketersediaan']);
        $saranas = $this->saranaService->getSaranas($filters, 15);
        $kategoris = $this->kategoriService->getAllKategori();

        return view('saranamanagement::sarana.index', compact('saranas', 'kategoris', 'filters'));
    }

    /**
     * Show the form for creating a new sarana
     */
    public function create(): View
    {
        $kategoris = $this->kategoriService->getAllKategori();
        
        return view('saranamanagement::sarana.create', compact('kategoris'));
    }

    /**
     * Store a newly created sarana
     */
    public function store(StoreSaranaRequest $request): RedirectResponse
    {
        try {
            $this->saranaService->createSarana($request->validated());
            
            return redirect()
                ->route('sarana.index')
                ->with('success', 'Sarana berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal menambahkan sarana: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified sarana
     */
    public function show(Sarana $sarana): View
    {
        $sarana->load(['kategori']);

        // Approvers dengan pagination
        $approvers = $this->saranaApproverService->getApproversForSarana($sarana->id, [], 10);

        // Daftar user yang bisa dipilih sebagai approver (exclude existing approvers)
        $existingApproverIds = $sarana->approvers()->pluck('approver_id')->toArray();
        $availableApprovers = User::whereNotIn('id', $existingApproverIds)
            ->orderBy('name')
            ->get();
        
        return view('saranamanagement::sarana.show', compact('sarana', 'availableApprovers', 'approvers'));
    }

    /**
     * Show the form for editing the specified sarana
     */
    public function edit(Sarana $sarana): View
    {
        $kategoris = $this->kategoriService->getAllKategori();
        
        return view('saranamanagement::sarana.edit', compact('sarana', 'kategoris'));
    }

    /**
     * Update the specified sarana
     */
    public function update(UpdateSaranaRequest $request, Sarana $sarana): RedirectResponse
    {
        try {
            $this->saranaService->updateSarana($sarana, $request->validated());
            
            return redirect()
                ->route('sarana.index')
                ->with('success', 'Sarana berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal memperbarui sarana: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified sarana
     */
    public function destroy(Sarana $sarana): RedirectResponse
    {
        try {
            $this->saranaService->deleteSarana($sarana);
            
            return redirect()
                ->route('sarana.index')
                ->with('success', 'Sarana berhasil dihapus.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('sarana.show', $sarana)
                ->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Gagal menghapus sarana: ' . $e->getMessage()]);
        }
    }

    /**
     * Tampilkan halaman manajemen unit untuk sarana serialized
     */
    public function units(Sarana $sarana): View
    {
        $this->authorize('manageUnits', $sarana);

        $sarana->load(['kategori', 'units']);

        return view('saranamanagement::sarana.units', compact('sarana'));
    }

    /**
     * Simpan unit baru untuk sarana
     */
    public function storeUnit(Request $request, Sarana $sarana): RedirectResponse
    {
        $this->authorize('manageUnits', $sarana);

        $validated = $request->validate([
            'unit_code' => ['required', 'string', 'max:80'],
            'unit_status' => ['nullable', 'in:tersedia,rusak,maintenance,hilang'],
        ]);

        $status = $validated['unit_status'] ?? 'tersedia';

        $this->saranaService->addUnit($sarana, $validated['unit_code'], $status);

        return redirect()
            ->route('sarana.units.index', $sarana)
            ->with('success', 'Unit sarana berhasil ditambahkan.');
    }

    /**
     * Update unit sarana
     */
    public function updateUnit(Request $request, Sarana $sarana, SaranaUnit $unit): RedirectResponse
    {
        $this->authorize('manageUnits', $sarana);

        // Pastikan unit milik sarana yang sama
        if ($unit->sarana_id !== $sarana->id) {
            abort(404);
        }

        $validated = $request->validate([
            'unit_code' => ['required', 'string', 'max:80'],
            'unit_status' => ['required', 'in:tersedia,rusak,maintenance,hilang'],
        ]);

        $this->saranaService->updateUnit($unit, $validated);

        return redirect()
            ->route('sarana.units.index', $sarana)
            ->with('success', 'Unit sarana berhasil diperbarui.');
    }

    /**
     * Hapus unit sarana
     */
    public function destroyUnit(Sarana $sarana, SaranaUnit $unit): RedirectResponse
    {
        $this->authorize('manageUnits', $sarana);

        if ($unit->sarana_id !== $sarana->id) {
            abort(404);
        }

        $this->saranaService->deleteUnit($unit);

        return redirect()
            ->route('sarana.units.index', $sarana)
            ->with('success', 'Unit sarana berhasil dihapus.');
    }
}
