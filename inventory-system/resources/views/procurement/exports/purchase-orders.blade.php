<h1>Purchase Orders Report</h1>
<table width="100%" border="1" cellspacing="0" cellpadding="5"><thead><tr><th>Reference</th><th>Supplier</th><th>Status</th><th>Order Date</th><th>Expected Delivery</th><th>Total</th></tr></thead><tbody>
@foreach($rows as $order)<tr><td>{{ $order->reference_number }}</td><td>{{ $order->supplier?->company_name }}</td><td>{{ $order->status }}</td><td>{{ $order->order_date?->toDateString() }}</td><td>{{ $order->expected_delivery_date?->toDateString() }}</td><td>{{ $order->total_amount }}</td></tr>@endforeach
</tbody></table>
