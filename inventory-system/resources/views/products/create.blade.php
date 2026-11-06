@extends('layouts.app')

@section('title', 'Add Product')

@section('content')
    <div class="mb-4">
        <h1 class="page-title h2 mb-1">Add Product</h1>
        <p class="text-muted mb-0">Enter product details and its starting stock.</p>
    </div>

    <section class="card dashboard-card">
        <div class="card-body p-4">
            @include('products._form')
        </div>
    </section>
@endsection
