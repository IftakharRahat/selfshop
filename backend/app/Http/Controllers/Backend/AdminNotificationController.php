<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\AdminBroadcastNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminNotificationController extends Controller
{
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
        try {
            Log::info('AdminNotificationController@send start', [
                'target_type' => $request->input('target_type'),
            ]);

            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }

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

            $admin = Auth::guard('admin')->user();
            $targetType = (string) $validated['target_type'];
            $audienceType = $this->mapTargetType($targetType);

            $meta = [
                'admin_id' => $admin?->id,
                'admin_name' => $admin?->name,
                'target_type' => $audienceType,
            ];
            $startedAt = microtime(true);

            $sendNotificationBatch = function ($users) use ($validated, $audienceType, $meta) {
                if ($users->isEmpty()) {
                    return 0;
                }

                return $this->storeDatabaseNotificationsForUsers(
                    $users->pluck('id')->all(),
                    $validated['title'],
                    $validated['message'],
                    $validated['image_url'] ?? null,
                    $validated['link'] ?? null,
                    $audienceType,
                    $meta
                );
            };

            $sentCount = 0;

            if ($targetType === '1') {
                $title = $validated['title'];
                $message = $validated['message'];
                $imageUrl = $validated['image_url'] ?? null;
                $link = $validated['link'] ?? null;

                app()->terminating(function () use ($title, $message, $imageUrl, $link, $audienceType, $meta, $startedAt) {
                    try {
                        $asyncSentCount = $this->storeDatabaseNotificationsForAllUsers(
                            $title,
                            $message,
                            $imageUrl,
                            $link,
                            $audienceType,
                            $meta
                        );

                        Log::info('Admin notification sent', [
                            'target_type' => $audienceType,
                            'sent_count' => $asyncSentCount,
                            'mode' => 'after_response',
                            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                        ]);
                    } catch (\Throwable $exception) {
                        Log::error('AdminNotificationController@send async all-user failed', [
                            'error' => $exception->getMessage(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                        ]);
                    }
                });

                return redirect()
                    ->route('admin.notifications.index')
                    ->with('message', 'All-user notification queued and is being processed in background.');

            } elseif ($targetType === '2') {
                $recipientUserIds = collect($validated['user_ids'] ?? [])
                    ->map(fn($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();

                User::query()
                    ->whereDoesntHave('vendor')
                    ->whereIn('id', $recipientUserIds)
                    ->select('id')
                    ->orderBy('id')
                    ->chunkById(300, function ($users) use ($sendNotificationBatch, &$sentCount) {
                        $sentCount += $sendNotificationBatch($users);
                    });

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

                if ($recipientUserIds->isNotEmpty()) {
                    User::query()
                        ->whereIn('id', $recipientUserIds)
                        ->select('id')
                        ->orderBy('id')
                        ->chunkById(300, function ($users) use ($sendNotificationBatch, &$sentCount) {
                            $sentCount += $sendNotificationBatch($users);
                        });
                }

            }

            if ($sentCount < 1) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'No valid recipients found for this notification.');
            }

            Log::info('Admin notification sent', [
                'target_type' => $audienceType,
                'sent_count' => $sentCount,
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ]);

            return redirect()
                ->route('admin.notifications.index')
                ->with('message', 'Notification sent successfully to ' . number_format($sentCount) . ' recipient(s).');
        } catch (ValidationException $e) {
            throw $e;
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

    private function storeDatabaseNotificationsForUsers(
        array $userIds,
        string $title,
        string $message,
        ?string $imageUrl = null,
        ?string $actionUrl = null,
        string $audienceType = 'all_user',
        array $meta = []
    ): int {
        $normalizedUserIds = collect($userIds)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($normalizedUserIds->isEmpty()) {
            return 0;
        }

        $timestamp = now();
        $payload = [
            'title' => $title,
            'message' => $message,
            'type' => 'admin_broadcast',
            'image_url' => $imageUrl,
            'action_url' => $actionUrl,
            'link' => $actionUrl,
            'audience_type' => $audienceType,
            'meta' => $meta,
            'created_at' => $timestamp->toIso8601String(),
        ];

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($encodedPayload)) {
            throw new \RuntimeException('Failed to encode notification payload.');
        }

        $rows = $normalizedUserIds->map(function (int $userId) use ($encodedPayload, $timestamp) {
            return [
                'id' => (string) Str::uuid(),
                'type' => AdminBroadcastNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $userId,
                'data' => $encodedPayload,
                'read_at' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        })->all();

        DB::table('notifications')->insert($rows);

        return $normalizedUserIds->count();
    }

    private function storeDatabaseNotificationsForAllUsers(
        string $title,
        string $message,
        ?string $imageUrl = null,
        ?string $actionUrl = null,
        string $audienceType = 'all_user',
        array $meta = []
    ): int {
        $timestamp = now();
        $payload = [
            'title' => $title,
            'message' => $message,
            'type' => 'admin_broadcast',
            'image_url' => $imageUrl,
            'action_url' => $actionUrl,
            'link' => $actionUrl,
            'audience_type' => $audienceType,
            'meta' => $meta,
            'created_at' => $timestamp->toIso8601String(),
        ];

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!is_string($encodedPayload)) {
            throw new \RuntimeException('Failed to encode notification payload.');
        }

        $insertSql = <<<'SQL'
INSERT INTO notifications (id, type, notifiable_type, notifiable_id, data, read_at, created_at, updated_at)
SELECT UUID(), ?, ?, users.id, ?, NULL, ?, ?
FROM users
LEFT JOIN vendors ON vendors.user_id = users.id
WHERE vendors.id IS NULL
SQL;

        try {
            return (int) DB::affectingStatement($insertSql, [
                AdminBroadcastNotification::class,
                User::class,
                $encodedPayload,
                $timestamp,
                $timestamp,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('All-user bulk insert-select failed, falling back to chunked inserts', [
                'error' => $exception->getMessage(),
            ]);
        }

        $sentCount = 0;
        User::query()
            ->whereDoesntHave('vendor')
            ->select('id')
            ->orderBy('id')
            ->chunkById(1000, function ($users) use (&$sentCount, $title, $message, $imageUrl, $actionUrl, $audienceType, $meta) {
                $sentCount += $this->storeDatabaseNotificationsForUsers(
                    $users->pluck('id')->all(),
                    $title,
                    $message,
                    $imageUrl,
                    $actionUrl,
                    $audienceType,
                    $meta
                );
            });

        return $sentCount;
    }

}
