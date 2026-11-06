(() => {
    const root = document.getElementById('inventory-dashboard');
    if (!root || window.inventoryDashboardPolling) return;

    const productUrl = root.dataset.productUrl;
    const formatNumber = new Intl.NumberFormat();
    const formatCurrency = new Intl.NumberFormat(undefined, { style: 'currency', currency: 'PHP' });

    const setText = (id, value) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    };

    const createCell = (row, value) => {
        const cell = document.createElement('td');
        cell.textContent = value ?? '';
        row.appendChild(cell);
        return cell;
    };

    async function refreshDashboard() {
        try {
            const endpoints = [
                '/api/dashboard/stats',
                '/api/products/low-stock',
                '/api/transactions/recent',
            ];
            const responses = await Promise.all(endpoints.map((url) => fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })));
            if (responses.some((response) => !response.ok)) {
                throw new Error('Dashboard data could not be refreshed.');
            }

            const [stats, lowStock, transactions] = await Promise.all(responses.map((response) => response.json()));
            setText('dashboard-total-products', formatNumber.format(stats.data.total_products));
            setText('dashboard-low-stock', formatNumber.format(stats.data.low_stock_items));
            setText('dashboard-out-of-stock', formatNumber.format(stats.data.out_of_stock_items));
            setText('dashboard-inventory-value', formatCurrency.format(stats.data.inventory_value));

            const lowStockBody = document.getElementById('dashboard-low-stock-products');
            lowStockBody.replaceChildren();
            if (!lowStock.data.length) {
                const row = lowStockBody.insertRow();
                const cell = createCell(row, 'No low-stock products.');
                cell.colSpan = 3;
                cell.className = 'text-center text-muted';
            } else {
                lowStock.data.forEach((product) => {
                    const row = lowStockBody.insertRow();
                    const cell = row.insertCell();
                    const link = document.createElement('a');
                    link.href = productUrl.replace('__PRODUCT__', encodeURIComponent(product.id));
                    link.textContent = product.name;
                    cell.appendChild(link);
                    const sku = document.createElement('small');
                    sku.className = 'd-block text-muted';
                    sku.textContent = product.sku;
                    cell.appendChild(sku);
                    createCell(row, product.quantity);
                    createCell(row, product.reorder_level);
                });
            }

            const transactionBody = document.getElementById('dashboard-recent-transactions');
            transactionBody.replaceChildren();
            if (!transactions.data.length) {
                const row = transactionBody.insertRow();
                const cell = createCell(row, 'No inventory transactions yet.');
                cell.colSpan = 5;
                cell.className = 'text-center text-muted';
            } else {
                transactions.data.forEach((transaction) => {
                    const row = transactionBody.insertRow();
                    createCell(row, transaction.date);
                    const productCell = row.insertCell();
                    const link = document.createElement('a');
                    link.href = productUrl.replace('__PRODUCT__', encodeURIComponent(transaction.product_id));
                    link.textContent = transaction.product_name;
                    productCell.appendChild(link);
                    createCell(row, transaction.type.replaceAll('_', ' '));
                    createCell(row, `${transaction.signed_quantity > 0 ? '+' : ''}${transaction.signed_quantity}`);
                    createCell(row, transaction.user);
                });
            }

            setText('dashboard-last-updated', new Date().toLocaleString());
        } catch (error) {
            console.error(error);
        }
    }

    window.inventoryDashboardPolling = window.setInterval(refreshDashboard, 30000);
})();
