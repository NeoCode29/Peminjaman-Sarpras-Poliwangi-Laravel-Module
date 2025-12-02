<?php

namespace Modules\PrasaranaManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Entities\PrasaranaImage;
use Modules\PrasaranaManagement\Http\Requests\StorePrasaranaRequest;
use Modules\PrasaranaManagement\Http\Requests\UpdatePrasaranaRequest;
use Modules\PrasaranaManagement\Services\KategoriPrasaranaService;
use Modules\PrasaranaManagement\Services\PrasaranaService;

class PrasaranaController extends Controller
{
    public function __construct(
        private readonly PrasaranaService $prasaranaService,
        private readonly KategoriPrasaranaService $kategoriService,
        private readonly \Modules\PrasaranaManagement\Services\PrasaranaApproverService $prasaranaApproverService,
    ) {
        $this->middleware('auth');
        $this->middleware('profile.completed');

        // Auto-authorize all resource methods
        $this->authorizeResource(Prasarana::class, 'prasarana');
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'kategori_id', 'status', 'lokasi']);
        $prasarana = $this->prasaranaService->list($filters, 15);
        $kategoriPrasarana = $this->kategoriService->getAllKategori();

        return view('prasaranamanagement::prasarana.index', compact('prasarana', 'kategoriPrasarana', 'filters'));
    }

    public function create(): View
    {
        $kategoriPrasarana = $this->kategoriService->getAllKategori();

        return view('prasaranamanagement::prasarana.create', compact('kategoriPrasarana'));
    }

    public function store(StorePrasaranaRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();
            $images = $request->file('images', []);

            $this->prasaranaService->create($data, $request->user()->id, $images);

            return redirect()
                ->route('prasarana.index')
                ->with('success', 'Prasarana berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal menambahkan prasarana: ' . $e->getMessage()]);
        }
    }

    public function show(Prasarana $prasarana): View
    {
        $prasarana->load(['kategori', 'images', 'createdBy', 'approvers.approver']);

        $approvers = $this->prasaranaApproverService->getApproversForPrasarana($prasarana->id, [], 10);
        
        // Daftar user yang bisa dipilih sebagai approver (exclude existing approvers)
        $existingApproverIds = $prasarana->approvers()->pluck('approver_id')->toArray();
        $availableApprovers = User::whereNotIn('id', $existingApproverIds)
            ->orderBy('name')
            ->get();

        return view('prasaranamanagement::prasarana.show', compact('prasarana', 'approvers', 'availableApprovers'));
    }

    public function edit(Prasarana $prasarana): View
    {
        $prasarana->load(['kategori', 'images']);
        $kategoriPrasarana = $this->kategoriService->getAllKategori();

        return view('prasaranamanagement::prasarana.edit', compact('prasarana', 'kategoriPrasarana'));
    }

    public function update(UpdatePrasaranaRequest $request, Prasarana $prasarana): RedirectResponse
    {
        try {
            $data = $request->validated();
            $images = $request->file('images', []);
            $removeImages = $request->input('remove_images', []);
            $removeImageIds = array_keys(array_filter($removeImages, fn ($value) => (string) $value === '1'));

            $this->prasaranaService->update($prasarana, $data, $images, $removeImageIds);

            return redirect()
                ->route('prasarana.show', $prasarana)
                ->with('success', 'Prasarana berhasil diperbarui.');
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal memperbarui prasarana: ' . $e->getMessage()]);
        }
    }

    public function destroy(Prasarana $prasarana): RedirectResponse
    {
        try {
            $this->prasaranaService->delete($prasarana);

            return redirect()
                ->route('prasarana.index')
                ->with('success', 'Prasarana berhasil dihapus.');
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('prasarana.show', $prasarana)
                ->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Gagal menghapus prasarana: ' . $e->getMessage()]);
        }
    }

    public function destroyImage(PrasaranaImage $image): RedirectResponse
    {
        $prasarana = $image->prasarana;

        if ($prasarana) {
            $this->authorize('update', $prasarana);
        }

        $this->prasaranaService->deleteImage($image);

        return redirect()
            ->route('prasarana.edit', $prasarana)
            ->with('success', 'Gambar prasarana berhasil dihapus.');
    }
}
