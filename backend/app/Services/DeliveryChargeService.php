<?php

namespace App\Services;

use App\Models\Basicinfo;
use App\Models\Product;
use App\Models\Vendor;

class DeliveryChargeService
{
    /**
     * Resolve the delivery charge for a given vendor shipping to a customer city.
     *
     * @param  int|null  $vendorId      The vendor (supplier) ID
     * @param  int|null  $customerCityId The CarryBee city ID of the customer destination
     * @return array{charge: float, zone: string, zone_label: string}
     */
    public function resolveCharge(?int $vendorId, ?int $customerCityId): array
    {
        $basicInfo = Basicinfo::first();

        // If no customer city selected, fall back to legacy inside-dhaka charge
        if (!$customerCityId) {
            return [
                'charge'     => (float) ($basicInfo->inside_dhaka_charge ?? 60),
                'zone'       => 'unknown',
                'zone_label' => 'Delivery Charge',
            ];
        }

        // Get vendor's pickup city from CarryBee
        $vendorCityId = null;
        if ($vendorId) {
            $vendor = Vendor::find($vendorId);
            $vendorCityId = $vendor?->pickup_city_id;

            // Fallback: if pickup_city_id not set, try to match vendor's text city
            // against CarryBee city names to auto-resolve
            if (!$vendorCityId && $vendor && $vendor->city) {
                try {
                    $carryBee = new CarryBeeService();
                    $response = $carryBee->getCities();
                    $cities = $response['data']['cities'] ?? $response['cities'] ?? [];
                    foreach ($cities as $city) {
                        if (strcasecmp(trim($city['name'] ?? ''), trim($vendor->city)) === 0) {
                            $vendorCityId = $city['id'];
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    // CarryBee API unavailable — keep vendorCityId null
                }
            }
        }

        // Determine zone: same city or inter-city
        if ($vendorCityId && (int) $vendorCityId === (int) $customerCityId) {
            $zone = 'same_city';
            $zoneLabel = 'Same City';
            $charge = (float) ($basicInfo->default_same_city_charge
                ?? $basicInfo->inside_dhaka_charge
                ?? 60);
        } else {
            $zone = 'inter_city';
            $zoneLabel = 'Inter-City';
            $charge = (float) ($basicInfo->default_inter_city_charge
                ?? $basicInfo->outside_dhaka_charge
                ?? 130);
        }

        return [
            'charge'     => $charge,
            'zone'       => $zone,
            'zone_label' => $zoneLabel,
        ];
    }

    /**
     * Resolve delivery charges for multiple vendors (multi-vendor cart).
     *
     * @param  array  $vendorIds       Array of vendor IDs in the cart
     * @param  int|null  $customerCityId  CarryBee city ID of customer destination
     * @return array{vendors: array, total_charge: float}
     */
    public function resolveForCart(array $vendorIds, ?int $customerCityId): array
    {
        $vendorIds = array_unique(array_filter($vendorIds));
        $results = [];
        $totalCharge = 0;

        if (empty($vendorIds)) {
            // Fallback: single charge with no vendor info
            $result = $this->resolveCharge(null, $customerCityId);
            return [
                'vendors'      => [['vendor_id' => null] + $result],
                'total_charge' => $result['charge'],
            ];
        }

        foreach ($vendorIds as $vendorId) {
            $result = $this->resolveCharge($vendorId, $customerCityId);
            $result['vendor_id'] = $vendorId;

            // Add vendor name for display
            $vendor = Vendor::find($vendorId);
            $result['vendor_name'] = $vendor?->public_name ?? 'Supplier';

            $results[] = $result;
            $totalCharge += $result['charge'];
        }

        return [
            'vendors'      => $results,
            'total_charge' => $totalCharge,
        ];
    }

    /**
     * Get the delivery charge config (same_city + inter_city rates) for the API.
     *
     * @param  int|null  $customerCityId
     * @param  array     $vendorIds
     * @return array
     */
    public function getChargesForCheckout(?int $customerCityId, array $vendorIds = []): array
    {
        $basicInfo = Basicinfo::first();

        $sameCityCharge = (float) ($basicInfo->default_same_city_charge
            ?? $basicInfo->inside_dhaka_charge
            ?? 60);
        $interCityCharge = (float) ($basicInfo->default_inter_city_charge
            ?? $basicInfo->outside_dhaka_charge
            ?? 130);

        $response = [
            'same_city_charge'  => $sameCityCharge,
            'inter_city_charge' => $interCityCharge,
        ];

        // If vendor IDs provided, compute per-vendor breakdown
        if (!empty($vendorIds) && $customerCityId) {
            $breakdown = $this->resolveForCart($vendorIds, $customerCityId);
            $response['vendors'] = $breakdown['vendors'];
            $response['total_charge'] = $breakdown['total_charge'];
        }

        return $response;
    }
}
