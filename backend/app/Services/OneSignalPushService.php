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

        $externalIds = collect($userIds)
            ->map(fn($id) => trim((string) $id))
            ->filter()
            ->map(fn($id) => $panel . ':' . $id)
            ->unique()
            ->values();

        if ($externalIds->isEmpty()) {
            return;
        }

        foreach ($externalIds->chunk(200) as $chunk) {
            $payload = $this->buildPayload($title, $message, $url, $data);
            $payload['include_aliases'] = [
                'external_id' => $chunk->all(),
            ];
            $this->dispatch($panel, $payload);
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

    private function dispatch(string $panel, array $payload): void
    {
        $credentials = $this->resolveCredentials($panel);
        if (!$this->hasCredentials($credentials)) {
            Log::warning('OneSignal push skipped due to missing panel credentials', [
                'panel' => $credentials['panel'],
            ]);

            return;
        }

        $requestPayload = array_merge($payload, [
            'app_id' => $credentials['app_id'],
        ]);

        try {
            $request = $this->makeApiRequest($credentials['rest_api_key']);
            $apiBase = rtrim((string) config('onesignal.api_base', 'https://api.onesignal.com'), '/');
            $response = $request->post($apiBase . '/notifications', $requestPayload);

            if ($response->failed()) {
                Log::warning('OneSignal push dispatch failed', [
                    'panel' => $credentials['panel'],
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'payload' => $requestPayload,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('OneSignal push dispatch failed', [
                'panel' => $credentials['panel'],
                'error' => $exception->getMessage(),
                'payload' => $requestPayload,
            ]);
        }
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
