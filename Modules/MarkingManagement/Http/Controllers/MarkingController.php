<?php

namespace Modules\MarkingManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\MarkingManagement\Entities\Marking;
use Modules\MarkingManagement\Http\Requests\StoreMarkingRequest;
use Modules\MarkingManagement\Http\Requests\UpdateMarkingRequest;
use Modules\MarkingManagement\Http\Requests\ExtendMarkingRequest;
use Modules\MarkingManagement\Services\MarkingService;
use App\Models\Ukm;
use Modules\PrasaranaManagement\Entities\Prasarana;

class MarkingController extends Controller
{
    public function __construct(
        private readonly MarkingService $markingService
    ) {
        $this->middleware('auth');
        $this->middleware('profile.completed');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Marking::class);

        $filters = $request->only(['status', 'search', 'start_date', 'end_date']);

        // Non-admin users can only see their own markings
        if (!Auth::user()->hasPermissionTo('marking.manage')) {
            $filters['user_id'] = Auth::id();
        }

        $markings = $this->markingService->getMarkings($filters, 15);
        $statuses = Marking::getStatuses();

        return view('markingmanagement::marking.index', compact('markings', 'filters', 'statuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Marking::class);

        $prasaranas = Prasarana::where('status', 'tersedia')->get();
        $ukms = Ukm::active()->orderBy('nama')->get();
        $markingDuration = config('markingmanagement.marking_duration_days', 3);

        return view('markingmanagement::marking.create', compact('prasaranas', 'ukms', 'markingDuration'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMarkingRequest $request)
    {
        $this->authorize('create', Marking::class);

        // Check for conflicts
        $conflict = $this->markingService->checkConflicts($request->validated());
        if ($conflict) {
            return redirect()->back()
                ->with('error', $conflict)
                ->withInput();
        }

        try {
            $marking = $this->markingService->createMarking($request->validated());
            
            return redirect()->route('marking.show', $marking)
                ->with('success', 'Marking berhasil dibuat. Marking akan kadaluarsa pada ' . $marking->expires_at->format('d/m/Y H:i') . '.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat membuat marking: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Marking $marking)
    {
        $this->authorize('view', $marking);

        $marking->load(['user', 'ukm', 'prasarana']);

        return view('markingmanagement::marking.show', compact('marking'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Marking $marking)
    {
        $this->authorize('update', $marking);

        // Only allow editing if status is active and not expired
        if (!$marking->isActive() || $marking->isExpired()) {
            return redirect()->route('marking.show', $marking)
                ->with('error', 'Marking tidak dapat diedit karena sudah kadaluarsa atau tidak aktif.');
        }

        $prasaranas = Prasarana::where('status', 'tersedia')->get();
        $ukms = Ukm::active()->orderBy('nama')->get();

        return view('markingmanagement::marking.edit', compact('marking', 'prasaranas', 'ukms'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMarkingRequest $request, Marking $marking)
    {
        $this->authorize('update', $marking);

        // Check for conflicts (excluding current marking)
        $conflict = $this->markingService->checkConflicts($request->validated(), $marking->id);
        if ($conflict) {
            return redirect()->back()
                ->with('error', $conflict)
                ->withInput();
        }

        try {
            $marking = $this->markingService->updateMarking($marking, $request->validated());

            return redirect()->route('marking.show', $marking)
                ->with('success', 'Marking berhasil diperbarui.');

        } catch (\RuntimeException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat memperbarui marking.')
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage (cancel marking).
     */
    public function destroy(Marking $marking)
    {
        $this->authorize('delete', $marking);

        try {
            $this->markingService->cancelMarking($marking);

            return redirect()->route('marking.index')
                ->with('success', 'Marking berhasil dibatalkan.');

        } catch (\RuntimeException $e) {
            return redirect()->route('marking.show', $marking)
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat membatalkan marking.');
        }
    }

    /**
     * Convert marking to peminjaman.
     */
    public function convert(Marking $marking)
    {
        $this->authorize('convert', $marking);

        // Only allow converting if status is active and not expired
        if (!$marking->canBeConverted()) {
            return redirect()->route('marking.show', $marking)
                ->with('error', 'Marking tidak dapat dikonversi karena sudah kadaluarsa atau tidak aktif.');
        }

        // Redirect to peminjaman create with marking data
        // TODO: Implement peminjaman module integration
        return redirect()->route('marking.show', $marking)
            ->with('info', 'Fitur konversi ke peminjaman akan segera tersedia.');
    }

    /**
     * Extend marking expiration.
     */
    public function extend(ExtendMarkingRequest $request, Marking $marking)
    {
        $this->authorize('extend', $marking);

        try {
            $marking = $this->markingService->extendMarking($marking, $request->extension_days);
            
            return redirect()->route('marking.show', $marking)
                ->with('success', "Marking berhasil diperpanjang hingga {$marking->expires_at->format('d/m/Y H:i')}.");
        } catch (\RuntimeException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat memperpanjang marking.');
        }
    }
}
