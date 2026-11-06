<?php

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;

function purchaseRoleUser(string $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    return $user;
}

function purchaseOrderSupplier(array $overrides = []): Supplier
{
    return Supplier::query()->create(array_merge([
        'company_name' => 'Trusted Supplier',
        'email' => fake()->unique()->safeEmail(),
        'lead_time_days' => 7,
        'minimum_order_amount' => 0,
        'minimum_order_quantity' => 1,
        'is_active' => true,
    ], $overrides));
}

function pendingPurchaseOrder(Supplier $supplier, User $creator, Product $product, int $quantity = 5): PurchaseOrder
{
    $order = PurchaseOrder::query()->create([
        'reference_number' => PurchaseOrder::generateReferenceNumber(),
        'supplier_id' => $supplier->id,
        'status' => 'pending',
        'order_date' => now()->toDateString(),
        'created_by' => $creator->id,
        'subtotal' => $quantity * (float) $product->unit_price,
        'total_amount' => $quantity * (float) $product->unit_price,
    ]);
    $order->items()->create([
        'product_id' => $product->id,
        'quantity_ordered' => $quantity,
        'unit_price' => $product->unit_price,
    ]);

    return $order;
}

test('authenticated user can create a purchase order with immutable item prices', function () {
    $user = User::factory()->create();
    $supplier = purchaseOrderSupplier();
    $product = Product::factory()->create(['unit_price' => 12.50]);

    $this->actingAs($user)
        ->post(route('purchase-orders.store'), [
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'tax' => 2.50,
            'items' => [['product_id' => $product->id, 'quantity_ordered' => 4, 'unit_price' => 10]],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $order = PurchaseOrder::query()->with('items')->firstOrFail();
    expect($order->status)->toBe('pending')
        ->and($order->subtotal)->toBe('40.00')
        ->and($order->total_amount)->toBe('42.50')
        ->and($order->items->first()->unit_price)->toBe('10.00');

    $product->update(['unit_price' => 20]);
    expect($order->items->first()->fresh()->unit_price)->toBe('10.00');
});

test('only managers can approve and warehouse staff can receive partial deliveries', function () {
    $creator = User::factory()->create();
    $manager = purchaseRoleUser('manager');
    $warehouse = purchaseRoleUser('warehouse_staff');
    $supplier = purchaseOrderSupplier();
    $product = Product::factory()->create(['quantity' => 10]);
    $order = pendingPurchaseOrder($supplier, $creator, $product);

    $this->actingAs($warehouse)->post(route('purchase-orders.approve', $order))->assertForbidden();
    $this->actingAs($manager)->post(route('purchase-orders.approve', $order))->assertSessionHas('success');
    expect($order->fresh()->status)->toBe('approved')
        ->and($order->fresh()->approved_by)->toBe($manager->id);

    $this->actingAs($warehouse)->post(route('purchase-orders.receive', $order), [
        'received' => [$order->items()->first()->id => 2],
    ])->assertSessionHas('success');
    expect($product->fresh()->quantity)->toBe(12)
        ->and($order->fresh()->status)->toBe('approved')
        ->and($order->items()->first()->received_quantity)->toBe(2);

    $this->actingAs($warehouse)->post(route('purchase-orders.receive', $order), [
        'received' => [$order->items()->first()->id => 3],
    ])->assertSessionHas('success');
    expect($product->fresh()->quantity)->toBe(15)
        ->and($order->fresh()->status)->toBe('received')
        ->and($order->fresh()->actual_delivery_date)->not->toBeNull();
    $this->assertDatabaseCount('inventory_transactions', 2);
});

test('reorder point follows historical usage and generation avoids duplicate drafts', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $supplier = purchaseOrderSupplier(['minimum_order_quantity' => 5]);
    $product = Product::factory()->create([
        'supplier_id' => $supplier->id,
        'quantity' => 0,
        'reorder_level' => 10,
        'package_size' => 4,
    ]);
    $service = app(\App\Services\ReorderService::class);

    expect($service->calculateReorderPoint($product))->toBe(10)
        ->and($service->calculateOptimalOrderQuantity($product))->toBe(12);

    $first = $service->generatePurchaseOrders();
    $second = $service->generatePurchaseOrders();
    expect($first['orders'])->toBe(1)
        ->and($first['products'])->toBe(1)
        ->and($second['orders'])->toBe(0);
    $this->assertDatabaseHas('purchase_orders', ['status' => 'draft', 'created_by' => $admin->id]);
    $this->assertDatabaseHas('purchase_order_items', ['product_id' => $product->id, 'quantity_ordered' => 12]);

    $usageProduct = Product::factory()->create([
        'supplier_id' => $supplier->id,
        'quantity' => 90,
        'reorder_level' => 20,
    ]);
    InventoryTransaction::withoutEvents(fn () => $usageProduct->inventoryTransactions()->create([
        'type' => 'stock_out',
        'quantity' => 900,
        'user_id' => $admin->id,
        'created_at' => now()->subDays(30),
    ]));
    expect($service->calculateReorderPoint($usageProduct))->toBe(90);
});

test('supplier restore is restricted to administrators and restores archived suppliers', function () {
    $supplier = purchaseOrderSupplier();
    $supplier->delete();

    $this->actingAs(User::factory()->create())
        ->post(route('suppliers.restore', $supplier->id))
        ->assertForbidden();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->post(route('suppliers.restore', $supplier->id))
        ->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success', 'Supplier restored successfully.');

    expect($supplier->fresh())->not->toBeNull();
});

test('purchase order dashboards require authentication and return JSON for authenticated users', function () {
    foreach ([
        '/api/dashboard/pending-orders',
        '/api/dashboard/recent-orders',
        '/api/dashboard/supplier-performance',
        '/api/dashboard/reorder-alerts',
    ] as $endpoint) {
        $this->getJson($endpoint)->assertUnauthorized();
    }

    $this->actingAs(User::factory()->create());
    foreach ([
        '/api/dashboard/pending-orders',
        '/api/dashboard/recent-orders',
        '/api/dashboard/supplier-performance',
        '/api/dashboard/reorder-alerts',
    ] as $endpoint) {
        $this->getJson($endpoint)->assertOk()->assertJsonStructure(['data']);
    }
});

test('authenticated users can download purchase-order and inventory report CSV and PDF files', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $csv = $this->get(route('procurement.export', ['report' => 'products']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($csv->streamedContent())->toContain('Name,SKU,Category');

    $response = $this->get(route('procurement.export.pdf', ['report' => 'products']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    expect(substr($response->getContent(), 0, 4))->toBe('%PDF');
});

test('purchase order approval notification is stored and queued email requires SMTP configuration', function () {
    \Illuminate\Support\Facades\Mail::fake();
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => 'smtp.example.test',
        'mail.from.address' => 'inventory@example.test',
    ]);

    $creator = User::factory()->create();
    $manager = purchaseRoleUser('manager');
    $supplier = purchaseOrderSupplier();
    $product = Product::factory()->create();
    $order = pendingPurchaseOrder($supplier, $creator, $product);

    $this->actingAs($manager)->post(route('purchase-orders.approve', $order))->assertSessionHas('success');

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $creator->id,
        'type' => \App\Notifications\PurchaseOrderStatusNotification::class,
    ]);
    \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\PurchaseOrderApproved::class);
});

test('supplier performance metrics use completed order delivery and receipt data', function () {
    \Illuminate\Support\Facades\Cache::forget('supplier.performance');
    $creator = User::factory()->create();
    $supplier = purchaseOrderSupplier();
    $product = Product::factory()->create();
    $order = pendingPurchaseOrder($supplier, $creator, $product, 4);
    $order->update([
        'status' => 'received',
        'order_date' => now()->subDays(5)->toDateString(),
        'expected_delivery_date' => now()->subDay()->toDateString(),
        'actual_delivery_date' => now()->toDateString(),
    ]);
    $order->items()->update(['received_quantity' => 3]);

    $metrics = collect(app(\App\Services\SupplierPerformanceService::class)->all())
        ->firstWhere('supplier.id', $supplier->id);
    $cachedMetrics = \Illuminate\Support\Facades\Cache::get('supplier.performance');

    expect($metrics['total_orders'])->toBe(1)
        ->and($metrics['on_time_orders'])->toBe(0)
        ->and($metrics['on_time_percentage'])->toBe(0.0)
        ->and($metrics['average_delivery_days'])->toBe(5.0)
        ->and($metrics['order_accuracy'])->toBe(75.0)
        ->and($metrics['total_value'])->toBe(round(4 * (float) $product->unit_price, 2))
        ->and($cachedMetrics[0])->toHaveKey('supplier_id')
        ->and($cachedMetrics[0])->not->toHaveKey('supplier');
});

test('supplier cannot be archived while an order is pending', function () {
    $user = User::factory()->create();
    $supplier = purchaseOrderSupplier();
    $product = Product::factory()->create();
    pendingPurchaseOrder($supplier, $user, $product);

    $this->actingAs($user)
        ->delete(route('suppliers.destroy', $supplier))
        ->assertSessionHasErrors('supplier');

    expect($supplier->fresh())->not->toBeNull();
});

test('stock notifications use the low-stock mailable when SMTP is configured', function () {
    \Illuminate\Support\Facades\Mail::fake();
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => 'smtp.example.test',
        'mail.from.address' => 'inventory@example.test',
    ]);
    $admin = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create(['quantity' => 6, 'reorder_level' => 5]);

    $product->inventoryTransactions()->create([
        'type' => 'stock_out',
        'quantity' => 1,
        'user_id' => $admin->id,
    ]);

    \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\LowStockAlert::class);
});

test('CSV export escapes spreadsheet formula prefixes in user-controlled text', function () {
    $user = User::factory()->create();
    Product::factory()->create([
        'name' => '=HYPERLINK("https://example.test")',
    ]);

    $response = $this->actingAs($user)
        ->get(route('procurement.export', ['report' => 'products']))
        ->assertOk();

    expect($response->streamedContent())->toContain("'=HYPERLINK")
        ->and($response->streamedContent())->not->toContain("\n=HYPERLINK");
});
