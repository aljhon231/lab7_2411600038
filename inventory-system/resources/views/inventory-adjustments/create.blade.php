@extends('layouts.app')

@section('title', 'Adjust Inventory')

@section('content')
    <div class="mb-4">
        <h1 class="page-title h2 mb-1">Adjust Inventory</h1>
        <p class="text-muted mb-0">Record the physical count. The system will calculate and apply the signed difference.</p>
    </div>
    <section class="card dashboard-card"><div class="card-body p-4">
        <form method="POST" action="{{ route('inventory-adjustments.store') }}" id="inventory-adjustment-form">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="adjustment-product" class="form-label">Product</label>
                    <select id="adjustment-product" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                        <option value="">Choose a product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id', request('product_id')) === (string) $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                        @endforeach
                    </select>
                    @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="system-quantity" class="form-label">Current system quantity</label>
                    <input id="system-quantity" class="form-control" type="number" value="" readonly placeholder="Select a product">
                </div>
                <div class="col-md-6">
                    <label for="physical-count" class="form-label">Physical count</label>
                    <input id="physical-count" name="physical_count" type="number" min="0" step="1" value="{{ old('physical_count') }}" class="form-control @error('physical_count') is-invalid @enderror" required>
                    @error('physical_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="adjustment-reason" class="form-label">Reason</label>
                    <select id="adjustment-reason" name="reason" class="form-select @error('reason') is-invalid @enderror" required>
                        <option value="">Choose a reason</option>
                        @foreach ($reasons as $reason)
                            <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ $reason }}</option>
                        @endforeach
                    </select>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="adjustment-notes" class="form-label">Notes</label>
                    <textarea id="adjustment-notes" name="notes" rows="3" maxlength="5000" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                    @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div id="stock-load-error" class="text-danger small mt-2" role="alert"></div>
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit">Save adjustment</button>
                <a href="{{ route('inventory-transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div></section>
@endsection

@push('scripts')
    <script>
        (() => {
            const product = document.getElementById('adjustment-product');
            const quantity = document.getElementById('system-quantity');
            const error = document.getElementById('stock-load-error');
            product.addEventListener('change', async () => {
                quantity.value = '';
                error.textContent = '';
                if (!product.value) return;
                try {
                    const response = await fetch(`/api/products/${encodeURIComponent(product.value)}/stock`, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error('Current stock could not be loaded.');
                    const result = await response.json();
                    quantity.value = result.data.quantity;
                } catch (exception) {
                    error.textContent = exception.message;
                }
            });
            if (product.value) product.dispatchEvent(new Event('change'));
        })();
    </script>
@endpush
