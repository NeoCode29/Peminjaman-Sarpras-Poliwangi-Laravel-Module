<?php

namespace Modules\PrasaranaManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\PrasaranaManagement\Entities\KategoriPrasarana;
use Modules\PrasaranaManagement\Http\Requests\StoreKategoriPrasaranaRequest;
use Modules\PrasaranaManagement\Http\Requests\UpdateKategoriPrasaranaRequest;
use Modules\PrasaranaManagement\Services\KategoriPrasaranaService;

class KategoriPrasaranaController extends Controller
{
    public function __construct(
        private readonly KategoriPrasaranaService $kategoriService,
    ) {
        $this->middleware('auth');
        $this->middleware('profile.completed');

        // Auto-authorize all resource methods
        $this->authorizeResource(KategoriPrasarana::class, 'kategori_prasarana');
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search']);
        $kategoris = $this->kategoriService->getKategori($filters, 15);

        return view('prasaranamanagement::kategori.index', compact('kategoris', 'filters'));
    }

    public function create(): View
    {
        return view('prasaranamanagement::kategori.create');
    }

    public function store(StoreKategoriPrasaranaRequest $request): RedirectResponse
    {
        try {
            $this->kategoriService->createKategori($request->validated());

            return redirect()
                ->route('kategori-prasarana.index')
                ->with('success', 'Kategori prasarana berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal menambahkan kategori: ' . $e->getMessage()]);
        }
    }

    public function show(KategoriPrasarana $kategoriPrasarana): View
    {
        $kategoriPrasarana->loadCount('prasarana');

        return view('prasaranamanagement::kategori.show', compact('kategoriPrasarana'));
    }

    public function edit(KategoriPrasarana $kategoriPrasarana): View
    {
        return view('prasaranamanagement::kategori.edit', compact('kategoriPrasarana'));
    }

    public function update(UpdateKategoriPrasaranaRequest $request, KategoriPrasarana $kategoriPrasarana): RedirectResponse
    {
        try {
            $this->kategoriService->updateKategori($kategoriPrasarana, $request->validated());

            return redirect()
                ->route('kategori-prasarana.index')
                ->with('success', 'Kategori prasarana berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal memperbarui kategori: ' . $e->getMessage()]);
        }
    }

    public function destroy(KategoriPrasarana $kategoriPrasarana): RedirectResponse
    {
        try {
            $this->kategoriService->deleteKategori($kategoriPrasarana);

            return redirect()
                ->route('kategori-prasarana.index')
                ->with('success', 'Kategori prasarana berhasil dihapus.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('kategori-prasarana.show', $kategoriPrasarana)
                ->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Gagal menghapus kategori: ' . $e->getMessage()]);
        }
    }
}
