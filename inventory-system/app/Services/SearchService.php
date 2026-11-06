<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    public function products(array $filters): Builder
    {
        return Product::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('supplier', 'like', "%{$search}%")
                        ->orWhereHas('supplierRecord', fn (Builder $supplier) => $supplier->where('company_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['supplier_id'] ?? null, fn (Builder $query, int $supplierId) => $query->where('supplier_id', $supplierId))
            ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category', $category));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<PurchaseOrder>
     */
    public function purchaseOrders(array $filters): Builder
    {
        return PurchaseOrder::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('company_name', 'like', "%{$search}%")));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->byStatus($status))
            ->when($filters['supplier_id'] ?? null, fn (Builder $query, int $id) => $query->where('supplier_id', $id))
            ->when($filters['start_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('order_date', '>=', $date))
            ->when($filters['end_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('order_date', '<=', $date));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<InventoryTransaction>
     */
    public function transactions(array $filters): Builder
    {
        return InventoryTransaction::query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->whereHas('product', fn (Builder $product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%")));
            })
            ->when($filters['product_id'] ?? null, fn (Builder $query, int $id) => $query->where('product_id', $id))
            ->when($filters['user_id'] ?? null, fn (Builder $query, int $id) => $query->where('user_id', $id))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['start_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['end_date'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }
}
