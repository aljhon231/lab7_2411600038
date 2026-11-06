<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $movements = $this->movementQuery($filters)
            ->with(['product', 'user'])
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $lowStockProducts = Product::query()
            ->lowStock()
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category', $filters['category']))
            ->orderBy('quantity')
            ->get();
        $inventoryValue = Product::query()
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category', $filters['category']))
            ->sum(DB::raw('quantity * unit_price'));
        $categories = Product::query()->distinct()->orderBy('category')->pluck('category');

        return view('reports.index', compact('movements', 'lowStockProducts', 'inventoryValue', 'categories', 'filters'));
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'report' => ['required', 'in:movements,low-stock,valuation'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);

        $report = $filters['report'];

        return response()->streamDownload(function () use ($report, $filters): void {
            $output = fopen('php://output', 'w');

            if ($report === 'movements') {
                fputcsv($output, ['Product', 'Type', 'Quantity', 'User', 'Date', 'Reference']);
                $this->movementQuery($filters)->with(['product', 'user'])->chunk(200, function ($rows) use ($output): void {
                    foreach ($rows as $row) {
                        fputcsv($output, [
                            $row->product?->name ?? 'Deleted product',
                            $row->type,
                            $row->quantity,
                            $row->user?->name ?? 'Deleted user',
                            $row->created_at->toDateTimeString(),
                            $row->reference_number,
                        ]);
                    }
                });
            } elseif ($report === 'low-stock') {
                fputcsv($output, ['Product', 'SKU', 'Quantity', 'Reorder Level', 'Supplier']);
                Product::query()->lowStock()
                    ->when($filters['category'] ?? null, fn (Builder $query, $category) => $query->where('category', $category))
                    ->orderBy('quantity')
                    ->chunk(200, function ($rows) use ($output): void {
                        foreach ($rows as $product) {
                            fputcsv($output, [$product->name, $product->sku, $product->quantity, $product->reorder_level, $product->supplier]);
                        }
                    });
            } else {
                fputcsv($output, ['Product', 'SKU', 'Quantity', 'Unit Price', 'Inventory Value']);
                Product::query()
                    ->when($filters['category'] ?? null, fn (Builder $query, $category) => $query->where('category', $category))
                    ->orderBy('name')
                    ->chunk(200, function ($rows) use ($output): void {
                        foreach ($rows as $product) {
                            fputcsv($output, [
                                $product->name,
                                $product->sku,
                                $product->quantity,
                                $product->unit_price,
                                number_format($product->quantity * (float) $product->unit_price, 2, '.', ''),
                            ]);
                        }
                    });
            }

            fclose($output);
        }, $report.'-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<InventoryTransaction>
     */
    private function movementQuery(array $filters): Builder
    {
        return InventoryTransaction::query()
            ->when($filters['start_date'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['end_date'] ?? null, fn (Builder $query, $date) => $query->whereDate('created_at', '<=', $date))
            ->when($filters['category'] ?? null, fn (Builder $query, $category) => $query->whereHas('product', fn (Builder $products) => $products->where('category', $category)));
    }
}
