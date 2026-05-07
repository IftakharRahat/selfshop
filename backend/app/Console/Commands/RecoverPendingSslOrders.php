<?php

namespace App\Console\Commands;

use App\Services\SslCommerzOrderFinalizer;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class RecoverPendingSslOrders extends Command
{
    protected $signature = 'orders:recover-pending-ssl
        {--fix : Finalize paid pending SSLCommerz orders}
        {--mark-failed : Mark gateway-rejected pending orders as Failed}
        {--limit=100 : Maximum pending orders to inspect}';

    protected $description = 'Find Pending Payment SSLCommerz orders and optionally finalize paid ones safely.';

    public function handle(SslCommerzOrderFinalizer $finalizer): int
    {
        $fix = (bool) $this->option('fix');
        $markFailed = (bool) $this->option('mark-failed');
        $limit = max(1, (int) $this->option('limit'));

        $orders = DB::table('orders')
            ->where('status', 'Pending Payment')
            ->whereNotNull('transaction_id')
            ->where('transaction_id', '!=', '')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No Pending Payment SSLCommerz orders found.');
            return self::SUCCESS;
        }

        $this->info('Found ' . $orders->count() . ' Pending Payment order(s).');

        foreach ($orders as $order) {
            $tranId = (string) $order->transaction_id;
            $this->line("Order #{$order->id} ({$order->invoiceID}) transaction {$tranId}");

            if (!$fix && !$markFailed) {
                continue;
            }

            $gatewayData = $this->lookupTransaction($tranId);
            if (!$gatewayData) {
                $this->warn('  Could not verify transaction with SSLCommerz.');
                continue;
            }

            $status = strtoupper((string) ($gatewayData['status'] ?? ''));
            if (in_array($status, ['VALID', 'VALIDATED'], true)) {
                if (!$fix) {
                    $this->info('  Gateway says paid. Run with --fix to finalize.');
                    continue;
                }

                $request = new Request(array_merge($gatewayData, [
                    'tran_id' => $tranId,
                    'status' => $status,
                    'amount' => $gatewayData['amount'] ?? $order->paymentAmount ?? $order->deliveryCharge ?? 0,
                    'value_a' => $order->user_id,
                ]));

                $result = $finalizer->finalizeFromCallback($request, false, true);
                if ($result['success'] ?? false) {
                    $this->info('  Finalized order #' . $result['order_id'] . '.');
                } else {
                    $this->error('  Finalization failed: ' . ($result['message'] ?? 'Unknown error'));
                }

                continue;
            }

            if ($markFailed && in_array($status, ['FAILED', 'CANCELLED', 'CANCELED', 'INVALID'], true)) {
                $updates = [
                    'status' => $status === 'FAILED' ? 'Failed' : 'Canceled',
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('orders', 'payment_status')) {
                    $updates['payment_status'] = $status === 'FAILED' ? 'Failed' : 'Canceled';
                }

                DB::table('orders')->where('id', $order->id)->update($updates);

                $this->warn("  Marked as {$status}.");
                continue;
            }

            $this->warn('  Gateway status is ' . ($status ?: 'unknown') . '; left unchanged.');
        }

        return self::SUCCESS;
    }

    private function lookupTransaction(string $tranId): ?array
    {
        $config = config('sslcommerz');
        $url = rtrim((string) $config['apiDomain'], '/') . $config['apiUrl']['transaction_status'];

        try {
            $response = Http::timeout(20)->get($url, [
                'tran_id' => $tranId,
                'store_id' => $config['apiCredentials']['store_id'],
                'store_passwd' => $config['apiCredentials']['store_password'],
                'v' => 1,
                'format' => 'json',
            ]);
        } catch (\Throwable $e) {
            $this->warn('  SSLCommerz lookup error: ' . $e->getMessage());
            return null;
        }

        if (!$response->successful()) {
            $this->warn('  SSLCommerz lookup HTTP ' . $response->status());
            return null;
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload[0]) && is_array($payload[0])) {
            foreach ($payload as $candidate) {
                if (($candidate['tran_id'] ?? null) === $tranId) {
                    return $candidate;
                }
            }

            return $payload[0];
        }

        return $payload;
    }
}
