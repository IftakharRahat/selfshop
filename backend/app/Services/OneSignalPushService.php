<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneSignalPushService
{
    /**
     * Send a push to all subscriptions tagged with a panel (user|supplier|admin).
     */
    public function sendToPanel(
        string $panel,
        string $title,
        string $message,
        ?string $url = null,
        array $data = []
    ): void {
        if (!$this->isConfiguredForPanel($panel)) {
            return;
        }

        $payload = $this->buildPayload($title, $message, $url, $data);
        $payload['filters'] = [
            ['field' => 'tag', 'key' => 'panel', 'relation' => '=', 'value' => $panel],
        ];

        $this->dispatch($panel, $payload);
    }

    /**
     * Send a push to one panel-specific external user id.
     */
    public function sendToPanelUser(
        string $panel,
        int|string $userId,
        string $title,
        string $message,
        ?string $url = null,
        array $data = []
    ): void {
        $this->sendToPanelUsers($panel, [(string) $userId], $title, $message, $url, $data);
    }

    /**
     * Send push to panel-specific external ids in chunks.
     */
    public function sendToPanelUsers(
        string $panel,
        array $userIds,
        string $title,
        string $message,
        ?string $url = null,
        array $data = []
    ): void {
        if (!$this->isConfiguredForPanel($panel)) {
            return;
        }

        $normalizedUserIds = collect($userIds)
            ->map(fn($id) => trim((string) $id))
            ->filter()
            ->unique()
            ->values();

        if ($normalizedUserIds->isEmpty()) {
            return;
        }

        foreach ($normalizedUserIds->chunk(200) as $chunk) {
            $chunkUserIds = $chunk->values();
            $externalIds = $chunkUserIds
                ->map(fn($id) => $panel . ':' . $id)
                ->all();

            $payload = $this->buildPayload($title, $message, $url, $data);
            $payload['include_aliases'] = [
                'external_id' => $externalIds,
            ];

            $dispatchResult = $this->dispatch($panel, $payload);
            $recipientCount = $dispatchResult['recipients'] ?? null;
            $shouldFallbackToTags = in_array($dispatchResult['status'], ['failed', 'error'], true)
                || ($dispatchResult['status'] === 'ok' && $recipientCount === 0);

            if (!$shouldFallbackToTags) {
                continue;
            }

            // Fallback for cases where external_id alias mapping is missing/stale.
            // This uses panel + user_id tags that the client sets on login.
            $fallbackPayload = $this->buildPayload($title, $message, $url, $data);
            $fallbackPayload['filters'] = $this->buildPanelUserFilters($panel, $chunkUserIds->all());

            $fallbackResult = $this->dispatch($panel, $fallbackPayload);
            Log::info('OneSignal push fallback via tags executed', [
                'panel' => $panel,
                'initial_status' => $dispatchResult['status'] ?? null,
                'initial_recipients' => $recipientCount,
                'fallback_status' => $fallbackResult['status'] ?? null,
                'fallback_recipients' => $fallbackResult['recipients'] ?? null,
                'target_user_count' => $chunkUserIds->count(),
            ]);
        }
    }

    private function buildPayload(
        string $title,
        string $message,
        ?string $url = null,
        array $data = []
    ): array {
        $notificationData = array_merge([
            'play_sound' => true,
        ], $data);

        $payload = [
            'headings' => ['en' => $title],
            'contents' => ['en' => $message],
            'data' => $notificationData,
            'target_channel' => 'push',
            // Native push channels can honor these defaults.
            'ios_sound' => env('ONESIGNAL_SOUND_FILE', 'default'),
            'android_sound' => env('ONESIGNAL_SOUND_FILE', 'default'),
        ];

        if (!empty($url)) {
            $payload['url'] = $url;
        }

        return $payload;
    }

    private function dispatch(string $panel, array $payload): array
    {
        $credentials = $this->resolveCredentials($panel);
        if (!$this->hasCredentials($credentials)) {
            Log::warning('OneSignal push skipped due to missing panel credentials', [
                'panel' => $credentials['panel'],
            ]);

            return [
                'status' => 'failed',
                'recipients' => null,
            ];
        }

        $requestPayload = array_merge($payload, [
            'app_id' => $credentials['app_id'],
        ]);

        try {
            $request = $this->makeApiRequest($credentials['rest_api_key']);
            $apiBase = rtrim((string) config('onesignal.api_base', 'https://api.onesignal.com'), '/');
            $response = $request->post($apiBase . '/notifications', $requestPayload);
            $responseBody = $response->json();
            $recipientCount = $this->extractRecipientCount(is_array($responseBody) ? $responseBody : null);

            if ($response->failed()) {
                Log::warning('OneSignal push dispatch failed', [
                    'panel' => $credentials['panel'],
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'payload' => $requestPayload,
                ]);

                return [
                    'status' => 'failed',
                    'recipients' => $recipientCount,
                ];
            }

            Log::info('OneSignal push dispatched', [
                'panel' => $credentials['panel'],
                'status' => $response->status(),
                'recipients' => $recipientCount,
                'response_body' => $responseBody,
            ]);

            return [
                'status' => 'ok',
                'recipients' => $recipientCount,
            ];
        } catch (\Throwable $exception) {
            Log::warning('OneSignal push dispatch failed', [
                'panel' => $credentials['panel'],
                'error' => $exception->getMessage(),
                'payload' => $requestPayload,
            ]);

            return [
                'status' => 'error',
                'recipients' => null,
            ];
        }
    }

    private function buildPanelUserFilters(string $panel, array $userIds): array
    {
        $filters = [];

        foreach ($userIds as $index => $userId) {
            if ($index > 0) {
                $filters[] = ['operator' => 'OR'];
            }

            $filters[] = ['field' => 'tag', 'key' => 'panel', 'relation' => '=', 'value' => $panel];
            $filters[] = ['operator' => 'AND'];
            $filters[] = ['field' => 'tag', 'key' => 'user_id', 'relation' => '=', 'value' => trim((string) $userId)];
        }

        return $filters;
    }

    private function extractRecipientCount(?array $responseBody): ?int
    {
        if (!is_array($responseBody)) {
            return null;
        }

        if (isset($responseBody['recipients']) && is_numeric($responseBody['recipients'])) {
            return (int) $responseBody['recipients'];
        }

        if (isset($responseBody['total_recipients']) && is_numeric($responseBody['total_recipients'])) {
            return (int) $responseBody['total_recipients'];
        }

        return null;
    }

    private function makeApiRequest(string $restApiKey): PendingRequest
    {
        $request = Http::withHeaders([
            'Authorization' => 'Key ' . $restApiKey,
            'Accept' => 'application/json',
        ]);

        $timeout = (int) config('onesignal.guzzle_client_timeout', 0);
        if ($timeout > 0) {
            $request = $request->timeout($timeout);
        }

        return $request;
    }

    private function isConfiguredForPanel(string $panel): bool
    {
        return $this->hasCredentials($this->resolveCredentials($panel));
    }

    private function hasCredentials(array $credentials): bool
    {
        return !empty($credentials['app_id']) && !empty($credentials['rest_api_key']);
    }

    private function resolveCredentials(string $panel): array
    {
        $normalizedPanel = strtolower(trim($panel));
        $panelConfig = (array) config('onesignal.panels.' . $normalizedPanel, []);

        return [
            'panel' => $normalizedPanel,
            'app_id' => trim((string) ($panelConfig['app_id'] ?? config('onesignal.app_id', ''))),
            'rest_api_key' => trim((string) ($panelConfig['rest_api_key'] ?? config('onesignal.rest_api_key', ''))),
        ];
    }
}
