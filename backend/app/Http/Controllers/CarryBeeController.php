<?php

namespace App\Http\Controllers;

use App\Services\CarryBeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarryBeeController extends Controller
{
    protected CarryBeeService $carryBee;

    public function __construct(CarryBeeService $carryBee)
    {
        $this->carryBee = $carryBee;
    }

    /**
     * GET /api/carrybee/cities
     */
    public function cities(): JsonResponse
    {
        $result = $this->carryBee->getCities();
        return response()->json($result);
    }

    /**
     * GET /api/carrybee/cities/{cityId}/zones
     */
    public function zones(int $cityId): JsonResponse
    {
        $result = $this->carryBee->getZones($cityId);
        return response()->json($result);
    }

    /**
     * GET /api/carrybee/cities/{cityId}/zones/{zoneId}/areas
     */
    public function areas(int $cityId, int $zoneId): JsonResponse
    {
        $result = $this->carryBee->getAreas($cityId, $zoneId);
        return response()->json($result);
    }
}
