<?php

namespace App\Http\Controllers;

use App\Helpers\StorageHelper;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\RefundClaim;
use App\Models\RefundClaimMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefundClaimController extends Controller
{
    public function eligibleOrders(Request $request)
    {
        $orders = Order::with([
                'customers',
                'orderproducts.product:id,ProductName,ProductSlug,ProductSku,ViewProductImage,warranty_days',
            ])
            ->where('user_id', Auth::id())
            ->where('status', 'Delivered')
            ->whereNotNull('deliveryDate')
            ->latest('deliveryDate')
            ->limit(100)
            ->get();

        $claimedLineIds = RefundClaim::where('user_id', Auth::id())
            ->pluck('orderproduct_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $eligible = [];
        foreach ($orders as $order) {
            foreach ($order->orderproducts as $line) {
                $warrantyDays = $this->resolveWarrantyDays($line);
                if ($warrantyDays <= 0 || in_array((int) $line->id, $claimedLineIds, true)) {
                    continue;
                }

                $expiresAt = $this->expiryDate($order->deliveryDate, $warrantyDays);
                if (!$expiresAt || now()->gt($expiresAt)) {
                    continue;
                }

                $eligible[] = $this->formatEligibleLine($order, $line, $warrantyDays, $expiresAt);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Eligible refund products',
            'data' => [
                'eligible_orders' => $eligible,
            ],
        ]);
    }

    public function index()
    {
        $claims = RefundClaim::with([
                'order:id,invoiceID,status,deliveryDate',
                'orderproduct:id,order_id,product_id,productName,productCode,quantity,productPrice,color,size',
                'product:id,ProductName,ProductSlug,ProductSku,ViewProductImage',
                'messages',
            ])
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(fn (RefundClaim $claim) => $this->formatClaim($claim));

        return response()->json([
            'status' => true,
            'message' => 'Refund claim list',
            'data' => [
                'claims' => $claims,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'orderproduct_id' => ['required', 'integer', 'exists:orderproducts,id'],
            'message' => ['required', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $line = Orderproduct::with([
            'order',
            'product:id,ProductName,ProductSlug,ProductSku,ViewProductImage,warranty_days',
        ])->findOrFail($data['orderproduct_id']);

        $eligibility = $this->validateLineEligibility($line);
        if ($eligibility !== true) {
            return $eligibility;
        }

        $imageUrl = $request->hasFile('image')
            ? StorageHelper::store($request->file('image'), 'refund-claims')
            : null;

        $claim = DB::transaction(function () use ($line, $data, $imageUrl) {
            $warrantyDays = $this->resolveWarrantyDays($line);
            $expiresAt = $this->expiryDate($line->order->deliveryDate, $warrantyDays);

            $claim = RefundClaim::create([
                'claim_number' => 'RF-TMP-' . Str::upper(Str::random(10)),
                'user_id' => Auth::id(),
                'order_id' => $line->order_id,
                'orderproduct_id' => $line->id,
                'product_id' => $line->product_id,
                'status' => 'pending',
                'delivery_date' => Carbon::parse($line->order->deliveryDate)->toDateString(),
                'expires_at' => $expiresAt,
                'warranty_days' => $warrantyDays,
                'message' => $data['message'],
                'image_path' => $imageUrl,
            ]);

            $claim->claim_number = 'RF-' . now()->format('Ymd') . '-' . str_pad((string) $claim->id, 6, '0', STR_PAD_LEFT);
            $claim->save();

            RefundClaimMessage::create([
                'refund_claim_id' => $claim->id,
                'sender_type' => 'user',
                'user_id' => Auth::id(),
                'message' => $data['message'],
                'attachment_path' => $imageUrl,
            ]);

            return $claim;
        });

        $claim->load([
            'order:id,invoiceID,status,deliveryDate',
            'orderproduct:id,order_id,product_id,productName,productCode,quantity,productPrice,color,size',
            'product:id,ProductName,ProductSlug,ProductSku,ViewProductImage',
            'messages',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Refund claim submitted successfully',
            'data' => [
                'claim' => $this->formatClaim($claim),
            ],
        ], 201);
    }

    public function show(RefundClaim $claim)
    {
        if ((int) $claim->user_id !== (int) Auth::id()) {
            abort(404);
        }

        $claim->load([
            'order:id,invoiceID,status,deliveryDate',
            'orderproduct:id,order_id,product_id,productName,productCode,quantity,productPrice,color,size',
            'product:id,ProductName,ProductSlug,ProductSku,ViewProductImage',
            'messages.user:id,name,phone,email',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Refund claim details',
            'data' => [
                'claim' => $this->formatClaim($claim),
            ],
        ]);
    }

    public function message(Request $request, RefundClaim $claim)
    {
        if ((int) $claim->user_id !== (int) Auth::id()) {
            abort(404);
        }

        if ($claim->status === 'closed') {
            return response()->json([
                'status' => false,
                'message' => 'This refund claim is closed.',
            ], 422);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $imageUrl = $request->hasFile('image')
            ? StorageHelper::store($request->file('image'), 'refund-claims')
            : null;

        RefundClaimMessage::create([
            'refund_claim_id' => $claim->id,
            'sender_type' => 'user',
            'user_id' => Auth::id(),
            'message' => $data['message'],
            'attachment_path' => $imageUrl,
        ]);

        if ($claim->status === 'pending') {
            $claim->status = 'in_progress';
            $claim->save();
        }

        $claim->load([
            'order:id,invoiceID,status,deliveryDate',
            'orderproduct:id,order_id,product_id,productName,productCode,quantity,productPrice,color,size',
            'product:id,ProductName,ProductSlug,ProductSku,ViewProductImage',
            'messages',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Reply sent successfully',
            'data' => [
                'claim' => $this->formatClaim($claim),
            ],
        ]);
    }

    private function validateLineEligibility(Orderproduct $line)
    {
        if (!$line->order || (int) $line->order->user_id !== (int) Auth::id()) {
            return response()->json(['status' => false, 'message' => 'Order product not found.'], 404);
        }

        if ($line->order->status !== 'Delivered' || empty($line->order->deliveryDate)) {
            return response()->json(['status' => false, 'message' => 'Only delivered products can be claimed.'], 422);
        }

        if ($this->resolveWarrantyDays($line) <= 0) {
            return response()->json(['status' => false, 'message' => 'This product has no active refund or warranty eligibility.'], 422);
        }

        $expiresAt = $this->expiryDate($line->order->deliveryDate, $this->resolveWarrantyDays($line));
        if (!$expiresAt || now()->gt($expiresAt)) {
            return response()->json(['status' => false, 'message' => 'The refund claim window has expired.'], 422);
        }

        $exists = RefundClaim::where('orderproduct_id', $line->id)->exists();
        if ($exists) {
            return response()->json(['status' => false, 'message' => 'A refund claim already exists for this product.'], 422);
        }

        return true;
    }

    private function resolveWarrantyDays(Orderproduct $line): int
    {
        $snapshot = (int) ($line->warranty_days_snapshot ?? 0);
        if ($snapshot > 0) {
            return $snapshot;
        }

        return (int) ($line->product?->warranty_days ?? 0);
    }

    private function expiryDate($deliveryDate, int $warrantyDays): ?Carbon
    {
        if (!$deliveryDate || $warrantyDays <= 0) {
            return null;
        }

        return Carbon::parse($deliveryDate)->addDays($warrantyDays)->endOfDay();
    }

    private function formatEligibleLine(Order $order, Orderproduct $line, int $warrantyDays, Carbon $expiresAt): array
    {
        return [
            'order_id' => $order->id,
            'invoiceID' => $order->invoiceID,
            'orderproduct_id' => $line->id,
            'product_id' => $line->product_id,
            'product_name' => $line->productName ?: $line->product?->ProductName,
            'product_code' => $line->productCode,
            'product_image' => $line->product?->ViewProductImage,
            'quantity' => $line->quantity,
            'product_price' => $line->productPrice,
            'color' => $line->color,
            'size' => $line->size,
            'delivery_date' => Carbon::parse($order->deliveryDate)->toDateString(),
            'expires_at' => $expiresAt->toIso8601String(),
            'warranty_days' => $warrantyDays,
            'days_remaining' => max(0, now()->diffInDays($expiresAt, false)),
            'customer' => [
                'name' => $order->customers?->customerName,
                'phone' => $order->customers?->customerPhone,
            ],
        ];
    }

    private function formatClaim(RefundClaim $claim): array
    {
        return [
            'id' => $claim->id,
            'claim_number' => $claim->claim_number,
            'status' => $claim->status,
            'message' => $claim->message,
            'image_path' => $claim->image_path,
            'delivery_date' => optional($claim->delivery_date)->toDateString(),
            'expires_at' => optional($claim->expires_at)->toIso8601String(),
            'warranty_days' => $claim->warranty_days,
            'created_at' => optional($claim->created_at)->toIso8601String(),
            'updated_at' => optional($claim->updated_at)->toIso8601String(),
            'order' => $claim->order,
            'orderproduct' => $claim->orderproduct,
            'product' => $claim->product,
            'messages' => $claim->messages->map(fn (RefundClaimMessage $message) => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'message' => $message->message,
                'attachment_path' => $message->attachment_path,
                'created_at' => optional($message->created_at)->toIso8601String(),
            ])->values(),
        ];
    }
}
