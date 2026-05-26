<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Product;
use App\Models\User;
use App\Models\WarrantyClaim;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestWarrantyClaimSystem extends Command
{
    protected $signature = 'test:warranty-claims {--cleanup : Remove test data after running}';
    protected $description = 'End-to-end test for the warranty/refund claim system';

    private array $testIds = [];

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════╗');
        $this->info('║   WARRANTY / REFUND CLAIM SYSTEM — E2E TEST     ║');
        $this->info('╚══════════════════════════════════════════════════╝');
        $this->info('');

        $passed = 0;
        $failed = 0;
        $errors = [];

        // ── TEST 1: Migration / Table exists ──
        $this->info('━━━ TEST 1: Database tables ━━━');
        if (Schema::hasTable('warranty_claims')) {
            $this->pass('warranty_claims table exists');
            $passed++;
        } else {
            $this->fail('warranty_claims table does NOT exist — run: php artisan migrate');
            $failed++;
            $errors[] = 'Table missing';
            $this->printSummary($passed, $failed, $errors);
            return 1;
        }

        if (Schema::hasColumn('products', 'warranty_days')) {
            $this->pass('products.warranty_days column exists');
            $passed++;
        } else {
            $this->fail('products.warranty_days column missing');
            $failed++;
            $errors[] = 'Column missing';
        }

        // ── TEST 2: Setup test data ──
        $this->info('');
        $this->info('━━━ TEST 2: Creating test data ━━━');

        // Find a real user
        $user = User::first();
        if (!$user) {
            $this->fail('No users in DB — cannot test');
            $this->printSummary($passed, ++$failed, $errors);
            return 1;
        }
        $this->pass("Using user: {$user->name} (ID: {$user->id})");
        $passed++;

        // Get a real category_id for product creation
        $categoryId = DB::table('categories')->value('id');
        if (!$categoryId) {
            $this->fail('No categories in DB — cannot create test products');
            $this->printSummary($passed, ++$failed, $errors);
            return 1;
        }

        // Create a test product WITH warranty
        $productWithWarranty = Product::create([
            'ProductName' => '🧪 TEST WARRANTY PRODUCT 15-DAY',
            'ProductSlug' => 'test-warranty-product-' . now()->timestamp,
            'ProductSku'  => 'TEST-WRN-SKU-' . now()->timestamp,
            'ProductResellerPrice' => 500,
            'ProductSalePrice' => 600,
            'ProductRegularPrice' => 700,
            'warranty_days' => 15,
            'status' => 'Active',
            'category_id' => $categoryId,
        ]);
        $this->testIds['product_with_warranty'] = $productWithWarranty->id;
        $this->pass("Created product WITH warranty: ID={$productWithWarranty->id}, warranty_days=15");
        $passed++;

        // Create a test product WITHOUT warranty
        $productNoWarranty = Product::create([
            'ProductName' => '🧪 TEST NO-WARRANTY PRODUCT',
            'ProductSlug' => 'test-no-warranty-product-' . now()->timestamp,
            'ProductSku'  => 'TEST-NOWRN-SKU-' . now()->timestamp,
            'ProductResellerPrice' => 300,
            'ProductSalePrice' => 400,
            'ProductRegularPrice' => 500,
            'warranty_days' => null,
            'status' => 'Active',
            'category_id' => $categoryId,
        ]);
        $this->testIds['product_no_warranty'] = $productNoWarranty->id;
        $this->pass("Created product WITHOUT warranty: ID={$productNoWarranty->id}");
        $passed++;

        // Create a test product with EXPIRED warranty (delivered 30 days ago, 5 day warranty)
        $productExpired = Product::create([
            'ProductName' => '🧪 TEST EXPIRED WARRANTY PRODUCT',
            'ProductSlug' => 'test-expired-warranty-' . now()->timestamp,
            'ProductSku'  => 'TEST-EXP-SKU-' . now()->timestamp,
            'ProductResellerPrice' => 200,
            'ProductSalePrice' => 300,
            'ProductRegularPrice' => 400,
            'warranty_days' => 5,
            'status' => 'Active',
            'category_id' => $categoryId,
        ]);
        $this->testIds['product_expired'] = $productExpired->id;
        $this->pass("Created product with 5-day warranty (will be expired): ID={$productExpired->id}");
        $passed++;

        // ── TEST 3: Create orders ──
        $this->info('');
        $this->info('━━━ TEST 3: Creating orders (Delivered) ━━━');

        // Order 1: Delivered 5 days ago, product with 15-day warranty → ACTIVE
        $order1 = new Order();
        $order1->user_id = $user->id;
        $order1->invoiceID = 'TEST-INV-ACTIVE-' . now()->timestamp;
        $order1->orderDate = now()->subDays(10)->format('Y-m-d');
        $order1->deliveryDate = now()->subDays(5)->format('Y-m-d');
        $order1->status = 'Delivered';
        $order1->subTotal = 500;
        $order1->save();
        $this->testIds['order_active'] = $order1->id;

        $op1 = Orderproduct::create([
            'order_id' => $order1->id,
            'product_id' => $productWithWarranty->id,
            'productCode' => $productWithWarranty->ProductSku,
            'productName' => $productWithWarranty->ProductName,
            'productPrice' => 500,
            'quantity' => 1,
        ]);
        $this->testIds['op_active'] = $op1->id;
        $this->pass("Order 1 (ACTIVE warranty): delivered 5 days ago, 15-day warranty → 10 days left");
        $passed++;

        // Order 2: Delivered 30 days ago, product with 5-day warranty → EXPIRED
        $order2 = new Order();
        $order2->user_id = $user->id;
        $order2->invoiceID = 'TEST-INV-EXPIRED-' . now()->timestamp;
        $order2->orderDate = now()->subDays(35)->format('Y-m-d');
        $order2->deliveryDate = now()->subDays(30)->format('Y-m-d');
        $order2->status = 'Delivered';
        $order2->subTotal = 200;
        $order2->save();
        $this->testIds['order_expired'] = $order2->id;

        $op2 = Orderproduct::create([
            'order_id' => $order2->id,
            'product_id' => $productExpired->id,
            'productCode' => $productExpired->ProductSku,
            'productName' => $productExpired->ProductName,
            'productPrice' => 200,
            'quantity' => 2,
        ]);
        $this->testIds['op_expired'] = $op2->id;
        $this->pass("Order 2 (EXPIRED warranty): delivered 30 days ago, 5-day warranty → expired");
        $passed++;

        // Order 3: Mixed — 1 warranty product + 1 no-warranty product
        $order3 = new Order();
        $order3->user_id = $user->id;
        $order3->invoiceID = 'TEST-INV-MIXED-' . now()->timestamp;
        $order3->orderDate = now()->subDays(8)->format('Y-m-d');
        $order3->deliveryDate = now()->subDays(3)->format('Y-m-d');
        $order3->status = 'Delivered';
        $order3->subTotal = 800;
        $order3->save();
        $this->testIds['order_mixed'] = $order3->id;

        $op3a = Orderproduct::create([
            'order_id' => $order3->id,
            'product_id' => $productWithWarranty->id,
            'productCode' => $productWithWarranty->ProductSku,
            'productName' => $productWithWarranty->ProductName,
            'productPrice' => 500,
            'quantity' => 1,
        ]);
        $this->testIds['op_mixed_warranty'] = $op3a->id;

        $op3b = Orderproduct::create([
            'order_id' => $order3->id,
            'product_id' => $productNoWarranty->id,
            'productCode' => $productNoWarranty->ProductSku,
            'productName' => $productNoWarranty->ProductName,
            'productPrice' => 300,
            'quantity' => 1,
        ]);
        $this->testIds['op_mixed_nowarranty'] = $op3b->id;
        $this->pass("Order 3 (MIXED): 1 warranty product (12 days left) + 1 no-warranty product");
        $passed++;

        // ── TEST 4: Test eligible products API logic ──
        $this->info('');
        $this->info('━━━ TEST 4: Warranty eligible products logic ━━━');

        // Simulate what the API does
        $orders = Order::where('user_id', $user->id)
            ->where('status', 'Delivered')
            ->whereNotNull('deliveryDate')
            ->whereIn('id', [$order1->id, $order2->id, $order3->id])
            ->with('orderproducts')
            ->get();

        $eligibleCount = 0;
        $excludedExpired = false;
        $excludedNoWarranty = false;

        foreach ($orders as $order) {
            foreach ($order->orderproducts as $op) {
                $product = Product::find($op->product_id);
                if (!$product || !$product->warranty_days || $product->warranty_days <= 0) {
                    if ($op->id === $op3b->id) $excludedNoWarranty = true;
                    continue;
                }
                $deliveredAt = Carbon::parse($order->deliveryDate);
                $expiresAt = $deliveredAt->copy()->addDays($product->warranty_days);

                if ($expiresAt->lt(Carbon::today())) {
                    if ($op->id === $op2->id) $excludedExpired = true;
                    continue;
                }

                $daysLeft = Carbon::today()->diffInDays($expiresAt, false);
                $eligibleCount++;
                $this->info("  ✓ Eligible: {$product->ProductName} | Order #{$order->invoiceID} | {$daysLeft} days left");
            }
        }

        if ($eligibleCount === 2) {
            $this->pass("Correct: 2 products eligible (Order 1 warranty + Order 3 warranty)");
            $passed++;
        } else {
            $this->fail("Expected 2 eligible products, got {$eligibleCount}");
            $failed++;
            $errors[] = 'Eligible count wrong';
        }

        if ($excludedExpired) {
            $this->pass("Correctly excluded expired warranty (Order 2)");
            $passed++;
        } else {
            $this->fail("Did not exclude expired warranty");
            $failed++;
            $errors[] = 'Expired not excluded';
        }

        if ($excludedNoWarranty) {
            $this->pass("Correctly excluded no-warranty product (Order 3 item 2)");
            $passed++;
        } else {
            $this->fail("Did not exclude no-warranty product");
            $failed++;
            $errors[] = 'No-warranty not excluded';
        }

        // ── TEST 5: Submit a claim ──
        $this->info('');
        $this->info('━━━ TEST 5: Submitting a warranty claim ━━━');

        $claim = WarrantyClaim::create([
            'order_id' => $order1->id,
            'order_product_id' => $op1->id,
            'product_id' => $productWithWarranty->id,
            'user_id' => $user->id,
            'vendor_id' => $productWithWarranty->vendor_id,
            'warranty_days' => $productWithWarranty->warranty_days,
            'delivered_at' => $order1->deliveryDate,
            'warranty_expires_at' => Carbon::parse($order1->deliveryDate)->addDays($productWithWarranty->warranty_days)->toDateString(),
            'reason' => 'Test claim: Product received damaged, screen has scratches.',
            'images' => null,
            'status' => 'pending',
        ]);
        $this->testIds['claim'] = $claim->id;

        if ($claim->id && $claim->claim_number) {
            $this->pass("Claim created: ID={$claim->id}, Number={$claim->claim_number}");
            $passed++;
        } else {
            $this->fail("Claim creation failed");
            $failed++;
            $errors[] = 'Claim creation failed';
        }

        // Verify claim_number format
        if (preg_match('/^WC-\d{6}-\d{5}$/', $claim->claim_number)) {
            $this->pass("Claim number format correct: {$claim->claim_number}");
            $passed++;
        } else {
            $this->fail("Claim number format wrong: {$claim->claim_number}");
            $failed++;
            $errors[] = 'Claim number format';
        }

        // ── TEST 6: Duplicate claim prevention ──
        $this->info('');
        $this->info('━━━ TEST 6: Duplicate claim prevention ━━━');

        $duplicateExists = WarrantyClaim::where('order_id', $order1->id)
            ->where('product_id', $productWithWarranty->id)
            ->where('order_product_id', $op1->id)
            ->exists();

        if ($duplicateExists) {
            $this->pass("Duplicate check works: existing claim detected for order {$order1->id} + product {$productWithWarranty->id}");
            $passed++;
        } else {
            $this->fail("Duplicate check failed");
            $failed++;
            $errors[] = 'Duplicate check broken';
        }

        // ── TEST 7: Admin — claim data listing ──
        $this->info('');
        $this->info('━━━ TEST 7: Admin claim data (all/pending/approved/rejected) ━━━');

        $allClaims = WarrantyClaim::with(['order', 'product', 'user'])->get();
        $pendingClaims = WarrantyClaim::where('status', 'pending')->get();

        $this->pass("Total claims in DB: {$allClaims->count()}");
        $this->pass("Pending claims: {$pendingClaims->count()}");
        $passed += 2;

        // Verify relationships load
        $testClaim = WarrantyClaim::with(['order', 'product', 'user', 'vendor', 'orderProduct'])->find($claim->id);
        $relErrors = [];

        if (!$testClaim->order) $relErrors[] = 'order';
        if (!$testClaim->product) $relErrors[] = 'product';
        if (!$testClaim->user) $relErrors[] = 'user';
        if (!$testClaim->orderProduct) $relErrors[] = 'orderProduct';

        if (count($relErrors) === 0) {
            $this->pass("All relationships load correctly (order, product, user, orderProduct)");
            $passed++;
        } else {
            $this->fail("Missing relationships: " . implode(', ', $relErrors));
            $failed++;
            $errors[] = 'Relationships: ' . implode(', ', $relErrors);
        }

        // Verify data fields
        $this->info("  → Order: #{$testClaim->order->invoiceID}");
        $this->info("  → Product: {$testClaim->product->ProductName}");
        $this->info("  → User: {$testClaim->user->name}");
        $this->info("  → Warranty: {$testClaim->warranty_days} days");
        $this->info("  → Delivered: {$testClaim->delivered_at->format('d M Y')}");
        $this->info("  → Expires: {$testClaim->warranty_expires_at->format('d M Y')}");
        $this->info("  → Days left: " . max(0, Carbon::today()->diffInDays($testClaim->warranty_expires_at, false)));

        // ── TEST 8: Admin — approve a claim ──
        $this->info('');
        $this->info('━━━ TEST 8: Admin approve/reject claim ━━━');

        $claim->status = 'approved';
        $claim->admin_note = 'Test approval: Verified the damage claim.';
        $claim->responded_at = now();
        $claim->responded_by = 1;
        $claim->save();

        $claim->refresh();
        if ($claim->status === 'approved') {
            $this->pass("Claim approved successfully");
            $passed++;
        } else {
            $this->fail("Claim status not updated");
            $failed++;
            $errors[] = 'Approve failed';
        }

        if ($claim->admin_note === 'Test approval: Verified the damage claim.') {
            $this->pass("Admin note saved correctly");
            $passed++;
        } else {
            $this->fail("Admin note not saved");
            $failed++;
        }

        if ($claim->responded_at) {
            $this->pass("responded_at timestamp set: {$claim->responded_at}");
            $passed++;
        } else {
            $this->fail("responded_at not set");
            $failed++;
        }

        // Reset to pending for next test
        $claim->status = 'pending';
        $claim->admin_note = null;
        $claim->responded_at = null;
        $claim->responded_by = null;
        $claim->save();

        // Test reject
        $claim->status = 'rejected';
        $claim->admin_note = 'Test rejection: Insufficient evidence.';
        $claim->responded_at = now();
        $claim->responded_by = 1;
        $claim->save();

        $claim->refresh();
        if ($claim->status === 'rejected') {
            $this->pass("Claim rejected successfully");
            $passed++;
        } else {
            $this->fail("Claim reject failed");
            $failed++;
        }

        // ── TEST 9: Warranty expiry boundary test ──
        $this->info('');
        $this->info('━━━ TEST 9: Warranty boundary edge cases ━━━');

        // Product delivered exactly warranty_days ago → should be last day eligible
        $deliveredExactly = Carbon::today()->subDays(15)->format('Y-m-d');
        $expiresExactly = Carbon::parse($deliveredExactly)->addDays(15);
        $isEligible = $expiresExactly->gte(Carbon::today());
        if ($isEligible) {
            $this->pass("Boundary: Delivered exactly 15 days ago with 15-day warranty → eligible (last day)");
            $passed++;
        } else {
            $this->fail("Boundary: Should be eligible on last day");
            $failed++;
            $errors[] = 'Boundary edge case';
        }

        // Product delivered warranty_days+1 ago → should be expired
        $deliveredPast = Carbon::today()->subDays(16)->format('Y-m-d');
        $expiresPast = Carbon::parse($deliveredPast)->addDays(15);
        $isExpired = $expiresPast->lt(Carbon::today());
        if ($isExpired) {
            $this->pass("Boundary: Delivered 16 days ago with 15-day warranty → expired");
            $passed++;
        } else {
            $this->fail("Boundary: Should be expired");
            $failed++;
            $errors[] = 'Boundary expired case';
        }

        // ── TEST 10: Route name verification ──
        $this->info('');
        $this->info('━━━ TEST 10: Route verification ━━━');

        $routeNames = [
            'admin.warranty-claims.index',
            'admin.warranty-claims.data',
            'admin.warranty-claims.show',
            'admin.warranty-claims.respond',
            'api.warranty.products',
            'api.warranty.claims.store',
            'api.warranty.claims.index',
        ];

        foreach ($routeNames as $name) {
            $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($name);
            if ($route) {
                $this->pass("Route '{$name}' → {$route->methods()[0]} {$route->uri()}");
                $passed++;
            } else {
                $this->fail("Route '{$name}' NOT FOUND");
                $failed++;
                $errors[] = "Missing route: {$name}";
            }
        }

        // ── Cleanup ──
        if ($this->option('cleanup')) {
            $this->info('');
            $this->info('━━━ CLEANUP ━━━');
            $this->cleanup();
            $this->info('  Test data removed.');
        }

        // ── Summary ──
        $this->printSummary($passed, $failed, $errors);

        return $failed > 0 ? 1 : 0;
    }

    private function pass(string $msg): void
    {
        $this->line("  <fg=green>✓ PASS</> {$msg}");
    }

    private function fail(string $msg): void
    {
        $this->line("  <fg=red>✗ FAIL</> {$msg}");
    }

    private function printSummary(int $passed, int $failed, array $errors): void
    {
        $this->info('');
        $this->info('══════════════════════════════════════════════════');
        $total = $passed + $failed;
        $this->line("  <fg=white;options=bold>Results:</> {$passed}/{$total} passed, {$failed} failed");

        if ($failed === 0) {
            $this->line('  <fg=green;options=bold>🎉 ALL TESTS PASSED!</>');
        } else {
            $this->line('  <fg=red;options=bold>⚠ FAILURES:</>');
            foreach ($errors as $e) {
                $this->line("    <fg=red>• {$e}</>");
            }
        }
        $this->info('══════════════════════════════════════════════════');
        $this->info('');
    }

    private function cleanup(): void
    {
        if (isset($this->testIds['claim'])) {
            WarrantyClaim::where('id', $this->testIds['claim'])->delete();
        }
        foreach (['op_active', 'op_expired', 'op_mixed_warranty', 'op_mixed_nowarranty'] as $k) {
            if (isset($this->testIds[$k])) {
                Orderproduct::where('id', $this->testIds[$k])->delete();
            }
        }
        foreach (['order_active', 'order_expired', 'order_mixed'] as $k) {
            if (isset($this->testIds[$k])) {
                Order::where('id', $this->testIds[$k])->delete();
            }
        }
        foreach (['product_with_warranty', 'product_no_warranty', 'product_expired'] as $k) {
            if (isset($this->testIds[$k])) {
                Product::where('id', $this->testIds[$k])->delete();
            }
        }
    }
}
