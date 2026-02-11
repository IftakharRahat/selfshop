<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorKycDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VendorAccountController extends Controller
{
    /**
     * Get the authenticated vendor profile + basic KYC summary.
     * GET /api/vendor/profile
     */
    public function profile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $vendor = Vendor::with(['kycDocuments', 'warehouses', 'payoutAccounts'])
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'status' => true,
            'data' => [
                'user' => $user,
                'vendor' => $vendor,
            ],
        ]);
    }

    /**
     * Create or update vendor profile (company, branding, address, contacts).
     * POST /api/vendor/profile
     */
    public function upsertProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'company_name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('vendors', 'slug')->ignore(optional($user->vendor)->id),
            ],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'country' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'pickup_location_label' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Default slug if not provided
        if (empty($data['slug'])) {
            $base = str($data['company_name'])->slug('-');
            $slug = $base;
            $i = 1;
            while (
                Vendor::where('slug', $slug)
                    ->where('id', '!=', optional($user->vendor)->id)
                    ->exists()
            ) {
                $slug = $base . '-' . $i++;
            }
            $data['slug'] = $slug;
        }

        $vendor = Vendor::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return response()->json([
            'status' => true,
            'message' => 'Vendor profile saved',
            'data' => [
                'vendor' => $vendor,
            ],
        ]);
    }

    /**
     * List KYC documents for the vendor.
     * GET /api/vendor/kyc-documents
     */
    public function kycDocuments()
    {
        /** @var User $user */
        $user = Auth::user();
        $vendor = $user->vendor;

        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor profile not found',
            ], 404);
        }

        $documents = $vendor->kycDocuments()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'documents' => $documents,
            ],
        ]);
    }

    /**
     * Submit a KYC document (metadata only for now – file upload can be added later).
     * POST /api/vendor/kyc-documents
     */
    public function storeKycDocument(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $vendor = $user->vendor;

        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor profile not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'document_type' => ['required', 'string', 'max:100'], // e.g. nid, trade_license
            'document_number' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $document = VendorKycDocument::create([
            'vendor_id' => $vendor->id,
            'document_type' => $data['document_type'],
            'document_number' => $data['document_number'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'KYC document submitted for review',
            'data' => [
                'document' => $document,
            ],
        ], 201);
    }
}

