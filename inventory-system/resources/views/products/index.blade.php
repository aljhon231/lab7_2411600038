@extends('layouts.app')

@section('title', 'Products')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title h2 mb-1">Products</h1>
            <p class="text-muted mb-0">Manage construction materials and stock levels.</p>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Add Product
        </a>
    </div>

    <section class="card dashboard-card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-2"><a class="btn btn-sm btn-outline-success" href="{{ route('procurement.export', ['report' => 'products', ...request()->query()]) }}">Export CSV</a><a class="btn btn-sm btn-outline-danger ms-2" href="{{ route('procurement.export.pdf', ['report' => 'products', ...request()->query()]) }}">Export PDF</a></div>
            <form method="GET" action="{{ route('products.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-sm-8 col-md-4">
                    <label for="search" class="form-label">Search products</label>
                    <input id="search" name="search" type="search" value="{{ request('search') }}" class="form-control" placeholder="Name, SKU, or category">
                </div>
                <div class="col-sm-4 col-md-3">
                    <label for="supplier_id" class="form-label">Supplier</label>
                    <select id="supplier_id" name="supplier_id" class="form-select"><option value="">All suppliers</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)request('supplier_id') === (string)$supplier->id)>{{ $supplier->company_name }}</option>@endforeach</select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-success" type="submit">Search</button>
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Reorder Level</th>
                            <th>Unit Price</th>
                            <th>Supplier</th>
                            <th>Stock Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td class="fw-semibold">{{ $product->name }}</td>
                                <td>{{ $product->sku }}</td>
                                <td>{{ $product->category }}</td>
                                <td>{{ $product->quantity }}</td>
                                <td>{{ $product->reorder_level }}</td>
                                <td>₱{{ number_format((float) $product->unit_price, 2) }}</td>
                                <td>{{ $product->supplierRecord?->company_name ?? $product->supplier ?: '—' }}</td>
                                <td>
                                    @if ($product->isOutOfStock())
                                        <span class="stock-status stock-status-out">Out of Stock</span>
                                    @elseif ($product->isLowStock())
                                        <span class="stock-status stock-status-low">Low Stock</span>
                                    @else
                                        <span class="stock-status stock-status-in-stock">In Stock</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-success" aria-label="View {{ $product->name }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit {{ $product->name }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if (Auth::user()->isAdmin() && $product->inventory_transactions_count === 0)
                                            <form method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete {{ $product->name }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">No products found. Add your first product to get started.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $products->links() }}</div>
        </div>
    </section>
@endsection
