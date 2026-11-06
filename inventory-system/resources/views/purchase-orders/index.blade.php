@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="page-title h2 mb-1">Purchase Orders</h1><p class="text-muted mb-0">Track supplier orders, approvals, and deliveries.</p></div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-success" href="{{ route('procurement.export', ['report' => 'purchase-orders', ...request()->query()]) }}">Export CSV</a>
            <a class="btn btn-outline-danger" href="{{ route('procurement.export.pdf', ['report' => 'purchase-orders', ...request()->query()]) }}">Export PDF</a>
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>New Purchase Order</a>
        </div>
    </div>
    <section class="card dashboard-card"><div class="card-body">
        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-3"><label for="search" class="form-label">Search</label><input id="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Reference or supplier"></div>
            <div class="col-md-2"><label for="status" class="form-label">Status</label><select id="status" name="status" class="form-select"><option value="">All statuses</option>@foreach(\App\Models\PurchaseOrder::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label for="supplier_id" class="form-label">Supplier</label><select id="supplier_id" name="supplier_id" class="form-select"><option value="">All suppliers</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)request('supplier_id') === (string)$supplier->id)>{{ $supplier->company_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label for="start_date" class="form-label">From</label><input id="start_date" type="date" name="start_date" value="{{ request('start_date') }}" class="form-control"></div>
            <div class="col-md-2"><label for="end_date" class="form-label">To</label><input id="end_date" type="date" name="end_date" value="{{ request('end_date') }}" class="form-control"></div>
            <div class="col-auto"><button class="btn btn-outline-success">Filter</button><a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Clear</a></div>
        </form>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Reference</th><th>Supplier</th><th>Order date</th><th>Expected delivery</th><th>Total</th><th>Status</th><th>Created by</th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td><a href="{{ route('purchase-orders.show', $order) }}">{{ $order->reference_number }}</a></td>
                        <td>{{ $order->supplier?->company_name ?? 'Archived supplier' }}</td>
                        <td>{{ $order->order_date?->format('M d, Y') }}</td>
                        <td>{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</td>
                        <td>₱{{ number_format((float)$order->total_amount, 2) }}</td>
                        <td><span class="badge text-bg-{{ $order->status === 'received' ? 'success' : (in_array($order->status, ['rejected','cancelled']) ? 'danger' : (in_array($order->status, ['pending','draft']) ? 'warning' : 'primary')) }}">{{ ucfirst($order->status) }}</span></td>
                        <td>{{ $order->createdBy?->name ?? 'Deleted user' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No purchase orders found.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $orders->links() }}</div>
    </div></section>
@endsection
