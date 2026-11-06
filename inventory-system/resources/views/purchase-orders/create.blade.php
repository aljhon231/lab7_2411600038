@extends('layouts.app')

@section('title', 'Create Purchase Order')

@section('content')
    <div class="mb-4"><h1 class="page-title h2 mb-1">Create Purchase Order</h1><p class="text-muted mb-0">The order will be submitted for approval.</p></div>
    <section class="card dashboard-card"><div class="card-body p-4">@include('purchase-orders._form')</div></section>
@endsection
