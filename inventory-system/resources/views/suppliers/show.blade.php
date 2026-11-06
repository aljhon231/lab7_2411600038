@extends('layouts.app')

@section('title', $supplier->company_name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title h2 mb-1">{{ $supplier->company_name }}</h1>
            <p class="text-muted mb-0">{{ $supplier->full_contact ?: 'No contact details provided.' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit Supplier</a>
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Back to Suppliers</a>
        </div>
    </div>

    <section class="card dashboard-card mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-sm-6 col-lg-3"><span class="text-muted d-block">Status</span><span class="badge {{ $supplier->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span></div>
                <div class="col-sm-6 col-lg-3"><span class="text-muted d-block">Lead time</span><strong>{{ $supplier->lead_time_days }} days</strong></div>
                <div class="col-sm-6 col-lg-3"><span class="text-muted d-block">Minimum order</span><strong>₱{{ number_format((float) $supplier->minimum_order_amount, 2) }}</strong></div>
                <div class="col-sm-6 col-lg-3"><span class="text-muted d-block">On-time delivery</span><strong>{{ $supplier->performance_rating === null ? 'Not rated yet' : number_format($supplier->performance_rating, 2).'%' }}</strong></div>
                <div class="col-md-6"><span class="text-muted d-block">Address</span><p class="mb-0">{{ $supplier->address ?: 'Not provided.' }}</p></div>
                <div class="col-md-6"><span class="text-muted d-block">Payment terms</span><p class="mb-0">{{ $supplier->payment_terms ?: 'Not specified.' }}</p></div>
            </div>
        </div>
    </section>

    <div class="row g-4">
        <div class="col-lg-6">
            <section class="card dashboard-card h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Products supplied</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Product</th><th>SKU</th><th>Quantity</th></tr></thead>
                            <tbody>
                                @forelse ($supplier->products as $product)
                                    <tr>
                                        <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                                        <td>{{ $product->sku }}</td>
                                        <td>{{ $product->quantity }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No linked products.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="card dashboard-card h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Recent purchase orders</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Reference</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($recentPurchaseOrders as $purchaseOrder)
                                    <tr>
                                        <td>{{ $purchaseOrder->reference_number }}</td>
                                        <td>{{ $purchaseOrder->order_date?->format('M d, Y') ?? '—' }}</td>
                                        <td><span class="badge text-bg-secondary">{{ ucfirst($purchaseOrder->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No purchase orders recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
