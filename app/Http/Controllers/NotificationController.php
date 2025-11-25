<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\NotificationRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService,
        protected NotificationRepositoryInterface $notificationRepository
    ) {}

    /**
     * Display notification inbox
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['read', 'category', 'priority', 'from', 'to', 'search']);

        $notifications = $this->notificationRepository->getUserNotifications(
            auth()->user(),
            $filters,
            $request->get('per_page', 15)
        );

        $stats = $this->notificationRepository->getStatistics(auth()->user());
        $categories = $this->notificationRepository->getCategories();

        return view('notifications.index', compact(
            'notifications',
            'stats',
            'filters',
            'categories'
        ));
    }

    /**
     * Get recent unread notifications for header dropdown (AJAX)
     */
    public function recent(): JsonResponse
    {
        $notifications = $this->notificationRepository->getRecentUnread(auth()->user());
        $count = $this->notificationRepository->getUnreadCount(auth()->user());

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'count' => $count,
        ]);
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(Request $request, string $id)
    {
        $success = $this->notificationService->markAsRead(auth()->user(), $id);

        // If AJAX request, return JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $success,
                'message' => $success
                    ? 'Notifikasi ditandai sebagai dibaca'
                    : 'Notifikasi tidak ditemukan',
            ]);
        }

        // If regular form request, redirect with flash message
        if ($success) {
            return redirect()->route('notifications.index')
                ->with('success', 'Notifikasi berhasil ditandai sebagai dibaca');
        }

        return redirect()->route('notifications.index')
            ->with('error', 'Notifikasi tidak ditemukan');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request)
    {
        $count = $this->notificationService->markAllAsRead(auth()->user());

        // If AJAX request, return JSON
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$count} notifikasi ditandai sebagai dibaca",
                'count' => $count,
            ]);
        }

        // If regular form request, redirect with flash message
        return redirect()->route('notifications.index')
            ->with('success', "{$count} notifikasi berhasil ditandai sebagai dibaca");
    }

    /**
     * Get unread count (for polling)
     */
    public function count(): JsonResponse
    {
        $count = $this->notificationRepository->getUnreadCount(auth()->user());

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(string $id): JsonResponse
    {
        $success = $this->notificationService->delete(auth()->user(), $id);

        return response()->json([
            'success' => $success,
            'message' => $success
                ? 'Notifikasi dihapus'
                : 'Notifikasi tidak ditemukan',
        ]);
    }
}
