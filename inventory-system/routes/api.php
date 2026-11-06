<?php

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Services\ReorderService;
use App\Services\SupplierPerformanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard/stats', function () {
        return response()->json(['data' => [
            'total_products' => Product::query()->count(),
            'low_stock_items' => Product::query()->lowStock()->count(),
            'out_of_stock_items' => Product::query()->outOfStock()->count(),
            'inventory_value' => (float) Product::query()->sum(DB::raw('quantity * unit_price')),
        ]]);
    })->name('api.dashboard.stats');

    Route::get('/products/low-stock', function () {
        return response()->json(['data' => Product::query()->lowStock()->orderBy('quantity')->limit(20)
            ->get(['id', 'name', 'sku', 'quantity', 'reorder_level'])]);
    })->name('api.products.low-stock');

    Route::get('/products/{product}/stock', function (Product $product) {
        return response()->json(['data' => [
            'id' => $product->getKey(),
            'quantity' => $product->quantity,
            'unit' => 'units',
        ]]);
    })->name('api.products.stock');

    Route::get('/transactions/recent', function () {
        $transactions = InventoryTransaction::query()
            ->with(['product:id,name,sku', 'user:id,name'])
            ->recent()
            ->limit(10)
            ->get()
            ->map(fn (InventoryTransaction $transaction) => [
                'id' => $transaction->getKey(),
                'product_id' => $transaction->product_id,
                'product_name' => $transaction->product?->name ?? 'Deleted product',
                'type' => $transaction->type,
                'quantity' => $transaction->quantity,
                'signed_quantity' => $transaction->signedQuantity(),
                'user' => $transaction->user?->name ?? 'Deleted user',
                'created_at' => $transaction->created_at->toIso8601String(),
                'date' => $transaction->created_at->format('M d, Y h:i A'),
            ]);

        return response()->json(['data' => $transactions]);
    })->name('api.transactions.recent');

    Route::get('/dashboard/pending-orders', fn () => response()->json([
        'data' => ['count' => PurchaseOrder::pending()->count()],
    ]))->name('api.dashboard.pending-orders');

    Route::get('/dashboard/recent-orders', function () {
        return response()->json(['data' => PurchaseOrder::query()
            ->with('supplier:id,company_name')
            ->latest('order_date')
            ->limit(5)
            ->get(['id', 'reference_number', 'supplier_id', 'status', 'order_date', 'total_amount'])]);
    })->name('api.dashboard.recent-orders');

    Route::get('/dashboard/supplier-performance', fn (SupplierPerformanceService $service) => response()->json([
        'data' => collect($service->all())->map(fn (array $row) => [
            'supplier' => $row['supplier']->company_name,
            'total_orders' => $row['total_orders'],
            'on_time_percentage' => $row['on_time_percentage'],
            'order_accuracy' => $row['order_accuracy'],
        ]),
    ]))->name('api.dashboard.supplier-performance');

    Route::get('/dashboard/reorder-alerts', function (ReorderService $service) {
        return response()->json(['data' => $service->getProductsNeedingReorder()->map(fn (Product $product) => [
            'id' => $product->getKey(),
            'name' => $product->name,
            'quantity' => $product->quantity,
            'reorder_point' => $service->calculateReorderPoint($product),
            'suggested_quantity' => $service->calculateOptimalOrderQuantity($product),
            'supplier' => $product->supplierRecord?->company_name,
        ])]);
    })->name('api.dashboard.reorder-alerts');
});
