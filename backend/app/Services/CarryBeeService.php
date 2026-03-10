<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CarryBeeService
{
    protected string $baseUrl;
    protected array $headers;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.carrybee.base_url', env('CARRYBEE_BASE_URL', '')), '/');
        $this->headers = [
            'Client-ID'      => config('services.carrybee.client_id', env('CARRYBEE_CLIENT_ID', '')),
            'Client-Secret'  => config('services.carrybee.client_secret', env('CARRYBEE_CLIENT_SECRET', '')),
            'Client-Context' => config('services.carrybee.client_context', env('CARRYBEE_CLIENT_CONTEXT', '')),
        ];
    }

    /**
     * GET /api/v2/cities
     */
    public function getCities(): array
    {
        return $this->get('/api/v2/cities');
    }

    /**
     * GET /api/v2/cities/{cityId}/zones
     */
    public function getZones(int $cityId): array
    {
        return $this->get("/api/v2/cities/{$cityId}/zones");
    }

    /**
     * GET /api/v2/cities/{cityId}/zones/{zoneId}/areas
     */
    public function getAreas(int $cityId, int $zoneId): array
    {
        return $this->get("/api/v2/cities/{$cityId}/zones/{$zoneId}/areas");
    }

    /**
     * GET /api/v2/area-suggestion?search=...
     */
    public function searchAreas(string $query): array
    {
        return $this->get('/api/v2/area-suggestion', ['search' => $query]);
    }

    /**
     * POST /api/v2/stores — Create a pickup store in Carry Bee.
     */
    public function createStore(array $data): array
    {
        return $this->post('/api/v2/stores', $data);
    }

    /**
     * POST /api/v2/parcels — Create a delivery parcel in Carry Bee.
     *
     * Required fields from Carry Bee:
     *  store_id, merchant_order_id, recipient_name, recipient_phone,
     *  recipient_address, recipient_city, recipient_zone, recipient_area,
     *  delivery_type, item_type, amount_to_collect, item_weight
     */
    public function createParcel(array $data): array
    {
        return $this->post('/api/v2/parcels', $data);
    }

    /**
     * Generic POST helper.
     */
    protected function post(string $path, array $data): array
    {
        try {
            $response = Http::withHeaders(array_merge($this->headers, [
                'Content-Type' => 'application/json',
            ]))->post("{$this->baseUrl}{$path}", $data);

            $body = $response->json() ?? [];

            if (!$response->successful()) {
                Log::warning("CarryBee POST {$path} failed", [
                    'status' => $response->status(),
                    'body'   => $body,
                    'data'   => $data,
                ]);
            }

            return $body;
        } catch (\Throwable $e) {
            Log::error("CarryBee POST {$path} exception", [
                'message' => $e->getMessage(),
                'data'    => $data,
            ]);
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }

    /**
     * Generic GET helper.
     */
    protected function get(string $path, array $query = []): array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get("{$this->baseUrl}{$path}", $query);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('CarryBee API error', [
                'path'    => $path,
                'message' => $e->getMessage(),
            ]);
            return ['error' => true, 'message' => $e->getMessage()];
        }
    }
}
