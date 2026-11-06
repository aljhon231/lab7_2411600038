<?php

namespace App\Providers;

use App\Models\PurchaseOrder;
use App\Services\ReorderService;
use App\Services\SupplierPerformanceService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('widgets.pending-orders', function ($view): void {
            $view->with('pendingOrderCount', PurchaseOrder::pending()->count());
        });

        View::composer('widgets.recent-orders', function ($view): void {
            $view->with('recentPurchaseOrders', PurchaseOrder::query()
                ->with('supplier')
                ->latest('order_date')
                ->limit(5)
                ->get());
        });

        View::composer('widgets.supplier-performance', function ($view): void {
            $view->with('topSuppliers', collect(app(SupplierPerformanceService::class)->all())
                ->filter(fn (array $row) => $row['on_time_percentage'] !== null)
                ->sortByDesc('on_time_percentage')
                ->take(5)
                ->map(fn (array $row) => [
                    'supplier' => $row['supplier'],
                    'performance_rating' => $row['on_time_percentage'],
                ]));
        });

        View::composer('widgets.reorder-alerts', function ($view): void {
            $view->with('reorderProducts', app(ReorderService::class)->getProductsNeedingReorder()->take(5));
        });
    }
}
