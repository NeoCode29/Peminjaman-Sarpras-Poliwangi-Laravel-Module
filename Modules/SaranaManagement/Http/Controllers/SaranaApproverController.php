<?php

namespace Modules\SaranaManagement\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use App\Http\Controllers\Controller;
use Modules\SaranaManagement\Entities\Sarana;
use Modules\SaranaManagement\Entities\SaranaApprover;
use Modules\SaranaManagement\Http\Requests\StoreSaranaApproverRequest;
use Modules\SaranaManagement\Http\Requests\UpdateSaranaApproverRequest;
use Modules\SaranaManagement\Services\SaranaApproverService;

class SaranaApproverController extends Controller
{
    public function __construct(
        private readonly SaranaApproverService $service
    ) {
        $this->middleware(['auth', 'profile.completed']);
    }

    public function store(StoreSaranaApproverRequest $request, Sarana $sarana): RedirectResponse
    {
        $this->authorize('create', [SaranaApprover::class, $sarana]);

        $data = $request->validated();
        $data['sarana_id'] = $sarana->id;
        $data['is_active'] = $data['is_active'] ?? true;

        $this->service->createApprover($data);

        return redirect()
            ->route('sarana.show', $sarana)
            ->with('sarpras_success', 'Approver sarana berhasil ditambahkan.');
    }

    public function update(UpdateSaranaApproverRequest $request, Sarana $sarana, SaranaApprover $approver): RedirectResponse
    {
        $this->authorize('update', $approver);

        $this->service->updateApprover($approver, $request->validated());

        return redirect()
            ->route('sarana.show', $sarana)
            ->with('sarpras_success', 'Approver sarana berhasil diperbarui.');
    }

    public function destroy(Sarana $sarana, SaranaApprover $approver): RedirectResponse
    {
        $this->authorize('delete', $approver);

        $this->service->deleteApprover($approver);

        return redirect()
            ->route('sarana.show', $sarana)
            ->with('sarpras_success', 'Approver sarana berhasil dihapus.');
    }
}
