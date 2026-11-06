<nav class="navbar navbar-dark main-navbar">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <i class="bi bi-box-seam"></i>
            <span>Inventory System</span>
        </a>

        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm position-relative" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    @if (Auth::user()->unreadNotifications()->count())
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ Auth::user()->unreadNotifications()->count() }}</span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 20rem">
                    <h6 class="dropdown-header">Recent notifications</h6>
                    @forelse (Auth::user()->notifications()->latest()->limit(5)->get() as $notification)
                        <div class="dropdown-item-text small text-wrap {{ $notification->read_at ? 'text-muted' : 'fw-semibold' }}">
                            {{ $notification->data['product_name'] ?? 'Inventory notification' }}:
                            {{ $notification->data['suggested_action'] ?? 'Review inventory.' }}
                            <small class="d-block text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                            @if (! $notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                    @csrf
                                    <button class="btn btn-link btn-sm p-0" type="submit">View and mark as read</button>
                                </form>
                            @else
                                <a class="small" href="{{ $notification->data['url'] ?? route('dashboard') }}">View product</a>
                            @endif
                        </div>
                    @empty
                        <span class="dropdown-item-text text-muted small">No notifications.</span>
                    @endforelse
                </div>
            </div>
            <div class="dropdown">
                <button class="btn btn-outline-light btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">Profile</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</nav>

<aside class="sidebar">
    <div class="sidebar-content">
        <div class="sidebar-profile text-center">
            <div class="profile-icon"><i class="bi bi-person-fill"></i></div>
            <h6 class="mb-1">{{ Auth::user()->name }}</h6>
            <small class="text-muted">Inventory account</small>
        </div>
        <hr>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <i class="bi bi-boxes"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('products.create') }}" class="nav-link {{ request()->routeIs('products.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle"></i>Add Product
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                    <i class="bi bi-buildings"></i>Suppliers
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('purchase-orders.index') }}" class="nav-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
                    <i class="bi bi-receipt"></i>Purchase Orders
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('suppliers.performance') }}" class="nav-link {{ request()->routeIs('suppliers.performance') ? 'active' : '' }}">
                    <i class="bi bi-graph-up"></i>Supplier Performance
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('reorder-alerts.index') }}" class="nav-link {{ request()->routeIs('reorder-alerts.*') ? 'active' : '' }}">
                    <i class="bi bi-exclamation-circle"></i>Reorder Alerts
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('inventory-transactions.index') }}" class="nav-link {{ request()->routeIs('inventory-transactions.*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-left-right"></i>Inventory Transactions
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('inventory-transactions.create') }}" class="nav-link">
                    <i class="bi bi-arrow-down-up"></i>Record Stock Movement
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('inventory-adjustments.create') }}" class="nav-link {{ request()->routeIs('inventory-adjustments.*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard2-check"></i>Stock Adjustment
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph"></i>Inventory Reports
                </a>
            </li>
        </ul>
    </div>
</aside>
