@extends('layouts.app')

@section('title', 'Reorder Alerts')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="page-title h2 mb-1">Reorder Alerts</h1><p class="text-muted mb-0">Based on recent usage, supplier lead time, and outstanding orders.</p></div>
        <form method="POST" action="{{ route('reorder-alerts.run') }}">@csrf<button class="btn btn-primary" type="submit">Generate Draft Purchase Orders</button></form>
    </div>
    <section class="card dashboard-card"><div class="card-body table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Product</th><th>Supplier</th><th>On hand</th><th>Reorder point</th><th>Suggested quantity</th></tr></thead>
            <tbody>
                @forelse($products as $product)
                    <tr><td>{{ $product->name }} <small class="d-block text-muted">{{ $product->sku }}</small></td><td>{{ $product->supplierRecord?->company_name }}</td><td>{{ $product->quantity }}</td><td>{{ app(\App\Services\ReorderService::class)->calculateReorderPoint($product) }}</td><td>{{ app(\App\Services\ReorderService::class)->calculateOptimalOrderQuantity($product) }}</td></tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">No products currently need reordering.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></section>
@endsection
