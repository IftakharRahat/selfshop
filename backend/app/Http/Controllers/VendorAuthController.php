<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use App\Services\CarryBeeService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VendorAuthController extends Controller
{
    /**
     * Vendor self-registration.
     * POST /api/vendor/register
     *
     * Creates a user + pending vendor profile. Admin must approve later.
     * Also creates a pickup store in Carry Bee if pickup point fields are supplied.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'size:11', 'regex:/^01[3-9]\d{8}$/', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'company_name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:255'],
            // Carry Bee pickup point fields
            'pickup_city_id' => ['nullable', 'integer'],
            'pickup_zone_id' => ['nullable', 'integer'],
            'pickup_area_id' => ['nullable', 'integer'],
            'pickup_address' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        try {
            // Users table requires phone, my_referral_code, refer_by (non-null)
            $code = strtoupper(substr(preg_replace('/\s+/', '', $data['name']), 0, 3)) . $this->vendorUniqueId();
            $referBy = User::whereNotNull('my_referral_code')->value('my_referral_code') ?? 'SELFVENDOR';

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['phone'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'status' => 'Inactive',
                'is_verified_wholesaler' => false,
                'my_referral_code' => $code,
                'refer_by' => $referBy,
            ]);

            // Generate vendor slug
            $baseSlug = \Illuminate\Support\Str::slug($data['company_name'], '-');
            $slug = $baseSlug;
            $i = 1;
            while (Vendor::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $i++;
            }

            $vendor = Vendor::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'contact_phone' => $data['phone'],
                'business_type' => $data['business_type'] ?? null,
                'status' => 'pending',
                'slug' => $slug,
                'pickup_city_id' => $data['pickup_city_id'] ?? null,
                'pickup_zone_id' => $data['pickup_zone_id'] ?? null,
                'pickup_area_id' => $data['pickup_area_id'] ?? null,
                'pickup_address' => $data['pickup_address'] ?? null,
            ]);

            // Create a pickup store in Carry Bee
            $carrybeeStoreId = null;

            // DEBUG: Log what we received
            Log::info('CarryBee registration debug', [
                'vendor_id' => $vendor->id,
                'pickup_city_id' => $data['pickup_city_id'] ?? 'NOT SET',
                'pickup_zone_id' => $data['pickup_zone_id'] ?? 'NOT SET',
                'pickup_area_id' => $data['pickup_area_id'] ?? 'NOT SET',
                'city_empty' => empty($data['pickup_city_id']),
                'zone_empty' => empty($data['pickup_zone_id']),
                'area_empty' => empty($data['pickup_area_id']),
                'condition_result' => !empty($data['pickup_city_id']) && !empty($data['pickup_zone_id']) && !empty($data['pickup_area_id']),
            ]);

            if (!empty($data['pickup_city_id']) && !empty($data['pickup_zone_id']) && !empty($data['pickup_area_id'])) {
                try {
                    $carryBee = app(CarryBeeService::class);
                    $contactPhone = $data['phone'];
                    $storeResult = $carryBee->createStore([
                        'name' => $data['company_name'],
                        'contact_person_name' => $data['name'],
                        'contact_person_number' => $contactPhone,
                        'address' => $data['pickup_address'] ?? $data['company_name'],
                        'city_id' => (int) $data['pickup_city_id'],
                        'zone_id' => (int) $data['pickup_zone_id'],
                        'area_id' => (int) $data['pickup_area_id'],
                    ]);

                    Log::info('CarryBee store creation raw result', [
                        'vendor_id' => $vendor->id,
                        'storeResult' => $storeResult,
                        'data_id' => $storeResult['data']['id'] ?? 'NOT FOUND',
                    ]);

                    if (!empty($storeResult['data']['id'])) {
                        $carrybeeStoreId = $storeResult['data']['id'];
                        $vendor->update(['carrybee_store_id' => $carrybeeStoreId]);
                        Log::info('CarryBee store ID saved', ['vendor_id' => $vendor->id, 'store_id' => $carrybeeStoreId]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('CarryBee store creation failed (non-blocking)', [
                        'vendor_id' => $vendor->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            } else {
                Log::warning('CarryBee store creation SKIPPED - missing pickup fields', [
                    'vendor_id' => $vendor->id,
                ]);
            }

            // Notify admin(s) about new pending vendor
            try {
                $pushService = app(PushNotificationService::class);
                $pushService->notifyAdmins(
                    '🏪 New Supplier Registration',
                    "{$data['company_name']} ({$data['name']}) has registered and is pending approval.",
                    'info',
                    ['event' => 'vendor_registered', 'vendor_id' => $vendor->id]
                );
            } catch (\Throwable $e) {
                Log::warning('Vendor registration push notification failed (non-blocking)', [
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Vendor registration submitted. An admin will review and approve your account.',
                'data' => [
                    'user_id' => $user->id,
                    'vendor_id' => $vendor->id,
                    'carrybee_store_id' => $carrybeeStoreId,
                ],
            ], 201);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Vendor registration failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Registration failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function vendorUniqueId(): string
    {
        $last = User::latest('id')->first();
        return 'SS00' . ($last ? $last->id + 1 : 1);
    }
}
