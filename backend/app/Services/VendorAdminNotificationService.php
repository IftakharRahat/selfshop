<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vendor;
use App\Notifications\VendorPanelNotification;
use Illuminate\Support\Facades\Auth;

class VendorAdminNotificationService
{
    public function __construct(
        protected OneSignalPushService $oneSignalPushService
    ) {}

    public function notifyVendor(
        Vendor $vendor,
        string $title,
        string $message,
        string $type = 'info',
        array $meta = [],
        ?string $actionUrl = null
    ): void {
        $vendor->loadMissing('user');
        if (!$vendor->user) {
            return;
        }

        $vendor->user->notify(new VendorPanelNotification(
            $title,
            $message,
            $type,
            $this->enrichMeta($meta),
            $actionUrl
        ));

        $this->oneSignalPushService->sendToPanelUser(
            'supplier',
            (int) $vendor->user->id,
            $title,
            $message,
            $actionUrl,
            [
                'type' => $type,
                'audience_type' => 'supplier',
                'meta' => $this->enrichMeta($meta),
                'action_url' => $actionUrl,
            ]
        );
    }

    public function notifyVendorById(
        int $vendorId,
        string $title,
        string $message,
        string $type = 'info',
        array $meta = [],
        ?string $actionUrl = null
    ): void {
        $vendor = Vendor::with('user')->find($vendorId);
        if (!$vendor || !$vendor->user) {
            return;
        }

        $vendor->user->notify(new VendorPanelNotification(
            $title,
            $message,
            $type,
            $this->enrichMeta($meta),
            $actionUrl
        ));

        $this->oneSignalPushService->sendToPanelUser(
            'supplier',
            (int) $vendor->user->id,
            $title,
            $message,
            $actionUrl,
            [
                'type' => $type,
                'audience_type' => 'supplier',
                'meta' => $this->enrichMeta($meta),
                'action_url' => $actionUrl,
            ]
        );
    }

    public function notifyAllVendors(
        string $title,
        string $message,
        string $type = 'info',
        array $meta = [],
        ?string $actionUrl = null
    ): void {
        $users = User::whereHas('vendor')->get();
        if ($users->isEmpty()) {
            return;
        }

        foreach ($users as $user) {
            $user->notify(new VendorPanelNotification(
                $title,
                $message,
                $type,
                $this->enrichMeta($meta),
                $actionUrl
            ));
        }

        $this->oneSignalPushService->sendToPanelUsers(
            'supplier',
            $users->pluck('id')->all(),
            $title,
            $message,
            $actionUrl,
            [
                'type' => $type,
                'audience_type' => 'supplier',
                'meta' => $this->enrichMeta($meta),
                'action_url' => $actionUrl,
            ]
        );
    }

    private function enrichMeta(array $meta): array
    {
        $admin = Auth::guard('admin')->user();

        return array_merge([
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->name,
        ], $meta);
    }
}
