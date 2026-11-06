<?php

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Notification;

function trackingProduct(array $attributes = []): Product
{
    return Product::factory()->create(array_merge([
        'quantity' => 50,
        'reorder_level' => 10,
    ], $attributes));
}

test('stock adjustment records the signed difference and lets the observer update stock', function () {
    $user = User::factory()->create();
    $product = trackingProduct();

    $this->actingAs($user)
        ->post(route('inventory-adjustments.store'), [
            'product_id' => $product->id,
            'physical_count' => 45,
            'reason' => 'Physical count discrepancy',
            'notes' => 'Counted in the storeroom.',
        ])
        ->assertRedirect(route('products.show', $product))
        ->assertSessionHas('success');

    expect($product->fresh()->quantity)->toBe(45);
    $this->assertDatabaseHas('inventory_transactions', [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'quantity' => -5,
        'reason' => 'Physical count discrepancy',
        'user_id' => $user->id,
    ]);
});

test('administrators can reverse a transaction without erasing its audit record', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $product = trackingProduct();

    $this->actingAs($admin)
        ->post(route('inventory-transactions.store'), [
            'product_id' => $product->id,
            'type' => 'stock_out',
            'quantity' => 3,
        ])
        ->assertRedirect(route('inventory-transactions.index'));

    $transaction = InventoryTransaction::query()->where('type', 'stock_out')->firstOrFail();

    $this->delete(route('inventory-transactions.destroy', $transaction))
        ->assertRedirect(route('inventory-transactions.index'))
        ->assertSessionHas('success');

    expect($product->fresh()->quantity)->toBe(50);
    $this->assertSoftDeleted('inventory_transactions', ['id' => $transaction->id]);
    $this->assertDatabaseHas('inventory_transactions', [
        'product_id' => $product->id,
        'type' => 'adjustment',
        'quantity' => 3,
        'reason' => 'Transaction reversal',
        'user_id' => $admin->id,
    ]);
});

test('non-administrators cannot reverse transactions', function () {
    $user = User::factory()->create();
    $product = trackingProduct();
    $transaction = InventoryTransaction::factory()->for($product)->for($user)->create();

    $this->actingAs($user)
        ->delete(route('inventory-transactions.destroy', $transaction))
        ->assertForbidden();
});

test('low stock notifications are sent once per low-stock period', function () {
    Notification::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $product = trackingProduct(['quantity' => 6, 'reorder_level' => 5]);

    $makeTransaction = fn (string $type, int $quantity) => $product->inventoryTransactions()->create([
        'type' => $type,
        'quantity' => $quantity,
        'user_id' => $admin->id,
    ]);

    $makeTransaction('stock_out', 1);
    $makeTransaction('stock_out', 1);
    expect($product->fresh()->low_stock_notified)->toBeTrue();
    expect(Notification::sent($admin, LowStockNotification::class))->toHaveCount(1);

    $makeTransaction('stock_in', 2);
    expect($product->fresh()->low_stock_notified)->toBeFalse();
    $makeTransaction('stock_out', 1);

    expect($product->fresh()->quantity)->toBe(5);
    expect(Notification::sent($admin, LowStockNotification::class))->toHaveCount(2);
});

test('authenticated dashboard APIs return inventory data and guests are rejected', function () {
    trackingProduct(['quantity' => 0, 'unit_price' => 5]);

    $this->getJson('/api/dashboard/stats')->assertUnauthorized();

    $this->actingAs(User::factory()->create())
        ->getJson('/api/dashboard/stats')
        ->assertOk()
        ->assertJsonPath('data.total_products', 1)
        ->assertJsonPath('data.low_stock_items', 1)
        ->assertJsonPath('data.out_of_stock_items', 1)
        ->assertJsonPath('data.inventory_value', 0);
});

test('inventory reports render and export database rows as CSV', function () {
    $user = User::factory()->create();
    $product = trackingProduct();
    $product->inventoryTransactions()->create([
        'type' => 'stock_in',
        'quantity' => 2,
        'reference_number' => 'REF-42',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee('Stock movement report')
        ->assertSee($product->name);

    $response = $this->get(route('reports.export', ['report' => 'movements']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())->toContain('REF-42');
});
