@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title h2 mb-1">{{ $product->name }}</h1>
            <p class="text-muted mb-0">{{ $product->sku }} · {{ $product->category }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i>Edit Product</a>
            <a href="{{ route('inventory-adjustments.create', ['product_id' => $product->id]) }}" class="btn btn-outline-success">Adjust stock</a>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Back to Products</a>
        </div>
    </div>

    <section class="card dashboard-card mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-sm-6 col-lg-3"><span class="text-muted d-block">Quantity</span><strong class="fs-4">{{ $product->quantity }}</strong></div>
                <div class="col-sm-6 col-lg-3"><span class="text-muted d-block">Reorder Level</span><strong class="fs-4">{{ $product->reorder_level }}</strong></div>
                <div class="col-sm-6 col-lg-3"><span class="text-muted d-block">Unit Price</span><strong class="fs-4">₱{{ number_format((float) $product->unit_price, 2) }}</strong></div>
                <div class="col-sm-6 col-lg-3">
                    <span class="text-muted d-block">Stock Status</span>
                    @if ($product->isOutOfStock())
                        <span class="stock-status stock-status-out">Out of Stock</span>
                    @elseif ($product->isLowStock())
                        <span class="stock-status stock-status-low">Low Stock</span>
                    @else
                        <span class="stock-status stock-status-in-stock">In Stock</span>
                    @endif
                </div>
                <div class="col-md-6"><span class="text-muted d-block">Supplier</span><strong>{{ $product->supplierRecord?->company_name ?? $product->supplier ?: 'Not specified' }}</strong></div>
                <div class="col-12"><span class="text-muted d-block">Description</span><p class="mb-0">{{ $product->description ?: 'No description provided.' }}</p></div>
            </div>
        </div>
    </section>

    <section class="card dashboard-card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0">Stock Movement History</h2>
                <form method="GET" class="d-flex flex-wrap gap-2">
                    <input type="date" name="start_date" aria-label="Start date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                    <input type="date" name="end_date" aria-label="End date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Filter</button>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('products.show', $product) }}">Clear</a>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Date</th><th>Type</th><th>Quantity moved</th><th>Running balance</th><th>Reference</th><th>Recorded by</th></tr></thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                                <td><span class="badge {{ $transaction->type === 'stock_in' ? 'text-bg-success' : ($transaction->type === 'stock_out' ? 'text-bg-warning' : 'text-bg-info') }}">{{ str_replace('_', ' ', ucfirst($transaction->type)) }}</span></td>
                                <td>{{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ $transaction->signedQuantity() }}</td>
                                <td>{{ $transaction->running_balance }}</td>
                                <td>{{ $transaction->reference_number ?: '—' }}</td>
                                <td>{{ $transaction->user?->name ?? 'Deleted user' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No stock movements have been recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $transactions->links() }}
        </div>
    </section>
@endsection
