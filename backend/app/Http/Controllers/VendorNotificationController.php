<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorNotificationController extends Controller
{
    private function getVendorUser()
    {
        $user = Auth::user();
        if (!$user || !$user->vendor) {
            return null;
        }

        return $user;
    }

    /**
     * GET /api/vendor/notifications
     */
    public function index(Request $request)
    {
        $user = $this->getVendorUser();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $perPage = min(max((int) $request->input('per_page', 20), 5), 100);
        $unreadOnly = $request->boolean('unread_only', false);

        $query = $user->notifications()
            ->whereJsonContains('data->meta->audience', 'supplier')
            ->orderByDesc('created_at');
        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $items = $query->paginate($perPage);

        $notifications = collect($items->items())->map(function ($notification) {
            $data = is_array($notification->data) ? $notification->data : [];

            return [
                'id' => $notification->id,
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? '',
                'type' => $data['type'] ?? 'info',
                'action_url' => $data['action_url'] ?? null,
                'meta' => $data['meta'] ?? [],
                'is_read' => $notification->read_at !== null,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'status' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $user->unreadNotifications()
                    ->whereJsonContains('data->meta->audience', 'supplier')
                    ->count(),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                ],
            ],
        ]);
    }

    /**
     * POST /api/vendor/notifications/{id}/read
     */
    public function markRead(string $id)
    {
        $user = $this->getVendorUser();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $notification = $user->notifications()->where('id', $id)->first();
        if (!$notification) {
            return response()->json(['status' => false, 'message' => 'Notification not found'], 404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => true,
            'message' => 'Notification marked as read',
            'data' => [
                'unread_count' => $user->fresh()->unreadNotifications()->count(),
            ],
        ]);
    }

    /**
     * POST /api/vendor/notifications/read-all
     */
    public function markAllRead()
    {
        $user = $this->getVendorUser();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'All notifications marked as read',
            'data' => [
                'unread_count' => 0,
            ],
        ]);
    }
}
