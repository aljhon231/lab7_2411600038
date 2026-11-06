<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $statistics = [
            'totalProducts' => Product::count(),
            'lowStockItems' => Product::query()
                ->whereColumn('quantity', '<=', 'reorder_level')
                ->count(),
            'outOfStockItems' => Product::where('quantity', 0)->count(),
            'inventoryValue' => Product::query()
                ->sum(DB::raw('quantity * unit_price')),
        ];

        $recentProducts = Product::query()
            ->latest()
            ->take(5)
            ->get();
        $lowStockProducts = Product::query()
            ->lowStock()
            ->orderBy('quantity')
            ->limit(10)
            ->get();
        $recentTransactions = InventoryTransaction::query()
            ->with(['product', 'user'])
            ->recent()
            ->limit(10)
            ->get();

        $categorySummary = Product::query()
            ->selectRaw('category, COUNT(*) as product_count, SUM(quantity) as quantity, SUM(quantity * unit_price) as inventory_value')
            ->groupBy('category')
            ->orderBy('category')
            ->get();

        return view('dashboard', compact('statistics', 'recentProducts', 'lowStockProducts', 'recentTransactions', 'categorySummary'));
    }
}
