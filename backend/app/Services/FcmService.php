<?php

namespace App\Services;

use App\Models\FcmToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FcmService
{
    private string $projectId;
    private array $serviceAccount;

    public function __construct()
    {
        $serviceAccountPath = storage_path('app/firebase-service-account.json');

        if (!file_exists($serviceAccountPath)) {
            throw new \RuntimeException('Firebase service account JSON not found at: ' . $serviceAccountPath);
        }

        $this->serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
        $this->projectId = $this->serviceAccount['project_id'];
    }

    /**
     * Send push notifications to specific users by their IDs.
     */
    public function sendToUsers(
        array  $userIds,
        string $title,
        string $message,
        ?string $imageUrl = null,
        ?string $actionUrl = null
    ): array {
        $tokens = FcmToken::whereIn('user_id', $userIds)
            ->pluck('token')
            ->unique()
            ->values()
            ->all();

        if (empty($tokens)) {
            return ['success' => 0, 'failure' => 0, 'no_tokens' => true];
        }

        return $this->sendToTokens($tokens, $title, $message, $imageUrl, $actionUrl);
    }

    /**
     * Send push notifications to ALL users who have registered FCM tokens.
     */
    public function sendToAllUsers(
        string  $title,
        string  $message,
        ?string $imageUrl = null,
        ?string $actionUrl = null
    ): array {
        $totalSuccess = 0;
        $totalFailure = 0;
        $invalidTokens = [];

        FcmToken::query()
            ->select('token')
            ->distinct()
            ->orderBy('id')
            ->chunk(500, function ($chunk) use ($title, $message, $imageUrl, $actionUrl, &$totalSuccess, &$totalFailure, &$invalidTokens) {
                $tokens = $chunk->pluck('token')->all();
                $result = $this->sendToTokens($tokens, $title, $message, $imageUrl, $actionUrl);
                $totalSuccess += $result['success'];
                $totalFailure += $result['failure'];
                if (!empty($result['invalid_tokens'])) {
                    $invalidTokens = array_merge($invalidTokens, $result['invalid_tokens']);
                }
            });

        // Clean up invalid tokens
        if (!empty($invalidTokens)) {
            FcmToken::whereIn('token', $invalidTokens)->delete();
        }

        return [
            'success' => $totalSuccess,
            'failure' => $totalFailure,
            'cleaned_tokens' => count($invalidTokens),
        ];
    }

    /**
     * Send push to a list of FCM tokens via FCM HTTP v1 API.
     */
    private function sendToTokens(
        array   $tokens,
        string  $title,
        string  $message,
        ?string $imageUrl = null,
        ?string $actionUrl = null
    ): array {
        if (empty($tokens)) {
            return ['success' => 0, 'failure' => 0, 'invalid_tokens' => []];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error('FCM: Failed to obtain access token');
            return ['success' => 0, 'failure' => count($tokens), 'invalid_tokens' => []];
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
        $successCount = 0;
        $failureCount = 0;
        $invalidTokens = [];

        foreach ($tokens as $token) {
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $message,
                    ],
                    'data' => [
                        'title' => $title,
                        'body' => $message,
                        'click_action' => $actionUrl ?? '/',
                    ],
                    'webpush' => [
                        'notification' => [
                            'title' => $title,
                            'body' => $message,
                            'icon' => '/favicon.ico',
                            'badge' => '/favicon.ico',
                            'data' => [
                                'url' => $actionUrl ?? '/',
                            ],
                        ],
                    ],
                ],
            ];

            if ($imageUrl) {
                $payload['message']['notification']['image'] = $imageUrl;
                $payload['message']['webpush']['notification']['image'] = $imageUrl;
                $payload['message']['data']['image'] = $imageUrl;
            }

            try {
                $response = Http::withToken($accessToken)
                    ->timeout(10)
                    ->post($url, $payload);

                if ($response->successful()) {
                    $successCount++;
                } else {
                    $failureCount++;
                    $body = $response->json();
                    $errorCode = $body['error']['details'][0]['errorCode'] ?? ($body['error']['status'] ?? '');

                    if (in_array($errorCode, ['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT'])) {
                        $invalidTokens[] = $token;
                    }

                    Log::warning('FCM send failure', [
                        'token_prefix' => substr($token, 0, 20) . '...',
                        'status' => $response->status(),
                        'error' => $errorCode,
                    ]);
                }
            } catch (\Throwable $e) {
                $failureCount++;
                Log::warning('FCM send exception', [
                    'token_prefix' => substr($token, 0, 20) . '...',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Clean up invalid tokens
        if (!empty($invalidTokens)) {
            FcmToken::whereIn('token', $invalidTokens)->delete();
        }

        return [
            'success' => $successCount,
            'failure' => $failureCount,
            'invalid_tokens' => $invalidTokens,
        ];
    }

    /**
     * Get a Google OAuth2 access token using the service account credentials.
     * Cached for 50 minutes (tokens are valid for 60 minutes).
     */
    private function getAccessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            try {
                $now = time();
                $headerJson = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
                $claimSetJson = json_encode([
                    'iss' => $this->serviceAccount['client_email'],
                    'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                    'aud' => $this->serviceAccount['token_uri'],
                    'iat' => $now,
                    'exp' => $now + 3600,
                ]);

                if (!is_string($headerJson) || !is_string($claimSetJson)) {
                    Log::error('FCM: Failed to encode JWT payload');
                    return null;
                }

                $signatureInput = $this->base64UrlEncode($headerJson) . '.' . $this->base64UrlEncode($claimSetJson);

                $privateKey = openssl_pkey_get_private($this->serviceAccount['private_key']);
                if (!$privateKey) {
                    Log::error('FCM: Invalid private key in service account');
                    return null;
                }

                $signature = '';
                if (!openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                    Log::error('FCM: Failed to sign JWT');
                    return null;
                }

                $jwt = $signatureInput . '.' . $this->base64UrlEncode($signature);

                $response = Http::asForm()->post($this->serviceAccount['token_uri'], [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

                if ($response->successful()) {
                    return $response->json('access_token');
                }

                Log::error('FCM: Token exchange failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            } catch (\Throwable $e) {
                Log::error('FCM: Access token generation failed', [
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
