<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SteadfastOrderStatusService
{
    private const STATUS_SYNC_COOLDOWN_SECONDS = 120;
    private static ?bool $hasStatusColumns = null;

    public function createConsignment(Order $order, string $recipientName, string $recipientAddress, string $recipientPhone): array
    {
        if ($this->isDryRun()) {
            $trackingCode = 'TEST-' . strtoupper(Str::random(10));

            return [
                'ok' => true,
                'tracking_code' => $trackingCode,
                'raw_status' => 'in_review',
                'consignment_id' => 'dry-run-' . $order->id,
                'payload' => [
                    'mode' => 'dry_run',
                    'message' => 'Steadfast create_order skipped (STEADFAST_DRY_RUN enabled).',
                    'invoice' => (string) $order->invoiceID,
                    'tracking_code' => $trackingCode,
                ],
            ];
        }

        $headers = $this->buildHeaders();
        if ($headers === null) {
            return [
                'ok' => false,
                'message' => 'Steadfast credentials are missing in .env',
            ];
        }

        try {
            $response = Http::withHeaders($headers)->post(
                'https://portal.packzy.com/api/v1/create_order',
                [
                    'invoice' => (string) $order->invoiceID,
                    'recipient_name' => $recipientName,
                    'recipient_address' => $recipientAddress,
                    'recipient_phone' => $recipientPhone,
                    'cod_amount' => (float) ($order->subTotal ?? 0),
                    'note' => (string) ($order->customerNote ?? ''),
                ]
            );
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Steadfast request failed: ' . $e->getMessage(),
            ];
        }

        if (!$response->ok()) {
            return [
                'ok' => false,
                'message' => 'Steadfast create_order failed with HTTP ' . $response->status(),
                'payload' => $response->body(),
            ];
        }

        $json = $response->json();
        if (!is_array($json)) {
            return [
                'ok' => false,
                'message' => 'Steadfast create_order returned invalid JSON payload',
                'payload' => $response->body(),
            ];
        }

        $consignment = $json['consignment'] ?? $json['data']['consignment'] ?? $json['data'] ?? null;
        if (!is_array($consignment)) {
            return [
                'ok' => false,
                'message' => 'Steadfast create_order response missing consignment payload',
                'payload' => $json,
            ];
        }

        $trackingCode = $consignment['tracking_code'] ?? $consignment['trackingCode'] ?? null;
        $rawStatus = $consignment['status'] ?? $consignment['delivery_status'] ?? null;
        $consignmentId = $consignment['consignment_id'] ?? $consignment['cid'] ?? null;

        return [
            'ok' => true,
            'tracking_code' => $trackingCode ? (string) $trackingCode : null,
            'raw_status' => $rawStatus ? (string) $rawStatus : null,
            'consignment_id' => $consignmentId ? (string) $consignmentId : null,
            'payload' => $json,
        ];
    }

    public function syncOrderStatus(Order $order, bool $force = false): array
    {
        if (!$this->hasSteadfastColumns()) {
            // Keep API responses working even if migration is not applied yet.
            return $this->statusPayload($order);
        }

        if ($this->isDryRun()) {
            return $this->statusPayload($order);
        }

        if (!$this->isSteadfastEligibleOrder($order)) {
            return $this->statusPayload($order);
        }

        if (!$force && !$this->shouldSyncNow($order)) {
            return $this->statusPayload($order);
        }

        $headers = $this->buildHeaders();
        if ($headers === null) {
            return $this->statusPayload($order);
        }

        $trackingCode = $this->extractTrackingCode($order);
        $invoiceId = $order->invoiceID ? (string) $order->invoiceID : null;

        $responses = [];
        if ($trackingCode) {
            $responses[] = Http::withHeaders($headers)
                ->timeout(15)
                ->get('https://portal.packzy.com/api/v1/status_by_trackingcode/' . urlencode($trackingCode));
        }
        if ($invoiceId) {
            $responses[] = Http::withHeaders($headers)
                ->timeout(15)
                ->get('https://portal.packzy.com/api/v1/status_by_invoice/' . urlencode($invoiceId));
        }

        $best = null;
        foreach ($responses as $response) {
            if (!$response->ok()) {
                continue;
            }

            $payload = $response->json();
            if (!is_array($payload)) {
                continue;
            }

            $normalized = $this->normalizeStatusResponse($payload);
            if (!empty($normalized['status'])) {
                $best = $normalized;
                break;
            }
        }

        $order->steadfast_last_synced_at = now();

        if ($best !== null) {
            $order->steadfast_status = $best['status'];
            $order->steadfast_payload = json_encode($best['payload']);

            if (!empty($best['tracking_code']) && empty($order->tracking_number)) {
                $order->tracking_number = $best['tracking_code'];
            }
            if (!empty($best['consignment_id']) && empty($order->steadfast_consignment_id)) {
                $order->steadfast_consignment_id = (string) $best['consignment_id'];
            }
        }

        $order->save();

        return $this->statusPayload($order);
    }

    public function customerVisibleStatus(Order $order): string
    {
        $steadfastDisplay = $this->mapSteadfastToDisplay($order->steadfast_status);
        if ($steadfastDisplay !== null) {
            return $steadfastDisplay;
        }

        $local = Str::lower((string) $order->status);
        if (in_array($local, ['pending'], true)) {
            return 'Pending';
        }
        if (in_array($local, ['confirmed', 'accepted'], true)) {
            return 'Accepted';
        }
        if (in_array($local, ['canceled', 'cancelled', 'rejected'], true)) {
            return 'Rejected';
        }
        if (in_array($local, ['ontheway', 'shipped'], true)) {
            if ($order->warehouse_sent_at || $order->tracking_number || $order->trackingLink) {
                return 'Shipped to warehouse';
            }

            return 'On the way';
        }
        if (in_array($local, ['delivered'], true)) {
            return 'Delivered';
        }
        if (in_array($local, ['return', 'returned'], true)) {
            return 'Returned';
        }

        return $order->status ?: 'Pending';
    }

    public function mapSteadfastToDisplay(?string $rawStatus): ?string
    {
        if (!$rawStatus) {
            return null;
        }

        $status = Str::lower(trim($rawStatus));
        if ($status === '') {
            return null;
        }

        if (str_contains($status, 'deliver')) {
            return 'Delivered';
        }

        if (str_contains($status, 'cancel') || str_contains($status, 'return')) {
            return 'Cancelled';
        }

        if (str_contains($status, 'hold')) {
            return 'On hold';
        }

        if (
            str_contains($status, 'pickup') ||
            str_contains($status, 'picked') ||
            str_contains($status, 'transit') ||
            str_contains($status, 'dispatch') ||
            str_contains($status, 'hub') ||
            str_contains($status, 'on_the_way')
        ) {
            return 'On the way';
        }

        if (
            str_contains($status, 'in_review') ||
            str_contains($status, 'review') ||
            str_contains($status, 'pending')
        ) {
            return 'Shipped to warehouse';
        }

        return Str::title(str_replace('_', ' ', $rawStatus));
    }

    public function getStatusPayload(Order $order): array
    {
        return $this->statusPayload($order);
    }

    private function statusPayload(Order $order): array
    {
        return [
            'status' => (string) ($order->status ?? ''),
            'customer_status' => $this->customerVisibleStatus($order),
            'steadfast_status' => $order->steadfast_status,
            'steadfast_last_synced_at' => $order->steadfast_last_synced_at?->toIso8601String(),
            'warehouse_sent_at' => $order->warehouse_sent_at?->toIso8601String(),
        ];
    }

    private function isSteadfastEligibleOrder(Order $order): bool
    {
        return (bool) (
            $order->warehouse_sent_at ||
            $order->tracking_number ||
            $this->extractTrackingCode($order) ||
            ((int) $order->courier_id === 26)
        );
    }

    private function shouldSyncNow(Order $order): bool
    {
        if (!$order->steadfast_last_synced_at) {
            return true;
        }

        return $order->steadfast_last_synced_at->diffInSeconds(now()) >= self::STATUS_SYNC_COOLDOWN_SECONDS;
    }

    private function buildHeaders(): ?array
    {
        $apiKey = env('STEADFAST_API_KEY');
        $secretKey = env('STEADFAST_SECRET_KEY');
        if (!$apiKey || !$secretKey) {
            return null;
        }

        return [
            'Api-Key' => $apiKey,
            'Secret-Key' => $secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function extractTrackingCode(Order $order): ?string
    {
        if (!empty($order->tracking_number)) {
            return (string) $order->tracking_number;
        }

        if (empty($order->trackingLink)) {
            return null;
        }

        $path = trim((string) parse_url((string) $order->trackingLink, PHP_URL_PATH), '/');
        if ($path === '') {
            return null;
        }

        $parts = explode('/', $path);
        return !empty($parts) ? (string) end($parts) : null;
    }

    private function normalizeStatusResponse(array $json): array
    {
        $candidate = $json['consignment'] ?? $json['data']['consignment'] ?? $json['data'] ?? $json;
        if (!is_array($candidate)) {
            return [
                'status' => null,
                'tracking_code' => null,
                'consignment_id' => null,
                'payload' => $json,
            ];
        }

        $status = $candidate['delivery_status']
            ?? $candidate['status']
            ?? $candidate['current_status']
            ?? null;

        $trackingCode = $candidate['tracking_code'] ?? $candidate['trackingCode'] ?? null;
        $consignmentId = $candidate['consignment_id'] ?? $candidate['cid'] ?? null;

        return [
            'status' => $status ? (string) $status : null,
            'tracking_code' => $trackingCode ? (string) $trackingCode : null,
            'consignment_id' => $consignmentId ? (string) $consignmentId : null,
            'payload' => $json,
        ];
    }

    private function hasSteadfastColumns(): bool
    {
        if (self::$hasStatusColumns !== null) {
            return self::$hasStatusColumns;
        }

        self::$hasStatusColumns = Schema::hasColumns('orders', [
            'warehouse_sent_at',
            'steadfast_status',
            'steadfast_consignment_id',
            'steadfast_last_synced_at',
            'steadfast_payload',
        ]);

        return self::$hasStatusColumns;
    }

    private function isDryRun(): bool
    {
        return filter_var((string) env('STEADFAST_DRY_RUN', false), FILTER_VALIDATE_BOOL);
    }
}
