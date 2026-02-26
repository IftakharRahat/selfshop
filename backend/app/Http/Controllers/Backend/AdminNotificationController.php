<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\AdminBroadcastNotification;
use App\Services\OneSignalPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class AdminNotificationController extends Controller
{
    public function __construct(
        protected OneSignalPushService $oneSignalPushService
    ) {}

    /**
     * Notification compose page.
     */
    public function index()
    {
        return view('backend.content.notifications.index', [
            'totalUsers' => User::query()->whereDoesntHave('vendor')->count(),
            'totalSuppliers' => Vendor::count(),
        ]);
    }

    /**
     * Select2 AJAX source: users.
     */
    public function searchUsers(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        $ids = collect((array) $request->input('ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $usersQuery = User::query()
            ->select('id', 'name', 'email', 'phone')
            ->whereDoesntHave('vendor')
            ->when($ids->isNotEmpty(), function ($query) use ($ids) {
                $query->whereIn('id', $ids);
            }, function ($query) use ($term) {
                if ($term !== '') {
                    $query->where(function ($sub) use ($term) {
                        $sub->where('name', 'like', '%' . $term . '%')
                            ->orWhere('email', 'like', '%' . $term . '%')
                            ->orWhere('phone', 'like', '%' . $term . '%');
                    });
                }
            })
            ->orderByDesc('id');

        if ($ids->isEmpty()) {
            $usersQuery->limit(20);
        }

        $users = $usersQuery->get();

        $results = $users->map(function ($user) {
            $email = $user->email ? ' | ' . $user->email : '';
            $phone = $user->phone ? ' | ' . $user->phone : '';

            return [
                'id' => $user->id,
                'text' => '#' . $user->id . ' ' . ($user->name ?? 'User') . $email . $phone,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Select2 AJAX source: suppliers.
     */
    public function searchSuppliers(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        $ids = collect((array) $request->input('ids', []))
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $suppliersQuery = Vendor::query()
            ->leftJoin('users', 'users.id', '=', 'vendors.user_id')
            ->select(
                'vendors.id',
                'vendors.company_name',
                'vendors.contact_name',
                'vendors.contact_email',
                'users.email as user_email'
            )
            ->when($ids->isNotEmpty(), function ($query) use ($ids) {
                $query->whereIn('vendors.id', $ids);
            }, function ($query) use ($term) {
                if ($term !== '') {
                    $query->where(function ($sub) use ($term) {
                        $sub->where('vendors.company_name', 'like', '%' . $term . '%')
                            ->orWhere('vendors.contact_name', 'like', '%' . $term . '%')
                            ->orWhere('vendors.contact_email', 'like', '%' . $term . '%')
                            ->orWhere('users.email', 'like', '%' . $term . '%');
                    });
                }
            })
            ->orderByDesc('vendors.id');

        if ($ids->isEmpty()) {
            $suppliersQuery->limit(20);
        }

        $suppliers = $suppliersQuery->get();

        $results = $suppliers->map(function ($supplier) {
            $contact = $supplier->contact_name ?: ($supplier->contact_email ?: $supplier->user_email);
            $contactText = $contact ? ' | ' . $contact : '';

            return [
                'id' => $supplier->id,
                'text' => '#' . $supplier->id . ' ' . ($supplier->company_name ?? 'Supplier') . $contactText,
            ];
        })->values();

        return response()->json(['results' => $results]);
    }

    /**
     * Send notification from admin.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:2000'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'link' => ['nullable', 'string', 'max:2048'],
            'target_type' => ['required', 'in:1,2,3'],
            'user_ids' => ['required_if:target_type,2', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'supplier_ids' => ['required_if:target_type,3', 'array', 'min:1'],
            'supplier_ids.*' => ['integer', 'exists:vendors,id'],
        ]);

        // Ensure the notifications table exists before attempting to write
        if (!Schema::hasTable('notifications')) {
            Log::error('AdminNotificationController@send: notifications table does not exist. Run: php artisan migrate');

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Notifications table not found. Please run database migrations first.');
        }

        try {
            $admin = Auth::guard('admin')->user();
            $targetType = (string) $validated['target_type'];
            $audienceType = $this->mapTargetType($targetType);

            $meta = [
                'admin_id' => $admin?->id,
                'admin_name' => $admin?->name,
                'target_type' => $audienceType,
            ];

            $pushData = array_filter([
                'type' => 'admin_broadcast',
                'audience_type' => $audienceType,
                'image_url' => $validated['image_url'] ?? null,
                'action_url' => $validated['link'] ?? null,
                'meta' => $meta,
            ], fn($value) => $value !== null);

            $sendNotificationBatch = function ($users) use ($validated, $audienceType, $meta) {
                if ($users->isEmpty()) {
                    return;
                }

                Notification::send($users, new AdminBroadcastNotification(
                    $validated['title'],
                    $validated['message'],
                    $validated['image_url'] ?? null,
                    $validated['link'] ?? null,
                    $audienceType,
                    $meta
                ));
            };

            $sentCount = 0;

            if ($targetType === '1') {
                $sentCount = User::query()->whereDoesntHave('vendor')->count();
                User::query()
                    ->whereDoesntHave('vendor')
                    ->select('id')
                    ->orderBy('id')
                    ->chunkById(300, function ($users) use ($sendNotificationBatch) {
                        $sendNotificationBatch($users);
                    });

                $this->oneSignalPushService->sendToPanel(
                    'user',
                    $validated['title'],
                    $validated['message'],
                    $validated['link'] ?? null,
                    $pushData
                );
            } elseif ($targetType === '2') {
                $recipientUserIds = collect($validated['user_ids'] ?? [])
                    ->map(fn($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();

                $sentCount = User::query()
                    ->whereDoesntHave('vendor')
                    ->whereIn('id', $recipientUserIds)
                    ->count();

                if ($sentCount > 0) {
                    User::query()
                        ->whereDoesntHave('vendor')
                        ->whereIn('id', $recipientUserIds)
                        ->select('id')
                        ->orderBy('id')
                        ->chunkById(300, function ($users) use ($sendNotificationBatch) {
                            $sendNotificationBatch($users);
                        });

                    $this->oneSignalPushService->sendToPanelUsers(
                        'user',
                        $recipientUserIds->all(),
                        $validated['title'],
                        $validated['message'],
                        $validated['link'] ?? null,
                        $pushData
                    );
                }
            } else {
                $supplierIds = collect($validated['supplier_ids'] ?? [])
                    ->map(fn($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();

                $recipientUserIds = Vendor::query()
                    ->whereIn('id', $supplierIds)
                    ->whereNotNull('user_id')
                    ->pluck('user_id')
                    ->map(fn($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();

                $sentCount = $recipientUserIds->count();

                if ($sentCount > 0) {
                    User::query()
                        ->whereIn('id', $recipientUserIds)
                        ->select('id')
                        ->orderBy('id')
                        ->chunkById(300, function ($users) use ($sendNotificationBatch) {
                            $sendNotificationBatch($users);
                        });

                    $this->oneSignalPushService->sendToPanelUsers(
                        'supplier',
                        $recipientUserIds->all(),
                        $validated['title'],
                        $validated['message'],
                        $validated['link'] ?? null,
                        $pushData
                    );
                }
            }

            if ($sentCount < 1) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'No valid recipients found for this notification.');
            }

            return redirect()
                ->route('admin.notifications.index')
                ->with('message', 'Notification sent successfully to ' . number_format($sentCount) . ' recipient(s).');

        } catch (\Throwable $e) {
            Log::error('AdminNotificationController@send failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to send notification: ' . $e->getMessage());
        }
    }

    private function mapTargetType(string $targetType): string
    {
        return match ($targetType) {
            '1' => 'all_user',
            '2' => 'user',
            '3' => 'supplier',
            default => 'all_user',
        };
    }
}
