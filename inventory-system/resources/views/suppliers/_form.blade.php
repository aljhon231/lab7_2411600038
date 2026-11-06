<form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
    @csrf
    @if ($supplier->exists)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label for="company_name" class="form-label">Company name <span class="text-danger">*</span></label>
            <input id="company_name" name="company_name" value="{{ old('company_name', $supplier->company_name) }}" class="form-control @error('company_name') is-invalid @enderror" required maxlength="255">
            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="contact_person" class="form-label">Contact person</label>
            <input id="contact_person" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="form-control @error('contact_person') is-invalid @enderror" maxlength="255">
            @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
            <input id="email" name="email" type="email" value="{{ old('email', $supplier->email) }}" class="form-control @error('email') is-invalid @enderror" required maxlength="255">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="phone" class="form-label">Phone</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone', $supplier->phone) }}" class="form-control @error('phone') is-invalid @enderror" maxlength="30">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label for="address" class="form-label">Address</label>
            <textarea id="address" name="address" rows="2" class="form-control @error('address') is-invalid @enderror">{{ old('address', $supplier->address) }}</textarea>
            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label for="payment_terms" class="form-label">Payment terms</label>
            <input id="payment_terms" name="payment_terms" value="{{ old('payment_terms', $supplier->payment_terms) }}" class="form-control @error('payment_terms') is-invalid @enderror" maxlength="255" placeholder="e.g. Net 30">
            @error('payment_terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="lead_time_days" class="form-label">Lead time (days) <span class="text-danger">*</span></label>
            <input id="lead_time_days" name="lead_time_days" type="number" min="0" max="65535" value="{{ old('lead_time_days', $supplier->lead_time_days ?? 0) }}" class="form-control @error('lead_time_days') is-invalid @enderror" required>
            @error('lead_time_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="minimum_order_amount" class="form-label">Minimum order (₱) <span class="text-danger">*</span></label>
            <input id="minimum_order_amount" name="minimum_order_amount" type="number" min="0" max="9999999999.99" step="0.01" value="{{ old('minimum_order_amount', $supplier->minimum_order_amount ?? '0.00') }}" class="form-control @error('minimum_order_amount') is-invalid @enderror" required>
            @error('minimum_order_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-3">
            <label for="minimum_order_quantity" class="form-label">Minimum quantity <span class="text-danger">*</span></label>
            <input id="minimum_order_quantity" name="minimum_order_quantity" type="number" min="1" value="{{ old('minimum_order_quantity', $supplier->minimum_order_quantity ?? 1) }}" class="form-control @error('minimum_order_quantity') is-invalid @enderror" required>
            @error('minimum_order_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <input type="hidden" name="is_active" value="0">
            <div class="form-check">
                <input id="is_active" name="is_active" type="checkbox" value="1" class="form-check-input @error('is_active') is-invalid @enderror" @checked((bool) old('is_active', $supplier->exists ? $supplier->is_active : true))>
                <label for="is_active" class="form-check-label">Active supplier</label>
                @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">{{ $supplier->exists ? 'Save Changes' : 'Save Supplier' }}</button>
        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
