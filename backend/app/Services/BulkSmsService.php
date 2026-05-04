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
     * @return array{success: bool, status: int|null, response: mixed, number?: string}
     */
    public function send(string $number, string $message): array
    {
        if (empty($this->apiKey) || empty($this->senderId)) {
            Log::warning('BulkSmsService: API key or sender ID is not configured.');
            return ['success' => false, 'status' => null, 'response' => 'SMS credentials not configured'];
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
            $success = $response->successful() && $this->providerResponseLooksSuccessful($body);

            Log::info('BulkSmsService: SMS API response received', [
                'number'   => $number,
                'status'   => $response->status(),
                'success'  => $success,
                'response' => $body,
            ]);

            return [
                'success' => $success,
                'status' => $response->status(),
                'response' => $body,
                'number' => $number,
            ];
        } catch (\Throwable $e) {
            Log::error('BulkSmsService: Failed to send SMS', [
                'number' => $number,
                'error'  => $e->getMessage(),
            ]);

            return ['success' => false, 'status' => null, 'response' => $e->getMessage(), 'number' => $number];
        }
    }

    protected function providerResponseLooksSuccessful($body): bool
    {
        if (is_array($body)) {
            if (!empty($body['error_message']) || !empty($body['error'])) {
                return false;
            }

            if (isset($body['response_code'])) {
                return str_starts_with((string) $body['response_code'], '2');
            }

            if (isset($body['success'])) {
                return (bool) $body['success'];
            }

            return true;
        }

        $text = strtolower((string) $body);

        if (str_contains($text, 'error') || str_contains($text, 'invalid') || str_contains($text, 'failed') || str_contains($text, 'insufficient')) {
            return false;
        }

        return trim($text) !== '';
    }
}
