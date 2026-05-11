<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FcmTokenController extends Controller
{
    /**
     * Register (or refresh) an FCM token for the authenticated user.
     */
    public function store(Request $request)
    {
        // If migration wasn't run yet on production, skip hard failure.
        if (!Schema::hasTable('fcm_tokens')) {
            Log::warning('FCM token table missing; skipping token registration');
            return response()->json([
                'status' => false,
                'message' => 'FCM token storage is not ready on server',
            ], 200);
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'device_info' => ['nullable', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        try {
            // Upsert: if token already exists for this user, update timestamps.
            // If token exists for a different user, reassign it.
            FcmToken::updateOrCreate(
                ['token' => $validated['token']],
                [
                    'user_id' => $user->id,
                    'device_info' => $validated['device_info'] ?? $request->header('User-Agent'),
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'FCM token registered successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('FCM token registration failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to register FCM token',
            ], 500);
        }
    }

    /**
     * Remove an FCM token (e.g. on logout or permission revocation).
     */
    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:500'],
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        FcmToken::where('token', $validated['token'])
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'status' => true,
            'message' => 'FCM token removed successfully',
        ]);
    }
}
