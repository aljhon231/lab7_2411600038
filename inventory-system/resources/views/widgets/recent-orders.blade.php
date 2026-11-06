<section class="card dashboard-card h-100">
    <div class="card-body">
        <h2 class="h5">Recent purchase orders</h2>
        <div class="list-group list-group-flush" id="recent-purchase-orders">
            @forelse ($recentPurchaseOrders as $order)
                <a class="list-group-item list-group-item-action px-0" href="{{ route('purchase-orders.show', $order) }}">
                    <strong>{{ $order->reference_number }}</strong>
                    <span class="text-muted"> · {{ $order->supplier?->company_name }}</span>
                    <span class="badge text-bg-secondary float-end">{{ ucfirst($order->status) }}</span>
                </a>
            @empty
                <p class="text-muted mb-0">No purchase orders yet.</p>
            @endforelse
        </div>
    </div>
</section>
