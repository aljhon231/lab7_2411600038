@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title h2 mb-1">Suppliers</h1>
            <p class="text-muted mb-0">Manage supplier contacts and delivery terms.</p>
        </div>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Add Supplier
        </a>
    </div>

    <section class="card dashboard-card">
        <div class="card-body">
            <form method="GET" action="{{ route('suppliers.index') }}" class="row g-2 align-items-end mb-3">
                <div class="col-sm-8 col-md-4">
                    <label for="search" class="form-label">Search suppliers</label>
                    <input id="search" name="search" type="search" value="{{ request('search') }}" class="form-control" placeholder="Company, contact, or email">
                </div>
                <div class="col-sm-4 col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All suppliers</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label for="sort" class="form-label">Sort by</label>
                    <select id="sort" name="sort" class="form-select">
                        <option value="company_name" @selected(request('sort', 'company_name') === 'company_name')>Company</option>
                        <option value="lead_time_days" @selected(request('sort') === 'lead_time_days')>Lead time</option>
                        <option value="minimum_order_amount" @selected(request('sort') === 'minimum_order_amount')>Minimum order</option>
                        <option value="created_at" @selected(request('sort') === 'created_at')>Date added</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label for="direction" class="form-label">Direction</label>
                    <select id="direction" name="direction" class="form-select">
                        <option value="asc" @selected(request('direction', 'asc') === 'asc')>Ascending</option>
                        <option value="desc" @selected(request('direction') === 'desc')>Descending</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-success" type="submit">Apply</button>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">Clear</a>
                </div>
            </form>
            @if(Auth::user()->isAdmin())
                <p class="mb-3"><a href="{{ route('suppliers.index', ['show_archived' => 1]) }}">View archived suppliers</a></p>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Lead time</th>
                            <th>Minimum order</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr>
                                <td class="fw-semibold">{{ $supplier->company_name }}</td>
                                <td>{{ $supplier->full_contact ?: '—' }}</td>
                                <td>{{ $supplier->lead_time_days }} days</td>
                                <td>₱{{ number_format((float) $supplier->minimum_order_amount, 2) }}</td>
                                <td>{{ $supplier->products_count }}</td>
                                <td>
                                    <span class="badge {{ $supplier->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        @if($supplier->trashed())
                                            <form method="POST" action="{{ route('suppliers.restore', $supplier->id) }}">@csrf<button type="submit" class="btn btn-sm btn-outline-success">Restore</button></form>
                                        @else
                                        <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-outline-success" aria-label="View {{ $supplier->company_name }}"><i class="bi bi-eye"></i></a>
                                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit {{ $supplier->company_name }}"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Archive this supplier?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Archive {{ $supplier->company_name }}"><i class="bi bi-trash"></i></button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-5">No suppliers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $suppliers->links() }}</div>
        </div>
    </section>
@endsection
