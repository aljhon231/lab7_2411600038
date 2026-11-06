<form method="POST" action="{{ isset($product) ? route('products.update', $product) : route('products.store') }}">
    @csrf
    @isset($product)
        @method('PUT')
    @endisset

    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
            <input id="name" name="name" type="text" value="{{ old('name', $product->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required maxlength="255">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
            <input id="sku" name="sku" type="text" value="{{ old('sku', $product->sku ?? '') }}" class="form-control @error('sku') is-invalid @enderror" required maxlength="255">
            @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
            <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
                <option value="">Choose a category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(old('category', $product->category ?? '') === $category)>{{ $category }}</option>
                @endforeach
            </select>
            @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="supplier" class="form-label">Supplier</label>
            <input id="supplier" name="supplier" type="text" value="{{ old('supplier', $product->supplier ?? '') }}" class="form-control @error('supplier') is-invalid @enderror" maxlength="255">
            @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="supplier_id" class="form-label">Link to supplier record</label>
            <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                <option value="">No linked supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $product->supplier_id ?? '') === (string) $supplier->id)>{{ $supplier->company_name }}</option>
                @endforeach
            </select>
            @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="package_size" class="form-label">Supplier package size <span class="text-danger">*</span></label>
            <input id="package_size" name="package_size" type="number" min="1" value="{{ old('package_size', $product->package_size ?? 1) }}" class="form-control @error('package_size') is-invalid @enderror" required>
            @error('package_size')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <input type="hidden" name="is_active" value="0">
            <div class="form-check mb-2">
                <input id="is_active" name="is_active" type="checkbox" value="1" class="form-check-input @error('is_active') is-invalid @enderror" @checked((bool) old('is_active', isset($product) ? $product->is_active : true))>
                <label for="is_active" class="form-check-label">Active product</label>
                @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="col-md-6">
            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
            <input id="quantity" name="quantity" type="number" min="0" step="1" value="{{ old('quantity', $product->quantity ?? 0) }}" class="form-control @error('quantity') is-invalid @enderror" required>
            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
            @isset($product)
                <div class="form-text">Quantity changes are automatically recorded as stock transactions.</div>
            @endisset
        </div>
        <div class="col-md-6">
            <label for="reorder_level" class="form-label">Reorder Level <span class="text-danger">*</span></label>
            <input id="reorder_level" name="reorder_level" type="number" min="0" step="1" value="{{ old('reorder_level', $product->reorder_level ?? 10) }}" class="form-control @error('reorder_level') is-invalid @enderror" required>
            @error('reorder_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="unit_price" class="form-label">Unit Price (₱) <span class="text-danger">*</span></label>
            <input id="unit_price" name="unit_price" type="number" min="0" max="99999999.99" step="0.01" value="{{ old('unit_price', $product->unit_price ?? '') }}" class="form-control @error('unit_price') is-invalid @enderror" required>
            @error('unit_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="3" class="form-control product-description @error('description') is-invalid @enderror">{{ old('description', $product->description ?? '') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">
            @isset($product) Save Changes @else Save Product @endisset
        </button>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
