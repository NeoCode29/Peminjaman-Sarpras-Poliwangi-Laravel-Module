<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();

        $stats = $this->dashboardService->getCoreStats();

        if (!$user) {
            return view('dashboard', [
                'stats' => $stats,
                'topSarana' => [],
                'yearlyLoans' => null,
                'calendarEvents' => [],
                'quickActions' => [],
            ]);
        }

        $topSarana = $this->dashboardService->getTopSaranaUsageForUser($user, 30);

        // Matrix (grafik tahunan) hanya untuk admin/petugas yang bisa melihat semua peminjaman
        $yearlyLoans = $user->can('peminjaman.view')
            ? $this->dashboardService->getYearlyLoanTotalsForUser($user)
            : null;
        $calendarEvents = $this->dashboardService->getMonthlyCalendarEventsForUser($user);
        $quickActions = $this->dashboardService->getQuickActionsForUser($user);

        return view('dashboard', [
            'stats' => $stats,
            'topSarana' => $topSarana,
            'yearlyLoans' => $yearlyLoans,
            'calendarEvents' => $calendarEvents,
            'quickActions' => $quickActions,
        ]);
    }

    public function calendarEvents(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([], 401);
        }

        $startParam = $request->query('start');
        $endParam = $request->query('end');

        try {
            $start = $startParam ? Carbon::parse($startParam)->startOfDay() : now()->copy()->startOfMonth();
        } catch (\Throwable) {
            $start = now()->copy()->startOfMonth();
        }

        try {
            $end = $endParam ? Carbon::parse($endParam)->endOfDay() : now()->copy()->endOfMonth();
        } catch (\Throwable) {
            $end = now()->copy()->endOfMonth();
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $events = $this->dashboardService->getCalendarEventsForRange($user, $start, $end);

        return response()->json($events);
    }
}
