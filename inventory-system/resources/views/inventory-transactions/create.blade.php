@extends('layouts.app')

@section('title', 'Record Stock Movement')

@section('content')
    <div class="mb-4">
        <h1 class="page-title h2 mb-1">Record Stock Movement</h1>
        <p class="text-muted mb-0">Stock changes and their transaction records are saved together.</p>
    </div>

    <section class="card dashboard-card">
        <div class="card-body p-4">
            @if ($products->isEmpty())
                <div class="alert alert-info mb-0">
                    Add a product before recording a stock movement.
                    <a href="{{ route('products.create') }}" class="alert-link">Add Product</a>
                </div>
            @else
                <form method="POST" action="{{ route('inventory-transactions.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="product_id" class="form-label">Product <span class="text-danger">*</span></label>
                            <select id="product_id" name="product_id" class="form-select @error('product_id') is-invalid @enderror" required>
                                <option value="">Choose a product</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" data-stock="{{ $product->quantity }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }} ({{ $product->quantity }} in stock)</option>
                                @endforeach
                            </select>
                            @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="type" class="form-label">Transaction Type <span class="text-danger">*</span></label>
                            <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">Choose a movement</option>
                                <option value="stock_in" @selected(old('type') === 'stock_in')>Stock In</option>
                                <option value="stock_out" @selected(old('type') === 'stock_out')>Stock Out</option>
                            </select>
                            @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input id="quantity" name="quantity" type="number" min="1" max="2147483647" step="1" value="{{ old('quantity', 1) }}" class="form-control @error('quantity') is-invalid @enderror" required>
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="reference_number" class="form-label">Reference Number</label>
                            <input id="reference_number" name="reference_number" type="text" value="{{ old('reference_number') }}" class="form-control @error('reference_number') is-invalid @enderror" maxlength="255" placeholder="Invoice, receipt, or note">
                            @error('reference_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">Save Transaction</button>
                        <a href="{{ route('inventory-transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const product = document.getElementById('product_id');
            const type = document.getElementById('type');
            const quantity = document.getElementById('quantity');
            const updateLimit = () => {
                const selected = product.options[product.selectedIndex];
                quantity.max = type.value === 'stock_out' && selected?.dataset.stock
                    ? selected.dataset.stock
                    : '2147483647';
            };
            product.addEventListener('change', updateLimit);
            type.addEventListener('change', updateLimit);
            updateLimit();
        })();
    </script>
@endpush
