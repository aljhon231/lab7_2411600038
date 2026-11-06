<?php

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

test('authenticated users can create, view, update, and soft delete suppliers', function () {
    $user = User::factory()->create();
    $attributes = [
        'company_name' => 'North Supply Co.',
        'contact_person' => 'Alex Rivera',
        'email' => 'alex@north-supply.test',
        'phone' => '+63 912 345 6789',
        'address' => 'Quezon City',
        'payment_terms' => 'Net 30',
        'lead_time_days' => 7,
        'minimum_order_amount' => 2500,
        'minimum_order_quantity' => 1,
        'is_active' => 1,
    ];

    $this->actingAs($user)
        ->post(route('suppliers.store'), $attributes)
        ->assertRedirect()
        ->assertSessionHas('success', 'Supplier created successfully.');

    $supplier = Supplier::query()->where('email', $attributes['email'])->firstOrFail();
    $this->get(route('suppliers.index'))->assertOk()->assertSee('North Supply Co.');
    $this->get(route('suppliers.show', $supplier))->assertOk()->assertSee('Alex Rivera');
    $this->get(route('suppliers.edit', $supplier))->assertOk();

    $this->put(route('suppliers.update', $supplier), [
        ...$attributes,
        'company_name' => 'North Supply Incorporated',
    ])->assertRedirect(route('suppliers.show', $supplier));

    expect($supplier->fresh()->company_name)->toBe('North Supply Incorporated');

    $this->delete(route('suppliers.destroy', $supplier))
        ->assertRedirect(route('suppliers.index'))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    $this->get(route('suppliers.index'))->assertDontSee('North Supply Incorporated');
});

test('supplier search and active filter can be combined', function () {
    $user = User::factory()->create();
    Supplier::query()->create([
        'company_name' => 'Active Timber',
        'email' => 'active@timber.test',
        'lead_time_days' => 7,
        'minimum_order_amount' => 0,
        'minimum_order_quantity' => 1,
        'is_active' => true,
    ]);
    Supplier::query()->create([
        'company_name' => 'Inactive Timber',
        'email' => 'inactive@timber.test',
        'lead_time_days' => 7,
        'minimum_order_amount' => 0,
        'minimum_order_quantity' => 1,
        'is_active' => false,
    ]);

    $this->actingAs($user)
        ->get(route('suppliers.index', ['search' => 'timber', 'status' => 'active']))
        ->assertOk()
        ->assertSee('Active Timber')
        ->assertDontSee('Inactive Timber');
});

test('products can be linked to suppliers without changing the legacy supplier text', function () {
    $supplier = Supplier::query()->create([
        'company_name' => 'Linked Supplier',
        'email' => 'linked@supplier.test',
        'lead_time_days' => 7,
        'minimum_order_amount' => 0,
        'minimum_order_quantity' => 1,
        'is_active' => true,
    ]);
    $product = Product::factory()->create([
        'supplier' => 'Existing supplier label',
        'supplier_id' => $supplier->id,
    ]);

    expect($supplier->products()->first()->is($product))->toBeTrue()
        ->and($product->supplier)->toBe('Existing supplier label')
        ->and($product->supplierRecord->is($supplier))->toBeTrue();
});
