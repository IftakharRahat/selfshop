<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VendorAuthController extends Controller
{
    /**
     * Vendor self-registration.
     * POST /api/vendor/register
     *
     * Creates a user + pending vendor profile. Admin must approve later.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:6'],
            'company_name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
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
                'email' => $data['email'],
                'phone' => $data['email'],
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
                'business_type' => $data['business_type'] ?? null,
                'country' => $data['country'] ?? null,
                'city' => $data['city'] ?? null,
                'status' => 'pending',
                'slug' => $slug,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Vendor registration submitted. An admin will review and approve your account.',
                'data' => [
                    'user_id' => $user->id,
                    'vendor_id' => $vendor->id,
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

