<?php

namespace App\Console\Commands;

use App\Models\VendorEarning;
use App\Models\Product;
use App\Services\VendorCommissionService;
use Illuminate\Console\Command;

class RecalculateVendorCommissions extends Command
{
    protected $signature = 'vendor:recalculate-commissions {--dry-run : Show changes without saving}';
    protected $description = 'Recalculate commission_amount for all VendorEarning records using admin-configured category rates';

    public function handle(VendorCommissionService $commissionService): int
    {
        $dryRun = $this->option('dry-run');
        $earnings = VendorEarning::with(['orderProduct.product'])->get();

        $this->info("Processing {$earnings->count()} VendorEarning records...");
        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        $changed = 0;
        $totalOld = 0;
        $totalNew = 0;

        foreach ($earnings as $earning) {
            $product = $earning->orderProduct?->product;
            if (!$product || !$product->vendor_id) {
                continue;
            }

            $rate = $commissionService->getRateForProduct($product->vendor_id, $product->category_id);
            $oldCommission = (float) $earning->commission_amount;
            $newCommission = round((float) $earning->net_amount * $rate / 100, 2);

            $totalOld += $oldCommission;
            $totalNew += $newCommission;

            if (abs($oldCommission - $newCommission) > 0.01) {
                $changed++;

                if ($this->getOutput()->isVerbose()) {
                    $this->line(
                        "  Earning #{$earning->id} (Order #{$earning->order_id}): " .
                        "commission {$oldCommission} → {$newCommission} " .
                        "(rate={$rate}%, net={$earning->net_amount})"
                    );
                }

                if (!$dryRun) {
                    $earning->commission_percent = $rate;
                    $earning->commission_amount = $newCommission;
                    $earning->save();
                }
            }
        }

        $this->newLine();
        $this->info("Results:");
        $this->line("  Total records:   {$earnings->count()}");
        $this->line("  Changed:         {$changed}");
        $this->line("  Old total comm:  " . number_format($totalOld, 2));
        $this->line("  New total comm:  " . number_format($totalNew, 2));
        $this->line("  Difference:      " . number_format($totalOld - $totalNew, 2));

        if ($dryRun) {
            $this->warn('Run without --dry-run to apply changes.');
        } else {
            $this->info('All commission amounts recalculated successfully.');
        }

        return 0;
    }
}
