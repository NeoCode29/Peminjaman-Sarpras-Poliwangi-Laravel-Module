<?php

namespace Modules\PrasaranaManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Modules\PrasaranaManagement\Entities\Prasarana;
use Modules\PrasaranaManagement\Entities\PrasaranaApprover;
use Modules\PrasaranaManagement\Http\Requests\StorePrasaranaApproverRequest;
use Modules\PrasaranaManagement\Http\Requests\UpdatePrasaranaApproverRequest;
use Modules\PrasaranaManagement\Services\PrasaranaApproverService;

class PrasaranaApproverController extends Controller
{
    public function __construct(
        private readonly PrasaranaApproverService $service
    ) {
        $this->middleware(['auth', 'profile.completed']);
    }

    public function store(StorePrasaranaApproverRequest $request, Prasarana $prasarana): RedirectResponse
    {
        $this->authorize('create', [PrasaranaApprover::class, $prasarana]);

        $data = $request->validated();
        $data['prasarana_id'] = $prasarana->id;
        $data['is_active'] = $data['is_active'] ?? true;

        $this->service->createApprover($data);

        return redirect()
            ->route('prasarana.show', $prasarana)
            ->with('sarpras_success', 'Approver prasarana berhasil ditambahkan.');
    }

    public function update(UpdatePrasaranaApproverRequest $request, Prasarana $prasarana, PrasaranaApprover $approver): RedirectResponse
    {
        $this->authorize('update', $approver);

        $this->service->updateApprover($approver, $request->validated());

        return redirect()
            ->route('prasarana.show', $prasarana)
            ->with('sarpras_success', 'Approver prasarana berhasil diperbarui.');
    }

    public function destroy(Prasarana $prasarana, PrasaranaApprover $approver): RedirectResponse
    {
        $this->authorize('delete', $approver);

        $this->service->deleteApprover($approver);

        return redirect()
            ->route('prasarana.show', $prasarana)
            ->with('sarpras_success', 'Approver prasarana berhasil dihapus.');
    }
}
