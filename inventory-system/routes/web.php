<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryAdjustmentController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProcurementController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : view('auth.login'));

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('suppliers', SupplierController::class);
    Route::post('suppliers/{supplier}/restore', [SupplierController::class, 'restore'])
        ->middleware('admin')
        ->name('suppliers.restore');
    Route::get('suppliers-performance', [ProcurementController::class, 'performance'])->name('suppliers.performance');
    Route::get('reorder-alerts', [ProcurementController::class, 'reorderAlerts'])->name('reorder-alerts.index');
    Route::post('reorder-alerts/run', [ProcurementController::class, 'runReorderCheck'])
        ->middleware('admin')
        ->name('reorder-alerts.run');
    Route::get('procurement/export', [ProcurementController::class, 'export'])->name('procurement.export');
    Route::get('procurement/export/pdf', [ProcurementController::class, 'exportPdf'])->name('procurement.export.pdf');
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchase_order}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('purchase-orders/{purchase_order}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::resource('products', ProductController::class)->except('destroy');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware('admin')
        ->name('products.destroy');
    Route::resource('inventory-transactions', InventoryTransactionController::class)
        ->only(['index', 'create', 'store', 'show']);
    Route::delete('inventory-transactions/{inventory_transaction}', [InventoryTransactionController::class, 'destroy'])
        ->middleware('admin')
        ->name('inventory-transactions.destroy');
    Route::get('inventory-adjustments/create', [InventoryAdjustmentController::class, 'create'])->name('inventory-adjustments.create');
    Route::post('inventory-adjustments', [InventoryAdjustmentController::class, 'store'])->name('inventory-adjustments.store');
    Route::get('reports', [InventoryReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [InventoryReportController::class, 'export'])->name('reports.export');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
