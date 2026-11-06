@php
    $currentItems = old('items', isset($order) ? $order->items->map(fn ($item) => [
        'product_id' => $item->product_id,
        'quantity_ordered' => $item->quantity_ordered,
        'unit_price' => $item->unit_price,
    ])->all() : [['product_id' => '', 'quantity_ordered' => 1, 'unit_price' => '']]);
@endphp
<form method="POST" action="{{ isset($order) ? route('purchase-orders.update', $order) : route('purchase-orders.store') }}" id="purchase-order-form">
    @csrf
    @isset($order)
        @method('PUT')
    @endisset
    <div class="row g-3">
        <div class="col-md-6">
            <label for="supplier_id" class="form-label">Supplier <span class="text-danger">*</span></label>
            <select id="supplier_id" name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                <option value="">Choose a supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) old('supplier_id', $order->supplier_id ?? '') === (string) $supplier->id)>{{ $supplier->company_name }}</option>
                @endforeach
            </select>
            @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="order_date" class="form-label">Order date <span class="text-danger">*</span></label>
            <input id="order_date" name="order_date" type="date" value="{{ old('order_date', isset($order) ? $order->order_date?->toDateString() : now()->toDateString()) }}" class="form-control @error('order_date') is-invalid @enderror" required>
            @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="expected_delivery_date" class="form-label">Expected delivery</label>
            <input id="expected_delivery_date" name="expected_delivery_date" type="date" value="{{ old('expected_delivery_date', isset($order) ? $order->expected_delivery_date?->toDateString() : '') }}" class="form-control @error('expected_delivery_date') is-invalid @enderror">
            @error('expected_delivery_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
        <h2 class="h5 mb-0">Order items <span class="text-danger">*</span></h2>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-order-item"><i class="bi bi-plus-lg me-1"></i>Add item</button>
    </div>
    @error('items')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
    <div id="order-items" data-next-index="{{ count($currentItems) }}">
        @foreach ($currentItems as $index => $item)
            <div class="row g-2 align-items-end order-item mb-2">
                <div class="col-md-6">
                    <label class="form-label" for="items-{{ $index }}-product">Product</label>
                    <select id="items-{{ $index }}-product" name="items[{{ $index }}][product_id]" class="form-select product-select" required>
                        <option value="">Choose a product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" data-price="{{ $product->unit_price }}" @selected((string) ($item['product_id'] ?? '') === (string) $product->id)>{{ $product->name }} ({{ $product->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="items-{{ $index }}-quantity">Quantity</label>
                    <input id="items-{{ $index }}-quantity" name="items[{{ $index }}][quantity_ordered]" type="number" min="1" value="{{ $item['quantity_ordered'] ?? 1 }}" class="form-control item-quantity" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="items-{{ $index }}-price">Unit price (₱)</label>
                    <input id="items-{{ $index }}-price" name="items[{{ $index }}][unit_price]" type="number" min="0" step="0.01" value="{{ $item['unit_price'] ?? '' }}" class="form-control item-price" placeholder="Uses product price">
                </div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-order-item" aria-label="Remove item"><i class="bi bi-trash"></i></button></div>
            </div>
        @endforeach
    </div>
    <template id="order-item-template">
        <div class="row g-2 align-items-end order-item mb-2">
            <div class="col-md-6">
                <label class="form-label" for="items-__INDEX__-product">Product</label>
                <select id="items-__INDEX__-product" name="items[__INDEX__][product_id]" class="form-select product-select" required>
                    <option value="">Choose a product</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-price="{{ $product->unit_price }}">{{ $product->name }} ({{ $product->sku }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label" for="items-__INDEX__-quantity">Quantity</label><input id="items-__INDEX__-quantity" name="items[__INDEX__][quantity_ordered]" type="number" min="1" value="1" class="form-control item-quantity" required></div>
            <div class="col-md-3"><label class="form-label" for="items-__INDEX__-price">Unit price (₱)</label><input id="items-__INDEX__-price" name="items[__INDEX__][unit_price]" type="number" min="0" step="0.01" class="form-control item-price"></div>
            <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-order-item" aria-label="Remove item"><i class="bi bi-trash"></i></button></div>
        </div>
    </template>
    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <label for="tax" class="form-label">Tax (₱)</label>
            <input id="tax" name="tax" type="number" min="0" step="0.01" value="{{ old('tax', $order->tax ?? 0) }}" class="form-control">
        </div>
        <div class="col-md-4">
            <label for="notes" class="form-label">Notes</label>
            <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes', $order->notes ?? '') }}</textarea>
        </div>
        <div class="col-md-4">
            <label for="terms" class="form-label">Terms</label>
            <textarea id="terms" name="terms" rows="2" class="form-control">{{ old('terms', $order->terms ?? '') }}</textarea>
        </div>
    </div>
    <div class="text-end mt-3">
        <p class="mb-1">Subtotal: <strong id="order-subtotal">₱0.00</strong></p>
        <p class="mb-0">Total: <strong id="order-total">₱0.00</strong></p>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">{{ isset($order) ? 'Save Draft' : 'Submit for Approval' }}</button>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
    <script>
        (() => {
            const container = document.getElementById('order-items');
            const currency = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'PHP' });
            const updateTotals = () => {
                let subtotal = 0;
                container.querySelectorAll('.order-item').forEach((row) => {
                    const quantity = Number(row.querySelector('.item-quantity').value || 0);
                    const price = Number(row.querySelector('.item-price').value || row.querySelector('.product-select').selectedOptions[0]?.dataset.price || 0);
                    subtotal += quantity * price;
                });
                document.getElementById('order-subtotal').textContent = currency.format(subtotal);
                document.getElementById('order-total').textContent = currency.format(subtotal + Number(document.getElementById('tax').value || 0));
            };
            const bindRow = (row) => {
                row.querySelectorAll('input, select').forEach((field) => field.addEventListener('input', updateTotals));
                row.querySelector('.product-select').addEventListener('change', (event) => {
                    const price = row.querySelector('.item-price');
                    if (!price.value) price.value = event.target.selectedOptions[0]?.dataset.price || '';
                    updateTotals();
                });
                row.querySelector('.remove-order-item').addEventListener('click', () => {
                    if (container.querySelectorAll('.order-item').length > 1) row.remove();
                    updateTotals();
                });
            };
            container.querySelectorAll('.order-item').forEach(bindRow);
            document.getElementById('add-order-item').addEventListener('click', () => {
                const index = Number(container.dataset.nextIndex++);
                const row = document.getElementById('order-item-template').content.firstElementChild.cloneNode(true);
                row.querySelectorAll('[id], [name], label[for]').forEach((element) => {
                    for (const attribute of ['id', 'name', 'for']) {
                        if (element.hasAttribute(attribute)) {
                            element.setAttribute(attribute, element.getAttribute(attribute).replaceAll('__INDEX__', String(index)));
                        }
                    }
                });
                container.appendChild(row);
                bindRow(row);
                updateTotals();
            });
            document.getElementById('tax').addEventListener('input', updateTotals);
            updateTotals();
        })();
    </script>
@endpush
