<?php

namespace Modules\PeminjamanManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Modules\PeminjamanManagement\Entities\Peminjaman;
use Modules\PeminjamanManagement\Services\PeminjamanReportService;

class PeminjamanReportController extends Controller
{
    public function __construct(
        private readonly PeminjamanReportService $reportService,
    ) {
        $this->middleware(['auth', 'profile.completed']);
    }

    public function index(Request $request)
    {
        // Hanya admin / user dengan permission tertentu yang boleh akses, fallback ke policy viewAny
        $this->authorize('viewAny', Peminjaman::class);

        $filters = $this->prepareReportFilters($request);

        $perPage = (int) $request->input('per_page', 15);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 15;

        $reportData = $this->reportService->getPeminjamanReportData($filters, $perPage);

        $statusOptions = [
            null => 'Semua Status',
            Peminjaman::STATUS_PENDING => 'Pending',
            Peminjaman::STATUS_APPROVED => 'Disetujui',
            Peminjaman::STATUS_REJECTED => 'Ditolak',
            Peminjaman::STATUS_PICKED_UP => 'Sedang Dipinjam',
            Peminjaman::STATUS_RETURNED => 'Dikembalikan',
            Peminjaman::STATUS_CANCELLED => 'Dibatalkan',
            'conflicted' => 'Termasuk Konflik',
        ];

        return view('peminjamanmanagement::peminjaman.report-index', [
            'paginator' => $reportData['paginator'],
            'summary' => $reportData['summary'],
            'filters' => $filters,
            'statusOptions' => $statusOptions,
        ]);
    }

    protected function prepareReportFilters(Request $request): array
    {
        $defaultEnd = Carbon::now()->endOfDay();
        $defaultStart = $defaultEnd->copy()->subMonth()->startOfDay();

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        try {
            $start = $startDate ? Carbon::parse($startDate)->startOfDay() : $defaultStart;
        } catch (\Throwable $e) {
            $start = $defaultStart;
        }

        try {
            $end = $endDate ? Carbon::parse($endDate)->endOfDay() : $defaultEnd;
        } catch (\Throwable $e) {
            $end = $defaultEnd;
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $filters = [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => $request->input('status'),
            'pickup_status' => $request->input('pickup_status'),
            'user_id' => $request->input('user_id'),
            'search' => $request->input('search'),
        ];

        return Arr::where($filters, static fn ($value) => !is_null($value) && $value !== '');
    }
}
