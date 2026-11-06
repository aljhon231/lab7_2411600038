<h1>Purchase order {{ $purchaseOrder->reference_number }}</h1>
<p>Please review the purchase order from {{ config('app.name') }}.</p>
<ul>
    @foreach ($purchaseOrder->items as $item)
        <li>{{ $item->product?->name }} — {{ $item->quantity_ordered }} × ₱{{ number_format((float) $item->unit_price, 2) }}</li>
    @endforeach
</ul>
<p>Total: ₱{{ number_format((float) $purchaseOrder->total_amount, 2) }}</p>
