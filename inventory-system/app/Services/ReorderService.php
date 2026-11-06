<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Mail\LowStockAlert;
use App\Notifications\LowStockNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;

class ReorderService
{
    private const USAGE_LOOKBACK_DAYS = 90;

    public function calculateReorderPoint(Product $product): int
    {
        $usage = array_key_exists('recent_usage', $product->getAttributes())
            ? (int) $product->getAttribute('recent_usage')
            : $product->inventoryTransactions()
                ->where('type', 'stock_out')
                ->where('created_at', '>=', now()->subDays(self::USAGE_LOOKBACK_DAYS))
                ->sum(DB::raw('ABS(quantity)'));
        $averageDailyUsage = $usage / self::USAGE_LOOKBACK_DAYS;
        $leadTime = (int) ($product->supplierRecord?->lead_time_days ?? 0);

        return (int) ceil(($averageDailyUsage * $leadTime) + $product->reorder_level);
    }

    public function calculateOptimalOrderQuantity(Product $product): int
    {
        $supplier = $product->supplierRecord;
        $outstanding = array_key_exists('outstanding_order_quantity', $product->getAttributes())
            ? (int) $product->getAttribute('outstanding_order_quantity')
            : (int) PurchaseOrderItem::query()
                ->where('product_id', $product->getKey())
                ->whereHas('purchaseOrder', fn ($query) => $query->whereIn('status', ['draft', 'pending', 'approved', 'sent']))
                ->selectRaw('COALESCE(SUM(quantity_ordered - received_quantity), 0) as quantity')
                ->value('quantity');
        $shortfall = max(0, $this->calculateReorderPoint($product) - $product->quantity - (int) $outstanding);
        if ((int) $outstanding > 0 && $shortfall === 0) {
            return 0;
        }
        $minimum = max(1, (int) ($supplier?->minimum_order_quantity ?? 1));
        $packageSize = max(1, (int) ($product->package_size ?? 1));
        $quantity = max($minimum, $shortfall);

        return (int) (ceil($quantity / $packageSize) * $packageSize);
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProductsNeedingReorder(): Collection
    {
        $products = Product::query()
            ->where('is_active', true)
            ->whereNotNull('supplier_id')
            ->whereHas('supplierRecord', fn ($query) => $query->active())
            ->with('supplierRecord')
            ->withSum(['inventoryTransactions as recent_usage' => fn ($query) => $query
                ->where('type', 'stock_out')
                ->where('created_at', '>=', now()->subDays(self::USAGE_LOOKBACK_DAYS))], 'quantity')
            ->get();
        $outstanding = $this->outstandingByProduct($products->modelKeys());
        $products->each(fn (Product $product) => $product->setAttribute(
            'outstanding_order_quantity',
            $outstanding[$product->getKey()] ?? 0,
        ));

        return $products->filter(function (Product $product) use ($outstanding): bool {
            $ordered = (int) ($outstanding[$product->getKey()] ?? 0);
            $reorderPoint = $this->calculateReorderPoint($product);

            return $product->quantity <= $reorderPoint
                && ($ordered === 0 || $product->quantity + $ordered < $reorderPoint);
        })->values();
    }

    /**
     * @param  iterable<int, Product>  $products
     * @return \Illuminate\Support\Collection<int|string, \Illuminate\Support\Collection<int, Product>>
     */
    public function groupProductsBySupplier(iterable $products): \Illuminate\Support\Collection
    {
        return collect($products)->groupBy('supplier_id');
    }

    /**
     * @return array{orders: int, references: array<int, string>, products: int}
     */
    public function generatePurchaseOrders(): array
    {
        $created = [];
        $productCount = 0;
        $createdProductIds = [];
        $creatorId = User::query()->where('is_admin', true)->value('id')
            ?? User::query()->orderBy('id')->value('id');
        if (! $creatorId) {
            return ['orders' => 0, 'references' => [], 'products' => 0];
        }

        DB::transaction(function () use (&$created, &$productCount, &$createdProductIds, $creatorId): void {
            $products = Product::query()
                ->where('is_active', true)
                ->whereNotNull('supplier_id')
                ->whereHas('supplierRecord', fn ($query) => $query->active())
                ->with('supplierRecord')
                ->withSum(['inventoryTransactions as recent_usage' => fn ($query) => $query
                    ->where('type', 'stock_out')
                    ->where('created_at', '>=', now()->subDays(self::USAGE_LOOKBACK_DAYS))], 'quantity')
                ->lockForUpdate()
                ->get();
            $outstanding = $this->outstandingByProduct($products->modelKeys());
            $products->each(fn (Product $product) => $product->setAttribute(
                'outstanding_order_quantity',
                $outstanding[$product->getKey()] ?? 0,
            ));
            $needingReorder = $products->filter(function (Product $product) use ($outstanding): bool {
                $ordered = (int) ($outstanding[$product->getKey()] ?? 0);
                $reorderPoint = $this->calculateReorderPoint($product);

                return $product->quantity <= $reorderPoint
                    && ($ordered === 0 || $product->quantity + $ordered < $reorderPoint);
            });

            foreach ($this->groupProductsBySupplier($needingReorder) as $supplierId => $supplierProducts) {
                $supplier = Supplier::active()->find($supplierId);
                if (! $supplier || $supplierProducts->isEmpty()) {
                    continue;
                }

                $order = PurchaseOrder::query()->create([
                    'reference_number' => PurchaseOrder::generateReferenceNumber(),
                    'supplier_id' => $supplier->getKey(),
                    'status' => 'draft',
                    'order_date' => now()->toDateString(),
                    'expected_delivery_date' => now()->addDays($supplier->lead_time_days)->toDateString(),
                    'created_by' => $creatorId,
                    'notes' => 'Automatically generated from reorder point checks; approval is required.',
                ]);

                $subtotal = 0;
                foreach ($supplierProducts as $product) {
                    $quantity = $this->calculateOptimalOrderQuantity($product);
                    $price = (float) $product->unit_price;
                    $order->items()->create([
                        'product_id' => $product->getKey(),
                        'quantity_ordered' => $quantity,
                        'unit_price' => $price,
                    ]);
                    $subtotal += $quantity * $price;
                    $productCount++;
                    $createdProductIds[] = $product->getKey();
                }
                $order->update(['subtotal' => $subtotal, 'total_amount' => $subtotal]);
                $created[] = $order->reference_number;
            }
        }, attempts: 3);

        if ($productCount > 0) {
            Cache::forget('supplier.performance');
            $products = Product::query()->whereIn('id', array_unique($createdProductIds))->get();
            $administrators = User::query()->where(fn ($query) => $query
                ->where('is_admin', true)
                ->orWhere('role', 'admin'))->get();
            foreach ($products as $product) {
                if ($administrators->isNotEmpty()) {
                    Notification::send($administrators, new LowStockNotification($product));
                    if (config('mail.default') === 'smtp'
                        && filled(config('mail.mailers.smtp.host'))
                        && filled(config('mail.from.address'))) {
                        foreach ($administrators as $administrator) {
                            Mail::to($administrator->email)->queue(new LowStockAlert($product));
                        }
                    }
                }
            }
        }

        return ['orders' => count($created), 'references' => $created, 'products' => $productCount];
    }

    /**
     * @param  array<int, int|string>  $productIds
     * @return array<int|string, int>
     */
    private function outstandingByProduct(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        return DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->whereIn('purchase_order_items.product_id', $productIds)
            ->whereNull('purchase_orders.deleted_at')
            ->whereIn('purchase_orders.status', ['draft', 'pending', 'approved', 'sent'])
            ->groupBy('purchase_order_items.product_id')
            ->selectRaw('purchase_order_items.product_id, SUM(purchase_order_items.quantity_ordered - purchase_order_items.received_quantity) as quantity')
            ->pluck('quantity', 'product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();
    }
}
