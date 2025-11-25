<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateSystemSettingRequest;
use App\Services\SystemSettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $systemSettingService,
    ) {
    }

    /**
     * Display system settings page
     */
    public function index(): View
    {
        // Check permission
        $this->authorize('system.settings');

        $data = $this->systemSettingService->getSettingsForPage();

        return view('settings.index', $data);
    }

    /**
     * Update system settings
     */
    public function update(UpdateSystemSettingRequest $request): RedirectResponse
    {
        // Check permission
        $this->authorize('system.settings');

        try {
            $this->systemSettingService->updateSettings(
                $request->validated('settings') ?? []
            );

            return redirect()->route('settings.index')
                ->with('success', 'Pengaturan sistem berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
