@extends('layouts.app')

@section('title', 'Add Supplier')

@section('content')
    <div class="mb-4">
        <h1 class="page-title h2 mb-1">Add Supplier</h1>
        <p class="text-muted mb-0">Enter the supplier's contact and delivery details.</p>
    </div>
    <section class="card dashboard-card">
        <div class="card-body p-4">
            @include('suppliers._form')
        </div>
    </section>
@endsection
