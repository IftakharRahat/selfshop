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
        $this->apiUrl = config('services.bulksms.api_url', env('BULKSMS_API_URL', $this->apiUrl));
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
            $safeResponse = $this->summarizeProviderResponse($body, $number);

            Log::info('BulkSmsService: SMS API response received', [
                'number'   => $this->maskPhone($number),
                'status'   => $response->status(),
                'success'  => $success,
                'response' => $safeResponse,
            ]);

            return [
                'success' => $success,
                'status' => $response->status(),
                'response' => $safeResponse,
                'number' => $number,
            ];
        } catch (\Throwable $e) {
            Log::error('BulkSmsService: Failed to send SMS', [
                'number' => $this->maskPhone($number),
                'error'  => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => null,
                'response' => $this->summarizeProviderResponse($e->getMessage(), $number),
                'number' => $number,
            ];
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

    protected function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) <= 4) {
            return '****';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }

    protected function summarizeProviderResponse($body, string $number): string
    {
        $text = is_scalar($body) || $body === null
            ? (string) $body
            : json_encode($body);

        $text = (string) $text;

        foreach ([
            $this->apiKey => '[redacted_api_key]',
            $this->senderId => '[redacted_sender_id]',
            $number => '[redacted_phone]',
        ] as $secret => $replacement) {
            if ($secret !== '') {
                $text = str_replace($secret, $replacement, $text);
            }
        }

        $text = preg_replace('/((?:api_key|apikey|senderid|sender_id|number|phone|message)=)[^&\s]+/i', '$1[redacted]', $text) ?? $text;
        $text = preg_replace('/("(?:api_key|apikey|senderid|sender_id|number|phone|message)"\s*:\s*)"[^"]*"/i', '$1"[redacted]"', $text) ?? $text;
        $text = preg_replace('/\b(?:\+?88)?01[3-9]\d{8}\b/', '[redacted_phone]', $text) ?? $text;

        return substr($text, 0, 1000);
    }
}
