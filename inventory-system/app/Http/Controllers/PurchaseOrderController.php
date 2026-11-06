<?php

namespace App\Http\Controllers;

use App\Mail\PurchaseOrderApproved;
use App\Mail\PurchaseOrderCreated;
use App\Mail\PurchaseOrderSent;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Notifications\PurchaseOrderStatusNotification;
use App\Services\SearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function index(Request $request, SearchService $searchService): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', PurchaseOrder::STATUSES)],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $orders = $searchService->purchaseOrders($filters)
            ->with(['supplier', 'createdBy'])
            ->latest('order_date')
            ->paginate(10)
            ->withQueryString();
        $suppliers = Supplier::query()->orderBy('company_name')->get(['id', 'company_name']);

        return view('purchase-orders.index', compact('orders', 'suppliers'));
    }

    public function create(): View
    {
        $this->authorize('create', PurchaseOrder::class);
        $suppliers = Supplier::active()->orderBy('company_name')->get();
        $products = Product::query()->with('supplierRecord')->orderBy('name')->get();

        return view('purchase-orders.create', compact('suppliers', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', PurchaseOrder::class);
        $validated = $request->validate($this->validationRules());
        $order = DB::transaction(function () use ($request, $validated): PurchaseOrder {
            $supplier = Supplier::active()->findOrFail($validated['supplier_id']);
            $order = PurchaseOrder::query()->create([
                'reference_number' => PurchaseOrder::generateReferenceNumber(),
                'supplier_id' => $supplier->getKey(),
                'status' => 'pending',
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'tax' => $validated['tax'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? $supplier->payment_terms,
                'created_by' => $request->user()->getKey(),
            ]);

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $order->items()->create([
                    'product_id' => $product->getKey(),
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price' => $item['unit_price'] ?? $product->unit_price,
                ]);
                $subtotal += $item['quantity_ordered'] * (float) ($item['unit_price'] ?? $product->unit_price);
            }

            $order->update([
                'subtotal' => round($subtotal, 2),
                'total_amount' => round($subtotal + (float) $order->tax, 2),
            ]);

            return $order->load(['supplier', 'items.product', 'createdBy']);
        });

        $this->sendMailWhenConfigured(
            $order->createdBy?->email,
            new PurchaseOrderCreated($order),
        );
        Cache::forget('supplier.performance');

        return redirect()
            ->route('purchase-orders.show', $order)
            ->with('success', 'Purchase order created and submitted for approval.');
    }

    public function show(PurchaseOrder $purchase_order): View
    {
        $this->authorize('view', $purchase_order);
        $purchase_order->load(['supplier', 'items.product', 'createdBy', 'approvedBy']);

        return view('purchase-orders.show', ['order' => $purchase_order]);
    }

    public function edit(PurchaseOrder $purchase_order): View
    {
        $this->authorize('update', $purchase_order);
        $purchase_order->load('items.product');
        $suppliers = Supplier::active()->orderBy('company_name')->get();
        $products = Product::query()->with('supplierRecord')->orderBy('name')->get();

        return view('purchase-orders.edit', ['order' => $purchase_order, 'suppliers' => $suppliers, 'products' => $products]);
    }

    public function update(Request $request, PurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('update', $purchase_order);
        $validated = $request->validate($this->validationRules($purchase_order));

        DB::transaction(function () use ($validated, $purchase_order): void {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchase_order->getKey());
            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['order' => 'Only draft orders can be edited.']);
            }

            Supplier::active()->findOrFail($validated['supplier_id']);
            $order->update([
                'supplier_id' => $validated['supplier_id'],
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'tax' => $validated['tax'] ?? 0,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
            ]);
            $order->items()->delete();
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::query()->findOrFail($item['product_id']);
                $price = (float) ($item['unit_price'] ?? $product->unit_price);
                $order->items()->create([
                    'product_id' => $product->getKey(),
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price' => $price,
                ]);
                $subtotal += $item['quantity_ordered'] * $price;
            }
            $order->update([
                'subtotal' => round($subtotal, 2),
                'total_amount' => round($subtotal + (float) $order->tax, 2),
            ]);
        });
        Cache::forget('supplier.performance');

        return redirect()->route('purchase-orders.show', $purchase_order)->with('success', 'Draft purchase order updated.');
    }

    public function destroy(PurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('delete', $purchase_order);
        $purchase_order->delete();
        Cache::forget('supplier.performance');

        return redirect()->route('purchase-orders.index')->with('success', 'Draft purchase order archived.');
    }

    public function approve(PurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('approve', $purchase_order);
        DB::transaction(function () use ($purchase_order): void {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchase_order->getKey());
            if (! $order->canBeApproved()) {
                throw ValidationException::withMessages(['order' => 'This purchase order is no longer awaiting approval.']);
            }
            $order->update([
                'status' => 'approved',
                'approved_by' => request()->user()->getKey(),
                'approved_at' => now(),
            ]);
        });

        $purchase_order->refresh()->load(['supplier', 'items.product', 'createdBy', 'approvedBy']);
        if ($purchase_order->createdBy) {
            $purchase_order->createdBy->notify(new PurchaseOrderStatusNotification(
                $purchase_order,
                'Purchase order '.$purchase_order->reference_number.' was approved.',
            ));
        }
        $this->sendMailWhenConfigured($purchase_order->createdBy?->email, new PurchaseOrderApproved($purchase_order));
        Cache::forget('supplier.performance');

        return back()->with('success', 'Purchase order approved.');
    }

    public function reject(Request $request, PurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('reject', $purchase_order);
        $validated = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($purchase_order, $validated): void {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchase_order->getKey());
            if (! $order->canBeApproved()) {
                throw ValidationException::withMessages(['order' => 'This purchase order is no longer awaiting approval.']);
            }
            $order->update(['status' => 'rejected', 'rejection_reason' => $validated['rejection_reason']]);
        });

        $purchase_order->load('createdBy');
        if ($purchase_order->createdBy) {
            $purchase_order->createdBy->notify(new PurchaseOrderStatusNotification(
                $purchase_order,
                'Purchase order '.$purchase_order->reference_number.' was rejected.',
            ));
        }

        return back()->with('success', 'Purchase order rejected.');
    }

    public function send(PurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('send', $purchase_order);
        DB::transaction(function () use ($purchase_order): void {
            $order = PurchaseOrder::query()->lockForUpdate()->findOrFail($purchase_order->getKey());
            if ($order->status !== 'approved') {
                throw ValidationException::withMessages(['order' => 'Only approved orders can be sent.']);
            }
            $order->update(['status' => 'sent']);
        });
        $purchase_order->refresh();
        $purchase_order->load(['supplier', 'items.product', 'createdBy', 'approvedBy']);
        $this->sendMailWhenConfigured($purchase_order->supplier?->email, new PurchaseOrderSent($purchase_order));
        Cache::forget('supplier.performance');

        return back()->with('success', 'Purchase order marked as sent.');
    }

    public function receive(Request $request, PurchaseOrder $purchase_order): RedirectResponse
    {
        $this->authorize('receive', $purchase_order);
        $validated = $request->validate([
            'received' => ['required', 'array', 'min:1'],
            'received.*' => ['required', 'integer', 'min:0'],
        ]);
        if (array_sum($validated['received']) < 1) {
            throw ValidationException::withMessages(['received' => 'Enter at least one unit received.']);
        }

        DB::transaction(function () use ($request, $purchase_order, $validated): void {
            $order = PurchaseOrder::query()->lockForUpdate()->with('items')->findOrFail($purchase_order->getKey());
            if (! $order->canBeReceived()) {
                throw ValidationException::withMessages(['order' => 'This order cannot receive any more items.']);
            }

            foreach ($order->items as $item) {
                $quantity = (int) ($validated['received'][$item->getKey()] ?? 0);
                if ($quantity === 0) {
                    continue;
                }
                if ($quantity > $item->outstanding_quantity) {
                    throw ValidationException::withMessages([
                        'received.'.$item->getKey() => 'Received quantity cannot exceed the outstanding quantity.',
                    ]);
                }

                $item->increment('received_quantity', $quantity);
                $item->product->inventoryTransactions()->create([
                    'type' => 'stock_in',
                    'quantity' => $quantity,
                    'reference_document' => $order->reference_number,
                    'reference_number' => $order->reference_number,
                    'notes' => 'Purchase order receipt.',
                    'user_id' => $request->user()->getKey(),
                ]);
            }

            $order->refresh()->load('items');
            if ($order->items->every(fn (PurchaseOrderItem $item) => $item->received_quantity >= $item->quantity_ordered)) {
                $order->update(['status' => 'received', 'actual_delivery_date' => now()->toDateString()]);
            }
        });
        Cache::forget('supplier.performance');

        return back()->with('success', 'Received quantities recorded and inventory transactions created.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function validationRules(?PurchaseOrder $order = null): array
    {
        return [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')->where('is_active', true)],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'tax' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'terms' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    private function sendMailWhenConfigured(?string $recipient, object $mail): void
    {
        if (! $recipient || config('mail.default') !== 'smtp'
            || ! filled(config('mail.mailers.smtp.host'))
            || ! filled(config('mail.from.address'))) {
            return;
        }

        Mail::to($recipient)->queue($mail);
    }
}
