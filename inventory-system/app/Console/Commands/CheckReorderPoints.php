<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Services\ReorderService;
use Illuminate\Support\Facades\Log;

#[Signature('inventory:check-reorder')]
#[Description('Create draft purchase orders for products that need replenishment')]
class CheckReorderPoints extends Command
{
    public function handle(ReorderService $reorderService): int
    {
        try {
            $result = $reorderService->generatePurchaseOrders();
            $this->info("Created {$result['orders']} draft purchase order(s) for {$result['products']} product(s).");
            foreach ($result['references'] as $reference) {
                $this->line($reference);
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            Log::error('Automatic inventory reorder check failed.', ['exception' => $exception]);
            $this->error('Reorder check failed. See the application log for details.');

            return self::FAILURE;
        }
    }
}
