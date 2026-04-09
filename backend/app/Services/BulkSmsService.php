<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BulkSmsService
{
    protected string $apiKey;
    protected string $senderId;
    protected string $apiUrl = 'http://bulksmsbd.net/api/smsapi';

    public function __construct()
    {
        $this->apiKey = config('services.bulksms.api_key', env('BULKSMS_API_KEY', ''));
        $this->senderId = config('services.bulksms.sender_id', env('BULKSMS_SENDER_ID', ''));
    }

    /**
     * Send an SMS to a single phone number.
     *
     * @return array{success: bool, response: mixed}
     */
    public function send(string $number, string $message): array
    {
        if (empty($this->apiKey) || empty($this->senderId)) {
            Log::warning('BulkSmsService: API key or sender ID is not configured.');
            return ['success' => false, 'response' => 'SMS credentials not configured'];
        }

        // Normalise BD numbers: strip leading +88 if present
        $number = preg_replace('/^\+?88/', '', trim($number));

        try {
            $response = Http::timeout(15)->get($this->apiUrl, [
                'api_key'  => $this->apiKey,
                'type'     => 'text',
                'number'   => $number,
                'senderid' => $this->senderId,
                'message'  => $message,
            ]);

            $body = $response->json() ?? $response->body();

            Log::info('BulkSmsService: SMS sent', [
                'number'   => $number,
                'status'   => $response->status(),
                'response' => $body,
            ]);

            return ['success' => $response->successful(), 'response' => $body];
        } catch (\Throwable $e) {
            Log::error('BulkSmsService: Failed to send SMS', [
                'number' => $number,
                'error'  => $e->getMessage(),
            ]);

            return ['success' => false, 'response' => $e->getMessage()];
        }
    }
}
