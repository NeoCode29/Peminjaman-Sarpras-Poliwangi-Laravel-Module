<?php

namespace Modules\SaranaManagement\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\SaranaManagement\Entities\KategoriSarana;
use Modules\SaranaManagement\Http\Requests\StoreKategoriSaranaRequest;
use Modules\SaranaManagement\Http\Requests\UpdateKategoriSaranaRequest;
use Modules\SaranaManagement\Services\KategoriSaranaService;

class KategoriSaranaController extends Controller
{
    public function __construct(
        private readonly KategoriSaranaService $kategoriService
    ) {
        $this->middleware('auth');
        $this->middleware('profile.completed');
        
        // Auto-authorize all resource methods
        $this->authorizeResource(KategoriSarana::class, 'kategori_sarana');
    }

    /**
     * Display a listing of kategori saranas
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search']);
        $kategoris = $this->kategoriService->getKategori($filters, 15);

        return view('saranamanagement::kategori.index', compact('kategoris', 'filters'));
    }

    /**
     * Show the form for creating a new kategori
     */
    public function create(): View
    {
        return view('saranamanagement::kategori.create');
    }

    /**
     * Store a newly created kategori
     */
    public function store(StoreKategoriSaranaRequest $request): RedirectResponse
    {
        try {
            $this->kategoriService->createKategori($request->validated());
            
            return redirect()
                ->route('kategori-sarana.index')
                ->with('success', 'Kategori sarana berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal menambahkan kategori: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified kategori
     */
    public function show(KategoriSarana $kategoriSarana): View
    {
        $kategoriSarana->loadCount('saranas');
        
        return view('saranamanagement::kategori.show', compact('kategoriSarana'));
    }

    /**
     * Show the form for editing the specified kategori
     */
    public function edit(KategoriSarana $kategoriSarana): View
    {
        return view('saranamanagement::kategori.edit', compact('kategoriSarana'));
    }

    /**
     * Update the specified kategori
     */
    public function update(UpdateKategoriSaranaRequest $request, KategoriSarana $kategoriSarana): RedirectResponse
    {
        try {
            $this->kategoriService->updateKategori($kategoriSarana, $request->validated());
            
            return redirect()
                ->route('kategori-sarana.index')
                ->with('success', 'Kategori sarana berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal memperbarui kategori: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified kategori
     */
    public function destroy(KategoriSarana $kategoriSarana): RedirectResponse
    {
        try {
            $this->kategoriService->deleteKategori($kategoriSarana);
            
            return redirect()
                ->route('kategori-sarana.index')
                ->with('success', 'Kategori sarana berhasil dihapus.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('kategori-sarana.show', $kategoriSarana)
                ->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Gagal menghapus kategori: ' . $e->getMessage()]);
        }
    }
}
