<?php

namespace App\Services;

use App\Mail\NewOrderForSupplier;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Sentry\Severity;
use Sentry\State\Scope;
use Throwable;
use function Sentry\captureException;
use function Sentry\captureMessage;
use function Sentry\withScope;

class SupplierOrderNotificationService
{
    protected BulkSmsService $sms;

    public function __construct(BulkSmsService $sms)
    {
        $this->sms = $sms;
    }

    /**
     * Send SMS + email notifications to each vendor whose products appear in the order.
     *
     * @param Order $order
     * @param int[] $vendorIds Unique vendor IDs involved in this order
     * @param string|null $customerName For display in messages
     */
    public function notify(Order $order, array $vendorIds, ?string $customerName = null): void
    {
        if (empty($vendorIds)) {
            Log::warning('SupplierOrderNotificationService: No vendor IDs found for order', [
                'order_id' => $order->id,
                'invoice_id' => $order->invoiceID,
            ]);

            $this->captureNotificationMessage(
                'Supplier order notification skipped: no vendor IDs',
                Severity::warning(),
                $order,
                null,
                'all',
                ['reason' => 'empty_vendor_ids']
            );

            return;
        }

        foreach ($vendorIds as $vendorId) {
            $vendor = null;

            try {
                $vendor = Vendor::find($vendorId);

                if (!$vendor) {
                    Log::warning('SupplierOrderNotificationService: Vendor not found', [
                        'vendor_id' => $vendorId,
                        'order_id' => $order->id,
                        'invoice_id' => $order->invoiceID,
                    ]);

                    $this->captureNotificationMessage(
                        'Supplier order notification skipped: vendor not found',
                        Severity::warning(),
                        $order,
                        null,
                        'all',
                        ['vendor_id' => $vendorId, 'reason' => 'vendor_not_found']
                    );

                    continue;
                }

                $vendorProductIds = Product::where('vendor_id', $vendorId)->pluck('id')->toArray();
                $orderProducts = Orderproduct::where('order_id', $order->id)
                    ->whereIn('product_id', $vendorProductIds)
                    ->get();

                if ($orderProducts->isEmpty()) {
                    Log::warning('SupplierOrderNotificationService: No vendor products found in order', [
                        'vendor_id' => $vendor->id,
                        'order_id' => $order->id,
                        'invoice_id' => $order->invoiceID,
                    ]);

                    $this->captureNotificationMessage(
                        'Supplier order notification skipped: vendor has no products in order',
                        Severity::warning(),
                        $order,
                        $vendor,
                        'all',
                        ['reason' => 'no_vendor_products_in_order']
                    );

                    continue;
                }

                Log::info('SupplierOrderNotificationService: Starting supplier notification', [
                    'vendor_id' => $vendor->id,
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoiceID,
                    'product_count' => $orderProducts->count(),
                ]);

                $this->sendSms($vendor, $order, $orderProducts);
                $this->sendEmail($vendor, $order, $orderProducts, $customerName);
            } catch (Throwable $e) {
                Log::warning('SupplierOrderNotificationService: Failed for vendor', [
                    'vendor_id' => $vendorId,
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoiceID,
                    'error' => $e->getMessage(),
                ]);

                $this->captureNotificationException($e, $order, $vendor ?? null, 'all', [
                    'reason' => 'vendor_notification_exception',
                    'vendor_id' => $vendorId,
                ]);
            }
        }
    }

    protected function sendSms(Vendor $vendor, Order $order, $orderProducts): void
    {
        $phone = $vendor->contact_phone;

        if (empty($phone)) {
            Log::warning('SupplierOrderNotificationService: No contact_phone for vendor', [
                'vendor_id' => $vendor->id,
                'order_id' => $order->id,
                'invoice_id' => $order->invoiceID,
            ]);

            $this->captureNotificationMessage(
                'Supplier order SMS skipped: vendor has no contact phone',
                Severity::warning(),
                $order,
                $vendor,
                'sms',
                ['reason' => 'missing_contact_phone']
            );

            return;
        }

        $productSummary = $orderProducts->map(function ($item) {
            return $item->productName . ' x' . $item->quantity;
        })->implode(', ');

        $message = "SelfShop: New order {$order->invoiceID}! Products: {$productSummary}. Please check your supplier dashboard.";

        if (strlen($message) > 320) {
            $message = "SelfShop: New order {$order->invoiceID} with {$orderProducts->count()} product(s). Please check your supplier dashboard.";
        }

        try {
            $result = $this->sms->send($phone, $message);

            $context = [
                'reason' => ($result['success'] ?? false) ? 'sms_sent' : 'sms_provider_failed',
                'recipient' => $this->maskPhone((string) ($result['number'] ?? $phone)),
                'provider_status' => $result['status'] ?? null,
                'provider_response' => $this->summarizeValue($result['response'] ?? null),
                'message_length' => strlen($message),
                'product_count' => $orderProducts->count(),
            ];

            if (!($result['success'] ?? false)) {
                Log::warning('SupplierOrderNotificationService: SMS provider failed', [
                    'vendor_id' => $vendor->id,
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoiceID,
                    'phone' => $context['recipient'],
                    'provider_status' => $result['status'] ?? null,
                    'provider_response' => $result['response'] ?? null,
                ]);

                $this->captureNotificationMessage(
                    'Supplier order SMS failed',
                    Severity::warning(),
                    $order,
                    $vendor,
                    'sms',
                    $context
                );

                return;
            }

            Log::info('SupplierOrderNotificationService: SMS sent', [
                'vendor_id' => $vendor->id,
                'order_id' => $order->id,
                'invoice_id' => $order->invoiceID,
                'phone' => $context['recipient'],
                'provider_status' => $result['status'] ?? null,
            ]);
        } catch (Throwable $e) {
            Log::warning('SupplierOrderNotificationService: SMS failed', [
                'vendor_id' => $vendor->id,
                'order_id' => $order->id,
                'invoice_id' => $order->invoiceID,
                'error' => $e->getMessage(),
            ]);

            $this->captureNotificationException($e, $order, $vendor, 'sms', [
                'reason' => 'sms_exception',
                'recipient' => $this->maskPhone((string) $phone),
                'message_length' => strlen($message),
            ]);
        }
    }

    protected function sendEmail(Vendor $vendor, Order $order, $orderProducts, ?string $customerName): void
    {
        $email = $vendor->contact_email;

        if (empty($email)) {
            Log::warning('SupplierOrderNotificationService: No contact_email for vendor', [
                'vendor_id' => $vendor->id,
                'order_id' => $order->id,
                'invoice_id' => $order->invoiceID,
            ]);

            $this->captureNotificationMessage(
                'Supplier order email skipped: vendor has no contact email',
                Severity::warning(),
                $order,
                $vendor,
                'email',
                ['reason' => 'missing_contact_email']
            );

            return;
        }

        try {
            Mail::to($email)->send(new NewOrderForSupplier($order, $vendor, $orderProducts, $customerName));

            Log::info('SupplierOrderNotificationService: Email sent', [
                'vendor_id' => $vendor->id,
                'email' => $this->maskEmail($email),
                'order_id' => $order->id,
                'invoice_id' => $order->invoiceID,
            ]);
        } catch (Throwable $e) {
            Log::warning('SupplierOrderNotificationService: Email failed', [
                'vendor_id' => $vendor->id,
                'email' => $this->maskEmail($email),
                'order_id' => $order->id,
                'invoice_id' => $order->invoiceID,
                'error' => $e->getMessage(),
            ]);

            $this->captureNotificationException($e, $order, $vendor, 'email', [
                'reason' => 'email_exception',
                'recipient' => $this->maskEmail($email),
                'product_count' => $orderProducts->count(),
            ]);
        }
    }

    protected function captureNotificationMessage(string $message, Severity $severity, Order $order, ?Vendor $vendor, string $channel, array $context = []): void
    {
        withScope(function (Scope $scope) use ($message, $severity, $order, $vendor, $channel, $context): void {
            $this->configureSentryScope($scope, $order, $vendor, $channel, $context);
            captureMessage($message, $severity);
        });
    }

    protected function captureNotificationException(Throwable $exception, Order $order, ?Vendor $vendor, string $channel, array $context = []): void
    {
        withScope(function (Scope $scope) use ($exception, $order, $vendor, $channel, $context): void {
            $this->configureSentryScope($scope, $order, $vendor, $channel, $context);
            captureException($exception);
        });
    }

    protected function configureSentryScope(Scope $scope, Order $order, ?Vendor $vendor, string $channel, array $context): void
    {
        $scope->setTag('feature', 'supplier_order_notification');
        $scope->setTag('notification_channel', $channel);
        $scope->setTag('order_id', (string) $order->id);
        $scope->setTag('invoice_id', (string) $order->invoiceID);

        if ($vendor) {
            $scope->setTag('vendor_id', (string) $vendor->id);
        } elseif (isset($context['vendor_id'])) {
            $scope->setTag('vendor_id', (string) $context['vendor_id']);
        }

        if (isset($context['reason'])) {
            $scope->setTag('notification_reason', (string) $context['reason']);
        }

        $scope->setContext('supplier_order_notification', array_merge([
            'order_id' => $order->id,
            'invoice_id' => $order->invoiceID,
            'vendor_id' => $vendor?->id,
            'vendor_company' => $vendor?->company_name,
            'channel' => $channel,
        ], $context));
    }

    protected function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) <= 4) {
            return '****';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }

    protected function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return '***';
        }

        [$name, $domain] = explode('@', $email, 2);
        $visible = substr($name, 0, 2);

        return $visible . '***@' . $domain;
    }

    protected function summarizeValue($value): string
    {
        $text = is_scalar($value) || $value === null
            ? (string) $value
            : json_encode($value);

        return substr((string) $text, 0, 1000);
    }
}
