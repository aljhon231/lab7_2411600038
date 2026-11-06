(() => {
    const dashboard = document.getElementById('inventory-dashboard');
    if (!dashboard || window.procurementDashboardPolling) return;

    const text = (tag, value, className = '') => {
        const element = document.createElement(tag);
        element.textContent = value ?? '';
        if (className) element.className = className;
        return element;
    };

    async function refreshProcurementWidgets() {
        const endpoints = [
            '/api/dashboard/pending-orders',
            '/api/dashboard/recent-orders',
            '/api/dashboard/supplier-performance',
            '/api/dashboard/reorder-alerts',
        ];
        try {
            const responses = await Promise.all(endpoints.map((url) => fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })));
            if (responses.some((response) => !response.ok)) {
                throw new Error('Procurement dashboard data could not be refreshed.');
            }
            const [pending, recent, suppliers, alerts] = await Promise.all(responses.map((response) => response.json()));
            document.getElementById('pending-order-count').textContent = pending.data.count;

            const recentList = document.getElementById('recent-purchase-orders');
            recentList.replaceChildren();
            if (!recent.data.length) recentList.append(text('p', 'No purchase orders yet.', 'text-muted mb-0'));
            recent.data.forEach((order) => {
                const link = text('a', '', 'list-group-item list-group-item-action px-0');
                link.href = `/purchase-orders/${encodeURIComponent(order.id)}`;
                link.append(text('strong', order.reference_number));
                link.append(text('span', ` · ${order.supplier?.company_name ?? 'Archived supplier'}`, 'text-muted'));
                link.append(text('span', order.status, 'badge text-bg-secondary float-end'));
                recentList.append(link);
            });

            const supplierList = document.getElementById('supplier-performance-list');
            supplierList.replaceChildren();
            const rankedSuppliers = suppliers.data.filter((supplier) => supplier.on_time_percentage !== null)
                .sort((first, second) => second.on_time_percentage - first.on_time_percentage).slice(0, 5);
            if (!rankedSuppliers.length) supplierList.append(text('p', 'Performance ratings appear after completed deliveries.', 'text-muted mb-0'));
            rankedSuppliers.forEach((supplier) => {
                const row = text('div', '', 'd-flex justify-content-between border-bottom py-2');
                row.append(text('span', supplier.supplier));
                row.append(text('strong', `${Number(supplier.on_time_percentage).toFixed(1)}%`));
                supplierList.append(row);
            });

            const alertList = document.getElementById('reorder-alert-list');
            alertList.replaceChildren();
            if (!alerts.data.length) alertList.append(text('p', 'No products currently need reordering.', 'text-muted mb-0'));
            alerts.data.slice(0, 5).forEach((product) => {
                const row = text('div', '', 'list-group-item px-0 d-flex justify-content-between');
                row.append(text('span', product.name));
                row.append(text('span', `${product.quantity} left`, 'badge text-bg-warning'));
                alertList.append(row);
            });
        } catch (error) {
            console.error(error);
        }
    }

    window.procurementDashboardPolling = window.setInterval(refreshProcurementWidgets, 30000);
})();
