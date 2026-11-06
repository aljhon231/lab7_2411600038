@extends('layouts.app')

@section('title', 'Inventory Reports')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="page-title h2 mb-1">Inventory Reports</h1>
            <p class="text-muted mb-0">Review stock movement, reorder needs, and inventory value.</p>
        </div>
    </div>

    <section class="card dashboard-card mb-4"><div class="card-body">
        <form method="GET" action="{{ route('reports.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3"><label for="report-start" class="form-label">Start date</label><input id="report-start" class="form-control" type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"></div>
            <div class="col-md-3"><label for="report-end" class="form-label">End date</label><input id="report-end" class="form-control" type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"></div>
            <div class="col-md-3"><label for="report-category" class="form-label">Category</label>
                <select id="report-category" class="form-select" name="category"><option value="">All categories</option>
                    @foreach ($categories as $category)<option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-primary" type="submit">Apply filters</button><a class="btn btn-outline-secondary" href="{{ route('reports.index') }}">Clear</a></div>
        </form>
    </div></section>

    <section class="card dashboard-card mb-4"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Stock movement report</h2>
            <div class="d-flex gap-2"><a class="btn btn-sm btn-outline-success" href="{{ route('reports.export', [...request()->query(), 'report' => 'movements']) }}">Export CSV</a><a class="btn btn-sm btn-outline-danger" href="{{ route('procurement.export.pdf', ['report' => 'transactions', 'start_date' => $filters['start_date'] ?? null, 'end_date' => $filters['end_date'] ?? null]) }}">Export PDF</a></div>
        </div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Product</th><th>Type</th><th>Quantity</th><th>User</th><th>Date</th><th>Reference</th></tr></thead>
            <tbody>
                @forelse ($movements as $transaction)
                    <tr>
                        <td>{{ $transaction->product?->name ?? 'Deleted product' }}</td>
                        <td>{{ str_replace('_', ' ', ucfirst($transaction->type)) }}</td>
                        <td>{{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ $transaction->signedQuantity() }}</td>
                        <td>{{ $transaction->user?->name ?? 'Deleted user' }}</td>
                        <td>{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                        <td>{{ $transaction->reference_number ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No movements match these filters.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-3">{{ $movements->links() }}</div>
    </div></section>

    <div class="row g-4">
        <div class="col-lg-7">
            <section class="card dashboard-card h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Low stock report</h2>
                    <a class="btn btn-sm btn-outline-success" href="{{ route('reports.export', [...request()->query(), 'report' => 'low-stock']) }}">Export CSV</a>
                </div>
                <div class="table-responsive"><table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Product</th><th>SKU</th><th>Current</th><th>Reorder at</th><th>Supplier</th></tr></thead>
                    <tbody>
                        @forelse ($lowStockProducts as $product)
                            <tr><td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td><td>{{ $product->sku }}</td><td>{{ $product->quantity }}</td><td>{{ $product->reorder_level }}</td><td>{{ $product->supplier ?: '—' }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No low-stock products.</td></tr>
                        @endforelse
                    </tbody>
                </table></div>
            </div></section>
        </div>
        <div class="col-lg-5">
            <section class="card dashboard-card h-100"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0">Inventory valuation</h2>
                    <a class="btn btn-sm btn-outline-success" href="{{ route('reports.export', [...request()->query(), 'report' => 'valuation']) }}">Export CSV</a>
                </div>
                <p class="text-muted">Quantity × unit price for all products{{ ($filters['category'] ?? null) ? ' in '.$filters['category'] : '' }}.</p>
                <p class="fs-3 fw-bold mb-0">₱{{ number_format((float) $inventoryValue, 2) }}</p>
            </div></section>
        </div>
    </div>
@endsection
