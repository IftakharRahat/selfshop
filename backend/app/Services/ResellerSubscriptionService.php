<?php

namespace App\Services;

use App\Models\Resellerinvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class ResellerSubscriptionService
{
    private const PAYABLE_INVOICE_STATUSES = ['Unpaid', 'Pending', 'Failed', 'Canceled', 'Cancelled'];

    public static function userFromRequest(Request $request): ?User
    {
        $user = $request->user('sanctum') ?: $request->user();
        if ($user instanceof User) {
            return $user;
        }

        $token = $request->bearerToken();
        if (!$token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        $tokenable = $accessToken?->tokenable;

        return $tokenable instanceof User ? $tokenable : null;
    }

    public static function state(?User $user, bool $persistExpired = true): array
    {
        if (!$user) {
            return [
                'is_active' => false,
                'is_expired' => false,
                'status' => null,
                'membership_status' => null,
                'expire_date' => null,
                'requires_payment' => true,
                'latest_invoice' => null,
            ];
        }

        $isExpired = self::isExpired($user->expire_date);

        if ($persistExpired && $isExpired) {
            $changed = false;
            if ((string) $user->status !== 'Inactive') {
                $user->status = 'Inactive';
                $changed = true;
            }
            if ((string) $user->membership_status !== 'Unpaid') {
                $user->membership_status = 'Unpaid';
                $changed = true;
            }
            if ($changed) {
                $user->save();
                $user->refresh();
            }
        }

        $status = strtolower((string) ($user->status ?? ''));
        $membershipStatus = strtolower((string) ($user->membership_status ?? ''));
        $isActive = !$isExpired && $status === 'active' && $membershipStatus === 'paid';

        return [
            'is_active' => $isActive,
            'is_expired' => $isExpired,
            'status' => $user->status,
            'membership_status' => $user->membership_status,
            'expire_date' => $user->expire_date,
            'requires_payment' => !$isActive,
            'latest_invoice' => self::latestPayableInvoice($user),
        ];
    }

    public static function isActive(?User $user, bool $persistExpired = true): bool
    {
        return (bool) self::state($user, $persistExpired)['is_active'];
    }

    public static function latestPayableInvoice(User $user): ?Resellerinvoice
    {
        return Resellerinvoice::where('user_id', $user->id)
            ->whereIn('status', self::PAYABLE_INVOICE_STATUSES)
            ->latest('id')
            ->first();
    }

    private static function isExpired($expireDate): bool
    {
        if (empty($expireDate)) {
            return false;
        }

        try {
            return Carbon::parse($expireDate)->endOfDay()->lt(Carbon::today());
        } catch (\Throwable $e) {
            return false;
        }
    }
}
