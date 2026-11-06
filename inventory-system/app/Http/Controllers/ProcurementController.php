<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Services\ReorderService;
use App\Services\SearchService;
use App\Services\SupplierPerformanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcurementController extends Controller
{
    public function performance(SupplierPerformanceService $performance): View
    {
        $suppliers = collect($performance->all());
        $deliveryTrend = $suppliers
            ->flatMap(fn (array $row) => $row['delivery_trend'])
            ->groupBy('month')
            ->map(fn ($orders, $month) => [
                'month' => $month,
                'total' => $orders->sum('total'),
                'on_time' => $orders->sum('on_time'),
            ])
            ->values();

        return view('suppliers.performance', [
            'suppliers' => $suppliers,
            'topSuppliers' => $suppliers->whereNotNull('on_time_percentage')->sortByDesc('on_time_percentage')->take(5),
            'bottomSuppliers' => $suppliers->whereNotNull('on_time_percentage')->sortBy('on_time_percentage')->take(5),
            'deliveryTrend' => $deliveryTrend,
        ]);
    }

    public function reorderAlerts(ReorderService $reorderService): View
    {
        $products = $reorderService->getProductsNeedingReorder();

        return view('reorder-alerts.index', compact('products'));
    }

    public function runReorderCheck(ReorderService $reorderService): \Illuminate\Http\RedirectResponse
    {
        $result = $reorderService->generatePurchaseOrders();

        return redirect()->route('reorder-alerts.index')->with(
            'success',
            "Created {$result['orders']} draft purchase order(s) covering {$result['products']} product(s).",
        );
    }

    public function export(Request $request, SupplierPerformanceService $performance, SearchService $searchService): StreamedResponse
    {
        $validated = $request->validate([
            'report' => ['required', 'in:products,purchase-orders,transactions,supplier-performance'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:'.implode(',', PurchaseOrder::STATUSES)],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['nullable', 'in:stock_in,stock_out,adjustment'],
            'category' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $report = $validated['report'];

        return response()->streamDownload(function () use ($report, $performance, $searchService, $validated): void {
            $output = fopen('php://output', 'w');
            match ($report) {
                'products' => $this->writeProducts($output, $validated, $searchService),
                'purchase-orders' => $this->writePurchaseOrders($output, $validated, $searchService),
                'transactions' => $this->writeTransactions($output, $validated, $searchService),
                'supplier-performance' => $this->writeSupplierPerformance($output, $performance),
            };
            fclose($output);
        }, Str::slug($report).'-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request, SupplierPerformanceService $performance, SearchService $searchService)
    {
        $validated = $request->validate([
            'report' => ['required', 'in:products,purchase-orders,transactions,supplier-performance'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:'.implode(',', PurchaseOrder::STATUSES)],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['nullable', 'in:stock_in,stock_out,adjustment'],
            'category' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $report = $validated['report'];
        $rows = match ($report) {
            'products' => $searchService->products($validated)
                ->when($validated['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
                ->with('supplierRecord')->orderBy('name')->get(),
            'purchase-orders' => $searchService->purchaseOrders($validated)->with('supplier')->latest('order_date')->get(),
            'transactions' => $searchService->transactions($validated)->with(['product', 'user'])->latest()->get(),
            'supplier-performance' => collect($performance->all()),
        };

        return Pdf::loadView('procurement.exports.'.$report, compact('rows'))
            ->setPaper('a4', 'landscape')
            ->download(Str::slug($report).'-'.now()->format('Ymd').'.pdf');
    }

    /**
     * @param  resource  $output
     */
    private function writeProducts($output, array $filters, SearchService $searchService): void
    {
        fputcsv($output, ['Name', 'SKU', 'Category', 'Quantity', 'Reorder Level', 'Unit Price', 'Supplier']);
        $searchService->products($filters)
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->with('supplierRecord')
            ->orderBy('name')->chunk(200, function ($products) use ($output): void {
            foreach ($products as $product) {
                fputcsv($output, array_map($this->safeCsvValue(...), [$product->name, $product->sku, $product->category, $product->quantity, $product->reorder_level, $product->unit_price, $product->supplierRecord?->company_name ?? $product->supplier]));
            }
        });
    }

    /**
     * @param  resource  $output
     */
    private function writePurchaseOrders($output, array $filters, SearchService $searchService): void
    {
        fputcsv($output, ['Reference', 'Supplier', 'Status', 'Order Date', 'Expected Delivery', 'Total']);
        $searchService->purchaseOrders($filters)
            ->with('supplier')
            ->latest('order_date')->chunk(200, function ($orders) use ($output): void {
            foreach ($orders as $order) {
                fputcsv($output, array_map($this->safeCsvValue(...), [$order->reference_number, $order->supplier?->company_name, $order->status, $order->order_date?->toDateString(), $order->expected_delivery_date?->toDateString(), $order->total_amount]));
            }
        });
    }

    /**
     * @param  resource  $output
     */
    private function writeTransactions($output, array $filters, SearchService $searchService): void
    {
        fputcsv($output, ['Product', 'Type', 'Quantity', 'User', 'Date', 'Reference']);
        $searchService->transactions($filters)
            ->with(['product', 'user'])
            ->latest()->chunk(200, function ($rows) use ($output): void {
            foreach ($rows as $row) {
                fputcsv($output, array_map($this->safeCsvValue(...), [$row->product?->name, $row->type, $row->quantity, $row->user?->name, $row->created_at?->toDateTimeString(), $row->reference_number]));
            }
        });
    }

    /**
     * @param  resource  $output
     */
    private function writeSupplierPerformance($output, SupplierPerformanceService $performance): void
    {
        fputcsv($output, ['Supplier', 'Total Orders', 'On-time Orders', 'On-time %', 'Average Delivery Days', 'Order Accuracy %', 'Total Value']);
        foreach ($performance->all() as $row) {
            fputcsv($output, array_map($this->safeCsvValue(...), [$row['supplier']->company_name, $row['total_orders'], $row['on_time_orders'], $row['on_time_percentage'], $row['average_delivery_days'], $row['order_accuracy'], $row['total_value']]));
        }
    }

    private function safeCsvValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
