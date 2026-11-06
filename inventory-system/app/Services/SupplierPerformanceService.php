<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;

class SupplierPerformanceService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $metrics = Cache::remember('supplier.performance', now()->addMinutes(5), function (): array {
            $suppliers = Supplier::query()->with([
                'purchaseOrders' => fn ($query) => $query->with('items'),
            ])->orderBy('company_name')->get();

            return $suppliers->map(function (Supplier $supplier): array {
                $orders = $supplier->purchaseOrders;
                $completed = $orders->where('status', 'received');
                $measurable = $completed->filter(fn (PurchaseOrder $order) => $order->actual_delivery_date && $order->expected_delivery_date);
                $onTime = $measurable->filter(fn (PurchaseOrder $order) => $order->actual_delivery_date->lessThanOrEqualTo($order->expected_delivery_date))->count();
                $deliveryDays = $measurable->map(fn (PurchaseOrder $order) => $order->order_date->diffInDays($order->actual_delivery_date));
                $ordered = $completed->flatMap->items->sum('quantity_ordered');
                $received = $completed->flatMap->items->sum('received_quantity');
                $deliveryTrend = $measurable
                    ->groupBy(fn (PurchaseOrder $order) => $order->actual_delivery_date->format('Y-m'))
                    ->sortKeys()
                    ->map(fn ($monthOrders) => [
                        'month' => $monthOrders->first()->actual_delivery_date->format('M Y'),
                        'total' => $monthOrders->count(),
                        'on_time' => $monthOrders->filter(fn (PurchaseOrder $order) => $order->actual_delivery_date->lessThanOrEqualTo($order->expected_delivery_date))->count(),
                    ])
                    ->values()
                    ->all();

                return [
                    'supplier_id' => $supplier->getKey(),
                    'total_orders' => $orders->count(),
                    'completed_orders' => $completed->count(),
                    'on_time_orders' => $onTime,
                    'on_time_percentage' => $measurable->isEmpty() ? null : round($onTime / $measurable->count() * 100, 2),
                    'average_delivery_days' => $deliveryDays->isEmpty() ? null : round($deliveryDays->avg(), 2),
                    'order_accuracy' => $ordered === 0 ? null : round($received / $ordered * 100, 2),
                    'total_value' => round((float) $orders->sum('total_amount'), 2),
                    'delivery_trend' => $deliveryTrend,
                ];
            })->all();
        });

        $suppliers = Supplier::query()
            ->whereKey(array_column($metrics, 'supplier_id'))
            ->get()
            ->keyBy(fn (Supplier $supplier) => $supplier->getKey());

        return collect($metrics)
            ->map(function (array $row) use ($suppliers): ?array {
                $supplier = $suppliers->get($row['supplier_id']);
                if (! $supplier) {
                    return null;
                }

                unset($row['supplier_id']);
                $row['supplier'] = $supplier;

                return $row;
            })
            ->filter()
            ->values()
            ->all();
    }

    public function clearCache(): void
    {
        Cache::forget('supplier.performance');
    }
}
