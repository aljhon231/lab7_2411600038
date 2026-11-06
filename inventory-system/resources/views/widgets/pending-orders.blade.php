<section class="card dashboard-card h-100">
    <div class="card-body">
        <h2 class="h5">Pending purchase orders</h2>
        <p class="display-6 fw-bold mb-3" id="pending-order-count">{{ $pendingOrderCount }}</p>
        <a href="{{ route('purchase-orders.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-primary">Review orders</a>
    </div>
</section>
