<section class="card dashboard-card h-100">
    <div class="card-body">
        <h2 class="h5">Reorder alerts</h2>
        <div class="list-group list-group-flush" id="reorder-alert-list">
            @forelse ($reorderProducts as $product)
                <div class="list-group-item px-0 d-flex justify-content-between">
                    <span>{{ $product->name }}</span>
                    <span class="badge text-bg-warning">{{ $product->quantity }} left</span>
                </div>
            @empty
                <p class="text-muted mb-0">No products currently need reordering.</p>
            @endforelse
        </div>
        <a href="{{ route('reorder-alerts.index') }}" class="btn btn-sm btn-outline-primary mt-3">View alerts</a>
    </div>
</section>
