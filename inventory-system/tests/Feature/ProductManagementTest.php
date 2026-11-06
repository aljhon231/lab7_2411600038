<?php

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('inventory pages require authentication', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('products.index'))->assertRedirect(route('login'));
    $this->get(route('inventory-transactions.index'))->assertRedirect(route('login'));
});

test('admin account is seeded with a hashed password', function () {
    $this->seed();

    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    expect($admin->name)->toBe('Admin')
        ->and(Hash::check('admin123', $admin->password))->toBeTrue();
});

test('authenticated users can view product resource pages', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertOk();
    $this->get(route('products.create'))->assertOk();
    $this->get(route('products.show', $product))->assertOk();
    $this->get(route('products.edit', $product))->assertOk();
});

test('dashboard counts all products at or below the reorder level as low stock', function () {
    $user = User::factory()->create();
    Product::factory()->create(['quantity' => 0, 'reorder_level' => 10]);
    Product::factory()->create(['quantity' => 10, 'reorder_level' => 10]);
    Product::factory()->create(['quantity' => 11, 'reorder_level' => 10]);

    $response = $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    expect($response->viewData('statistics')['lowStockItems'])->toBe(2);
});

test('creating a product stores its opening stock transaction', function () {
    $user = User::factory()->create();
    $attributes = Product::factory()->raw([
        'quantity' => 12,
        'category' => 'Masonry',
    ]);

    $this->actingAs($user)
        ->post(route('products.store'), $attributes)
        ->assertRedirect()
        ->assertSessionHas('success', 'Product added successfully.');

    $product = Product::where('sku', $attributes['sku'])->firstOrFail();

    expect($product->quantity)->toBe(12);
    $this->assertDatabaseHas('inventory_transactions', [
        'product_id' => $product->id,
        'type' => 'stock_in',
        'quantity' => 12,
        'user_id' => $user->id,
    ]);
});

test('product validation requires fields and prevents duplicate SKUs', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('products.store'), [])
        ->assertSessionHasErrors([
            'name',
            'sku',
            'category',
            'quantity',
            'reorder_level',
            'unit_price',
        ]);

    $existingProduct = Product::factory()->create();
    $duplicateSku = Product::factory()->raw(['sku' => $existingProduct->sku]);

    $this->post(route('products.store'), $duplicateSku)
        ->assertSessionHasErrors('sku');

    $invalidQuantity = Product::factory()->raw(['quantity' => -1]);
    $this->post(route('products.store'), $invalidQuantity)
        ->assertSessionHasErrors('quantity');
});

test('updating a product may keep its own SKU and records stock changes', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['quantity' => 5]);
    $attributes = [
        ...$product->only([
            'name',
            'sku',
            'description',
            'category',
            'reorder_level',
            'unit_price',
            'supplier',
        ]),
        'quantity' => 8,
    ];

    $this->actingAs($user)
        ->put(route('products.update', $product), $attributes)
        ->assertRedirect(route('products.show', $product))
        ->assertSessionHas('success', 'Product updated successfully.');

    expect($product->fresh()->quantity)->toBe(8);
    $this->assertDatabaseHas('inventory_transactions', [
        'product_id' => $product->id,
        'type' => 'stock_in',
        'quantity' => 3,
        'user_id' => $user->id,
    ]);
});

test('preserves transaction history when deleting a product is requested', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $product = Product::factory()->create();
    $transaction = InventoryTransaction::factory()->for($product)->for($user)->create();

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'))
        ->assertSessionHasErrors('product');

    $this->assertDatabaseHas('products', ['id' => $product->id]);
    $this->assertDatabaseHas('inventory_transactions', ['id' => $transaction->id]);
});

test('product stock checks include the reorder threshold and zero stock', function () {
    $lowStockProduct = Product::factory()->create([
        'quantity' => 10,
        'reorder_level' => 10,
    ]);
    $outOfStockProduct = Product::factory()->create([
        'quantity' => 0,
        'reorder_level' => 10,
    ]);

    expect($lowStockProduct->isLowStock())->toBeTrue()
        ->and($lowStockProduct->isOutOfStock())->toBeFalse()
        ->and($outOfStockProduct->isLowStock())->toBeTrue()
        ->and($outOfStockProduct->isOutOfStock())->toBeTrue();
});
