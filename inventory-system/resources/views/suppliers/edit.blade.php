@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
    <div class="mb-4">
        <h1 class="page-title h2 mb-1">Edit Supplier</h1>
        <p class="text-muted mb-0">{{ $supplier->company_name }}</p>
    </div>
    <section class="card dashboard-card">
        <div class="card-body p-4">
            @include('suppliers._form')
        </div>
    </section>
@endsection
