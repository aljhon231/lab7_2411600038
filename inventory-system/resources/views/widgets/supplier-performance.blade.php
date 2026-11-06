<section class="card dashboard-card h-100">
    <div class="card-body">
        <h2 class="h5">Supplier performance</h2>
        <div id="supplier-performance-list">
        @forelse ($topSuppliers as $supplier)
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>{{ $supplier['supplier']->company_name }}</span>
                <strong>{{ number_format($supplier['performance_rating'], 1) }}%</strong>
            </div>
        @empty
            <p class="text-muted mb-0">Performance ratings appear after completed deliveries.</p>
        @endforelse
        </div>
        <a href="{{ route('suppliers.performance') }}" class="btn btn-sm btn-outline-primary mt-3">View report</a>
    </div>
</section>
