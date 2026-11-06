@extends('layouts.app')

@section('title', 'Transaction #'.$transaction->id)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title h2 mb-1">Transaction #{{ $transaction->id }}</h1>
            <p class="text-muted mb-0">{{ $transaction->created_at->format('M d, Y h:i A') }}</p>
        </div>
        <a href="{{ route('inventory-transactions.index') }}" class="btn btn-outline-secondary">Back to transactions</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <section class="card dashboard-card h-100"><div class="card-body">
                <h2 class="h5 mb-3">Transaction details</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Type</dt><dd class="col-sm-7">{{ str_replace('_', ' ', ucfirst($transaction->type)) }}</dd>
                    <dt class="col-sm-5">Quantity moved</dt><dd class="col-sm-7">{{ $transaction->signedQuantity() > 0 ? '+' : '' }}{{ $transaction->signedQuantity() }}</dd>
                    <dt class="col-sm-5">Reference</dt><dd class="col-sm-7">{{ $transaction->reference_number ?: '—' }}</dd>
                    <dt class="col-sm-5">Reason</dt><dd class="col-sm-7">{{ $transaction->reason ?: '—' }}</dd>
                    <dt class="col-sm-5">Notes</dt><dd class="col-sm-7">{{ $transaction->notes ?: '—' }}</dd>
                    <dt class="col-sm-5">Date and time</dt><dd class="col-sm-7">{{ $transaction->created_at->format('M d, Y h:i:s A') }}</dd>
                </dl>
            </div></section>
        </div>
        <div class="col-lg-6">
            <section class="card dashboard-card h-100"><div class="card-body">
                <h2 class="h5 mb-3">Product and user</h2>
                <p class="mb-1"><strong>Product:</strong>
                    @if ($transaction->product)<a href="{{ route('products.show', $transaction->product) }}">{{ $transaction->product->name }}</a> ({{ $transaction->product->sku }})@else Deleted product @endif
                </p>
                <p class="mb-0"><strong>Performed by:</strong> {{ $transaction->user?->name ?? 'Deleted user' }}</p>
            </div></section>
        </div>
    </div>
@endsection
