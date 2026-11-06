<h1>Inventory Transactions Report</h1>
<table width="100%" border="1" cellspacing="0" cellpadding="5"><thead><tr><th>Product</th><th>SKU</th><th>Type</th><th>Quantity</th><th>User</th><th>Date</th><th>Reference</th></tr></thead><tbody>
@foreach($rows as $transaction)<tr><td>{{ $transaction->product?->name }}</td><td>{{ $transaction->product?->sku }}</td><td>{{ $transaction->type }}</td><td>{{ $transaction->quantity }}</td><td>{{ $transaction->user?->name }}</td><td>{{ $transaction->created_at?->toDateTimeString() }}</td><td>{{ $transaction->reference_number }}</td></tr>@endforeach
</tbody></table>
