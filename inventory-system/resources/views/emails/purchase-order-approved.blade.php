<h1>Purchase order approved</h1>
<p>Purchase order {{ $purchaseOrder->reference_number }} for {{ $purchaseOrder->supplier?->company_name }} has been approved.</p>
<p>Total: ₱{{ number_format((float) $purchaseOrder->total_amount, 2) }}</p>
