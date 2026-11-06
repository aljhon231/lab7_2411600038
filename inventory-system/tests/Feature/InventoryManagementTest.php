<?php

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function createInventoryProduct(array $overrides = []): Product
{
    return Product::create(array_merge([
        'name' => 'Test Cement',
        'sku' => 'TEST-CEM-001',
        'description' => 'Test construction material.',
        'category' => 'Hardware',
        'quantity' => 5,
        'reorder_level' => 3,
        'unit_price' => 100,
        'supplier' => 'Test Supplier',
    ], $overrides));
}

it('protects inventory pages from guests', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('products.index'))->assertRedirect(route('login'));
    $this->get(route('products.create'))->assertRedirect(route('login'));
    $this->get(route('inventory-transactions.index'))->assertRedirect(route('login'));
});

it('shows live inventory statistics on the dashboard', function () {
    $user = User::factory()->create();
    createInventoryProduct(['sku' => 'DASH-OUT', 'quantity' => 0, 'unit_price' => 5]);
    createInventoryProduct(['sku' => 'DASH-LOW', 'quantity' => 3, 'reorder_level' => 5, 'unit_price' => 5]);
    createInventoryProduct(['sku' => 'DASH-OK', 'quantity' => 6, 'reorder_level' => 5, 'unit_price' => 10]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Total Products')
        ->assertSee('Low Stock Items')
        ->assertSee('Out of Stock')
        ->assertSee('₱75.00')
        ->assertSee('Test Cement');
});

it('creates products and records their opening stock', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('products.store'), [
            'name' => 'Concrete Block',
            'sku' => 'BLK-001',
            'description' => 'Standard concrete block.',
            'category' => 'Hardware',
            'quantity' => 12,
            'reorder_level' => 4,
            'unit_price' => 35.50,
            'supplier' => 'Masonry Supply',
        ])
        ->assertRedirect();

    $product = Product::where('sku', 'BLK-001')->firstOrFail();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'quantity' => 12,
    ]);
    $this->assertDatabaseHas('inventory_transactions', [
        'product_id' => $product->id,
        'type' => 'stock_in',
        'quantity' => 12,
        'user_id' => $user->id,
    ]);
});

it('validates required fields and unique SKUs', function () {
    $user = User::factory()->create();
    createInventoryProduct();

    $this->actingAs($user)
        ->from(route('products.create'))
        ->post(route('products.store'), [
            'name' => '',
            'sku' => 'TEST-CEM-001',
            'category' => '',
            'quantity' => -1,
            'reorder_level' => 0,
            'unit_price' => -1,
        ])
        ->assertRedirect(route('products.create'))
        ->assertSessionHasErrors(['name', 'sku', 'category', 'quantity', 'unit_price']);
});

it('allows a product to keep its SKU when updated and records stock changes', function () {
    $user = User::factory()->create();
    $product = createInventoryProduct();

    $this->actingAs($user)
        ->put(route('products.update', $product), [
            'name' => 'Updated Cement',
            'sku' => $product->sku,
            'description' => $product->description,
            'category' => $product->category,
            'quantity' => 2,
            'reorder_level' => $product->reorder_level,
            'unit_price' => $product->unit_price,
            'supplier' => $product->supplier,
        ])
        ->assertRedirect(route('products.show', $product));

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Cement',
        'quantity' => 2,
    ]);
    $this->assertDatabaseHas('inventory_transactions', [
        'product_id' => $product->id,
        'type' => 'stock_out',
        'quantity' => 3,
        'user_id' => $user->id,
    ]);
});

it('updates stock and records stock-in and stock-out movements', function () {
    $user = User::factory()->create();
    $product = createInventoryProduct();

    $this->actingAs($user)
        ->post(route('inventory-transactions.store'), [
            'product_id' => $product->id,
            'type' => 'stock_in',
            'quantity' => 3,
            'reference_document' => 'INV-100',
        ])
        ->assertRedirect(route('inventory-transactions.index'));

    $this->assertSame(8, $product->fresh()->quantity);

    $this->actingAs($user)
        ->post(route('inventory-transactions.store'), [
            'product_id' => $product->id,
            'type' => 'stock_out',
            'quantity' => 4,
            'reference_document' => 'ISS-200',
        ])
        ->assertRedirect(route('inventory-transactions.index'));

    $this->assertSame(4, $product->fresh()->quantity);
    $this->assertDatabaseCount('inventory_transactions', 2);
    $this->assertDatabaseHas('inventory_transactions', [
        'product_id' => $product->id,
        'type' => 'stock_out',
        'quantity' => 4,
        'reference_document' => 'ISS-200',
    ]);
});

it('rejects stock-out quantities that would make stock negative', function () {
    $user = User::factory()->create();
    $product = createInventoryProduct(['quantity' => 2]);

    $this->actingAs($user)
        ->from(route('inventory-transactions.create'))
        ->post(route('inventory-transactions.store'), [
            'product_id' => $product->id,
            'type' => 'stock_out',
            'quantity' => 3,
        ])
        ->assertRedirect(route('inventory-transactions.create'))
        ->assertSessionHasErrors('quantity');

    $this->assertSame(2, $product->fresh()->quantity);
    $this->assertDatabaseCount('inventory_transactions', 0);
});

it('seeds the admin account and sample products idempotently', function () {
    $this->seed();
    $this->seed();

    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    expect($admin->name)->toBe('Admin');
    expect(Hash::check('admin123', $admin->password))->toBeTrue();
    $this->assertDatabaseCount('products', 15);
    $this->assertDatabaseCount(
        'products',
        Product::query()->distinct('sku')->count(),
    );
    $this->assertGreaterThan(0, InventoryTransaction::count());
});

it('allows the seeded admin to log in and log out', function () {
    $this->seed();

    $this->post(route('login'), [
        'email' => 'admin@example.com',
        'password' => 'admin123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $this->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();
});

it('preserves products that have inventory history', function () {
    $user = User::factory()->create(['is_admin' => true]);
    $product = createInventoryProduct();
    InventoryTransaction::create([
        'product_id' => $product->id,
        'type' => 'stock_in',
        'quantity' => $product->quantity,
        'reference_document' => 'Opening stock',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete(route('products.destroy', $product))
        ->assertRedirect(route('products.index'))
        ->assertSessionHasErrors('product');

    $this->assertDatabaseHas('products', ['id' => $product->id]);
    $this->assertDatabaseHas('inventory_transactions', ['product_id' => $product->id]);
});
