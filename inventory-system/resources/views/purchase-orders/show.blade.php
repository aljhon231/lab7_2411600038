@extends('layouts.app')

@section('title', $order->reference_number)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="page-title h2 mb-1">{{ $order->reference_number }}</h1><p class="text-muted mb-0">{{ $order->supplier?->company_name ?? 'Archived supplier' }} · {{ $order->order_date?->format('M d, Y') }}</p></div>
        <div class="d-flex flex-wrap gap-2">
            @can('update', $order)<a href="{{ route('purchase-orders.edit', $order) }}" class="btn btn-outline-primary">Edit draft</a>@endcan
            @can('approve', $order)<form method="POST" action="{{ route('purchase-orders.approve', $order) }}">@csrf<button class="btn btn-success">Approve</button></form>@endcan
            @can('reject', $order)<form method="POST" action="{{ route('purchase-orders.reject', $order) }}" class="d-flex gap-1">@csrf<input name="rejection_reason" class="form-control" placeholder="Reason required" required><button class="btn btn-outline-danger">Reject</button></form>@endcan
            @can('send', $order)<form method="POST" action="{{ route('purchase-orders.send', $order) }}">@csrf<button class="btn btn-primary">Mark as sent</button></form>@endcan
            @can('delete', $order)<form method="POST" action="{{ route('purchase-orders.destroy', $order) }}" onsubmit="return confirm('Archive this draft?')">@csrf @method('DELETE')<button class="btn btn-outline-danger">Archive</button></form>@endcan
        </div>
    </div>
    @if($order->rejection_reason)<div class="alert alert-warning">Rejection reason: {{ $order->rejection_reason }}</div>@endif
    <section class="card dashboard-card mb-4"><div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-sm-3"><span class="text-muted d-block">Status</span><span class="badge text-bg-primary">{{ ucfirst($order->status) }}</span></div>
            <div class="col-sm-3"><span class="text-muted d-block">Expected delivery</span>{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</div>
            <div class="col-sm-3"><span class="text-muted d-block">Created by</span>{{ $order->createdBy?->name ?? 'Deleted user' }}</div>
            <div class="col-sm-3"><span class="text-muted d-block">Approved by</span>{{ $order->approvedBy?->name ?? '—' }}</div>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle">
            <thead><tr><th>Product</th><th>Ordered</th><th>Received</th><th>Outstanding</th><th>Unit price</th><th>Line total</th></tr></thead>
            <tbody>@foreach($order->items as $item)<tr><td>{{ $item->product?->name ?? 'Deleted product' }}</td><td>{{ $item->quantity_ordered }}</td><td>{{ $item->received_quantity }}</td><td>{{ $item->outstanding_quantity }}</td><td>₱{{ number_format((float)$item->unit_price, 2) }}</td><td>₱{{ number_format($item->line_total, 2) }}</td></tr>@endforeach</tbody>
            <tfoot><tr><th colspan="5" class="text-end">Subtotal</th><td>₱{{ number_format((float)$order->subtotal, 2) }}</td></tr><tr><th colspan="5" class="text-end">Tax</th><td>₱{{ number_format((float)$order->tax, 2) }}</td></tr><tr><th colspan="5" class="text-end">Total</th><td class="fw-bold">₱{{ number_format((float)$order->total_amount, 2) }}</td></tr></tfoot>
        </table></div>
    </div></section>
    @can('receive', $order)
        <section class="card dashboard-card mb-4"><div class="card-body">
            <h2 class="h5">Receive delivery</h2>
            <p class="text-muted">Enter the number of new units received for this delivery. Partial deliveries are supported.</p>
            <form method="POST" action="{{ route('purchase-orders.receive', $order) }}">@csrf
                <div class="row g-3 align-items-end">@foreach($order->items as $item)@if($item->outstanding_quantity > 0)<div class="col-md-4"><label for="received-{{ $item->id }}" class="form-label">{{ $item->product?->name }} ({{ $item->outstanding_quantity }} outstanding)</label><input id="received-{{ $item->id }}" name="received[{{ $item->id }}]" type="number" min="0" max="{{ $item->outstanding_quantity }}" value="0" class="form-control"></div>@endif @endforeach</div>
                <button class="btn btn-success mt-3">Record receipt</button>
            </form>
        </div></section>
    @endcan
    <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Back to Purchase Orders</a>
@endsection
