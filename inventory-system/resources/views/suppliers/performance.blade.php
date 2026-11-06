@extends('layouts.app')

@section('title', 'Supplier Performance')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="page-title h2 mb-1">Supplier Performance</h1>
            <p class="text-muted mb-0">Metrics use completed purchase orders and recorded receipts.</p>
        </div>
        <div class="d-flex gap-2"><a class="btn btn-outline-success" href="{{ route('procurement.export', ['report' => 'supplier-performance']) }}">Export CSV</a><a class="btn btn-outline-danger" href="{{ route('procurement.export.pdf', ['report' => 'supplier-performance']) }}">Export PDF</a></div>
    </div>
    <section class="card dashboard-card mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Supplier</th><th>Total orders</th><th>On time</th><th>On-time %</th><th>Avg. delivery days</th><th>Order accuracy</th><th>Total value</th></tr></thead>
                    <tbody>
                        @forelse ($suppliers as $row)
                            <tr>
                                <td>{{ $row['supplier']->company_name }}</td>
                                <td>{{ $row['total_orders'] }}</td>
                                <td>{{ $row['on_time_orders'] }} / {{ $row['completed_orders'] }}</td>
                                <td>{{ $row['on_time_percentage'] === null ? 'Insufficient data' : number_format($row['on_time_percentage'], 1).'%' }}</td>
                                <td>{{ $row['average_delivery_days'] === null ? '—' : number_format($row['average_delivery_days'], 1) }}</td>
                                <td>{{ $row['order_accuracy'] === null ? 'Insufficient data' : number_format($row['order_accuracy'], 1).'%' }}</td>
                                <td>₱{{ number_format($row['total_value'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No suppliers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="form-text mt-3 mb-0">On-time delivery is measured only for received orders with both expected and actual dates. Order accuracy is received units divided by ordered units.</p>
        </div>
    </section>
    <div class="row g-4">
        <div class="col-lg-6"><section class="card dashboard-card"><div class="card-body"><h2 class="h5">Top-performing suppliers</h2>@forelse($topSuppliers as $row)<p class="mb-1">{{ $row['supplier']->company_name }} <strong class="float-end">{{ number_format($row['on_time_percentage'], 1) }}%</strong></p>@empty<p class="text-muted mb-0">No delivery data yet.</p>@endforelse</div></section></div>
        <div class="col-lg-6"><section class="card dashboard-card"><div class="card-body"><h2 class="h5">Suppliers needing improvement</h2>@forelse($bottomSuppliers as $row)<p class="mb-1">{{ $row['supplier']->company_name }} <strong class="float-end">{{ number_format($row['on_time_percentage'], 1) }}%</strong></p>@empty<p class="text-muted mb-0">No delivery data yet.</p>@endforelse</div></section></div>
    </div>
    <section class="card dashboard-card mt-4"><div class="card-body"><h2 class="h5">Monthly on-time delivery trend</h2>
        @forelse($deliveryTrend as $month)
            @php($percentage = $month['total'] ? ($month['on_time'] / $month['total']) * 100 : 0)
            <div class="mb-3"><div class="d-flex justify-content-between"><span>{{ $month['month'] }}</span><span>{{ $month['on_time'] }} of {{ $month['total'] }} on time ({{ number_format($percentage, 1) }}%)</span></div><div class="progress" role="img" aria-label="On-time delivery {{ number_format($percentage, 1) }}%"><div class="progress-bar bg-success" style="width: {{ $percentage }}%"></div></div></div>
        @empty
            <p class="text-muted mb-0">A delivery trend appears after actual and expected delivery dates are recorded.</p>
        @endforelse
    </div></section>
@endsection
