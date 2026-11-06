<h1>Purchase order submitted</h1>
<p>Purchase order {{ $purchaseOrder->reference_number }} has been submitted for review.</p>
<p>Supplier: {{ $purchaseOrder->supplier?->company_name }}</p>
<ul>
    @foreach ($purchaseOrder->items as $item)
        <li>{{ $item->product?->name }} — {{ $item->quantity_ordered }} × ₱{{ number_format((float) $item->unit_price, 2) }}</li>
    @endforeach
</ul>
<p>Total: ₱{{ number_format((float) $purchaseOrder->total_amount, 2) }}</p>
