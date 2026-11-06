@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="welcome-section mb-4" id="inventory-dashboard" data-product-url="{{ route('products.show', ['product' => '__PRODUCT__']) }}">
        <h1 class="h3 mb-1">Inventory Dashboard</h1>
        <p class="text-muted mb-0">A live summary of products and stock in your inventory.</p>
        <small class="text-muted">Last updated: <time id="dashboard-last-updated">{{ now()->format('M d, Y h:i:s A') }}</time></small>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <section class="card stat-card h-100"><div class="card-body">
                <div class="stat-icon"><i class="bi bi-boxes"></i></div>
                <div class="stat-title">Total Products</div>
                <div class="stat-value" id="dashboard-total-products">{{ number_format($statistics['totalProducts']) }}</div>
            </div></section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card stat-card h-100"><div class="card-body">
                <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="stat-title">Low Stock Items</div>
                <div class="stat-value" id="dashboard-low-stock">{{ number_format($statistics['lowStockItems']) }}</div>
            </div></section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card stat-card h-100"><div class="card-body">
                <div class="stat-icon"><i class="bi bi-x-octagon"></i></div>
                <div class="stat-title">Out of Stock</div>
                <div class="stat-value" id="dashboard-out-of-stock">{{ number_format($statistics['outOfStockItems']) }}</div>
            </div></section>
        </div>
        <div class="col-sm-6 col-xl-3">
            <section class="card stat-card h-100"><div class="card-body">
                <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-title">Total Inventory Value</div>
                <div class="stat-value fs-4" id="dashboard-inventory-value">₱{{ number_format((float) $statistics['inventoryValue'], 2) }}</div>
            </div></section>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <section class="card dashboard-card h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Low-stock alerts</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Product</th><th>Current</th><th>Reorder at</th></tr></thead>
                            <tbody id="dashboard-low-stock-products">
                                @forelse ($lowStockProducts as $product)
                                    <tr>
                                        <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a><small class="d-block text-muted">{{ $product->sku }}</small></td>
                                        <td>{{ $product->quantity }}</td><td>{{ $product->reorder_level }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No low-stock products.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-7">
            <section class="card dashboard-card h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Recent inventory transactions</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Date</th><th>Product</th><th>Type</th><th>Quantity</th><th>User</th></tr></thead>
                            <tbody id="dashboard-recent-transactions">
                                @forelse ($recentTransactions as $transaction)
                                    <tr>
                                        <td>{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                                        <td><a href="{{ route('products.show', $transaction->product) }}">{{ $transaction->product?->name ?? 'Deleted product' }}</a></td>
                                        <td>{{ str_replace('_', ' ', ucfirst($transaction->type)) }}</td>
                                        <td>{{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ $transaction->signedQuantity() }}</td>
                                        <td>{{ $transaction->user?->name ?? 'Deleted user' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">No inventory transactions yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <section class="card dashboard-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Recently Added Products</h2>
                        <a href="{{ route('products.index') }}" class="small">View all</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Product</th><th>Category</th><th>Quantity</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($recentProducts as $product)
                                    <tr>
                                        <td><a href="{{ route('products.show', $product) }}" class="fw-semibold text-decoration-none">{{ $product->name }}</a><small class="d-block text-muted">{{ $product->sku }}</small></td>
                                        <td>{{ $product->category }}</td>
                                        <td>{{ $product->quantity }}</td>
                                        <td>
                                            @if ($product->isOutOfStock())
                                                <span class="stock-status stock-status-out">Out</span>
                                            @elseif ($product->isLowStock())
                                                <span class="stock-status stock-status-low">Low</span>
                                            @else
                                                <span class="stock-status stock-status-in-stock">In Stock</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">No products yet. <a href="{{ route('products.create') }}">Add your first product</a>.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-5">
            <section class="card dashboard-card h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Inventory Summary by Category</h2>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Category</th><th>Products</th><th>Units</th><th>Value</th></tr></thead>
                            <tbody>
                                @forelse ($categorySummary as $category)
                                    <tr>
                                        <td>{{ $category->category }}</td>
                                        <td>{{ $category->product_count }}</td>
                                        <td>{{ $category->quantity }}</td>
                                        <td>₱{{ number_format((float) $category->inventory_value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">Category totals will appear after products are added.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ route('inventory-transactions.index') }}" class="btn btn-outline-success btn-sm mt-3">View stock movements</a>
                </div>
            </section>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
        <h2 class="h4 mb-0">Procurement Dashboard</h2>
        <a href="{{ route('purchase-orders.index') }}" class="small">All purchase orders</a>
    </div>
    <div class="row g-4">
        <div class="col-md-6 col-xl-3">@include('widgets.pending-orders')</div>
        <div class="col-md-6 col-xl-3">@include('widgets.recent-orders')</div>
        <div class="col-md-6 col-xl-3">@include('widgets.supplier-performance')</div>
        <div class="col-md-6 col-xl-3">@include('widgets.reorder-alerts')</div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/dashboard-polling.js') }}" defer></script>
    <script src="{{ asset('js/procurement-polling.js') }}" defer></script>
@endpush
