@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
    <div class="mb-4">
        <h1 class="page-title h2 mb-1">Edit Product</h1>
        <p class="text-muted mb-0">Update details for {{ $product->name }}.</p>
    </div>

    <section class="card dashboard-card">
        <div class="card-body p-4">
            @include('products._form')
        </div>
    </section>
@endsection
