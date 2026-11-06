<h1>Products Report</h1>
<table width="100%" border="1" cellspacing="0" cellpadding="5"><thead><tr><th>Name</th><th>SKU</th><th>Category</th><th>Quantity</th><th>Reorder</th><th>Unit Price</th><th>Supplier</th></tr></thead><tbody>
@foreach($rows as $product)<tr><td>{{ $product->name }}</td><td>{{ $product->sku }}</td><td>{{ $product->category }}</td><td>{{ $product->quantity }}</td><td>{{ $product->reorder_level }}</td><td>{{ $product->unit_price }}</td><td>{{ $product->supplierRecord?->company_name ?? $product->supplier }}</td></tr>@endforeach
</tbody></table>
