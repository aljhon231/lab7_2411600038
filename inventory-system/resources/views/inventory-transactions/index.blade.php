@extends('layouts.app')

@section('title', 'Inventory Transactions')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title h2 mb-1">Inventory Transactions</h1>
            <p class="text-muted mb-0">Review recorded stock receipts and issues.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory-adjustments.create') }}" class="btn btn-outline-success">Adjust stock</a>
            <a href="{{ route('inventory-transactions.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Record Stock Movement
            </a>
        </div>
    </div>

    <section class="card dashboard-card">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-3"><label class="form-label" for="filter-search">Search product or user</label><input id="filter-search" name="search" value="{{ request('search') }}" class="form-control"></div>
                <div class="col-md-3">
                    <label class="form-label" for="filter-product">Product</label>
                    <select class="form-select" id="filter-product" name="product_id">
                        <option value="">All products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filter-type">Type</label>
                    <select class="form-select" id="filter-type" name="type">
                        <option value="">All types</option>
                        @foreach (['stock_in' => 'Stock In', 'stock_out' => 'Stock Out', 'adjustment' => 'Adjustment'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filter-user">User</label>
                    <select class="form-select" id="filter-user" name="user_id"><option value="">All users</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected((string)request('user_id') === (string)$user->id)>{{ $user->name }}</option>@endforeach</select>
                </div>
                <div class="col-md-2"><label class="form-label" for="filter-start">From</label><input id="filter-start" class="form-control" type="date" name="start_date" value="{{ request('start_date') }}"></div>
                <div class="col-md-2"><label class="form-label" for="filter-end">To</label><input id="filter-end" class="form-control" type="date" name="end_date" value="{{ request('end_date') }}"></div>
                <div class="col-md-3 d-flex gap-2"><button class="btn btn-outline-primary" type="submit">Filter</button><a class="btn btn-outline-secondary" href="{{ route('inventory-transactions.index') }}">Clear</a><a class="btn btn-outline-success" href="{{ route('procurement.export', ['report' => 'transactions', ...request()->query()]) }}">CSV</a><a class="btn btn-outline-danger" href="{{ route('procurement.export.pdf', ['report' => 'transactions', ...request()->query()]) }}">PDF</a></div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Date and time</th><th>Product</th><th>Type</th><th>Quantity</th><th>Reference</th><th>Recorded by</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                                <td>@if ($transaction->product)<a href="{{ route('products.show', $transaction->product) }}">{{ $transaction->product->name }}</a><small class="d-block text-muted">{{ $transaction->product->sku }}</small>@else Deleted product @endif</td>
                                <td><span class="badge transaction-type {{ $transaction->type === 'stock_in' ? 'text-bg-success' : ($transaction->type === 'stock_out' ? 'text-bg-warning' : 'text-bg-info') }}">{{ str_replace('_', ' ', ucfirst($transaction->type)) }}</span></td>
                                <td>{{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ $transaction->signedQuantity() }}</td>
                                <td>{{ $transaction->reference_number ?: '—' }}</td>
                                <td>{{ $transaction->user?->name ?? 'Deleted user' }}</td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('inventory-transactions.show', $transaction) }}">View</a>
                                    @if (Auth::user()->isAdmin())
                                        <form class="d-inline" method="POST" action="{{ route('inventory-transactions.destroy', $transaction) }}" onsubmit="return confirm('Reverse and archive this transaction?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Reverse</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No inventory transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $transactions->links() }}</div>
        </div>
    </section>
@endsection
