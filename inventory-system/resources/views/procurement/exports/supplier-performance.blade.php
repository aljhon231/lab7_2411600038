<h1>Supplier Performance Report</h1>
<table width="100%" border="1" cellspacing="0" cellpadding="5"><thead><tr><th>Supplier</th><th>Total Orders</th><th>On-time Orders</th><th>On-time %</th><th>Average Delivery Days</th><th>Accuracy %</th><th>Total Value</th></tr></thead><tbody>
@foreach($rows as $row)<tr><td>{{ $row['supplier']->company_name }}</td><td>{{ $row['total_orders'] }}</td><td>{{ $row['on_time_orders'] }}</td><td>{{ $row['on_time_percentage'] }}</td><td>{{ $row['average_delivery_days'] }}</td><td>{{ $row['order_accuracy'] }}</td><td>{{ $row['total_value'] }}</td></tr>@endforeach
</tbody></table>
