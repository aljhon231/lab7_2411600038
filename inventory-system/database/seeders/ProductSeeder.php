<?php

namespace Database\Seeders;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * @var array<int, array<string, string|int|float|null>>
     */
    private array $products = [
        ['name' => 'Portland Cement', 'sku' => 'CEM-001', 'description' => '40 kg bag of Portland cement.', 'category' => 'Hardware', 'quantity' => 48, 'reorder_level' => 10, 'unit_price' => 280.00, 'supplier' => 'BuildRight Supply'],
        ['name' => 'Marine Plywood 1/2 inch', 'sku' => 'PLY-001', 'description' => 'Water-resistant plywood sheet.', 'category' => 'Lumber', 'quantity' => 8, 'reorder_level' => 12, 'unit_price' => 850.00, 'supplier' => 'Northside Lumber'],
        ['name' => 'Common Nails 2 inch', 'sku' => 'NAI-001', 'description' => 'One-kilogram pack of common nails.', 'category' => 'Hardware', 'quantity' => 0, 'reorder_level' => 10, 'unit_price' => 95.00, 'supplier' => 'BuildRight Supply'],
        ['name' => 'Claw Hammer', 'sku' => 'TLS-001', 'description' => '16 oz steel claw hammer.', 'category' => 'Tools', 'quantity' => 16, 'reorder_level' => 5, 'unit_price' => 320.00, 'supplier' => 'Handy Tools Co.'],
        ['name' => 'Phillips Screwdriver Set', 'sku' => 'TLS-002', 'description' => 'Six-piece magnetic screwdriver set.', 'category' => 'Tools', 'quantity' => 11, 'reorder_level' => 5, 'unit_price' => 450.00, 'supplier' => 'Handy Tools Co.'],
        ['name' => 'Electric Drill 500W', 'sku' => 'TLS-003', 'description' => 'Corded 500 watt electric drill.', 'category' => 'Tools', 'quantity' => 4, 'reorder_level' => 5, 'unit_price' => 2450.00, 'supplier' => 'Handy Tools Co.'],
        ['name' => 'PVC Pipe 1/2 inch', 'sku' => 'PLB-001', 'description' => 'Three-meter PVC water pipe.', 'category' => 'Plumbing', 'quantity' => 35, 'reorder_level' => 10, 'unit_price' => 120.00, 'supplier' => 'AquaFlow Trading'],
        ['name' => 'Interior Latex Paint White', 'sku' => 'PNT-001', 'description' => 'One-gallon interior latex paint.', 'category' => 'Paint', 'quantity' => 13, 'reorder_level' => 6, 'unit_price' => 680.00, 'supplier' => 'Color House'],
        ['name' => 'Electrical Wire 2.0 mm', 'sku' => 'ELC-001', 'description' => '100-meter roll of copper electrical wire.', 'category' => 'Electrical', 'quantity' => 6, 'reorder_level' => 8, 'unit_price' => 1850.00, 'supplier' => 'BrightWire Electrical'],
        ['name' => 'Steel Reinforcing Bar 10 mm', 'sku' => 'HRD-001', 'description' => 'Six-meter steel reinforcing bar.', 'category' => 'Hardware', 'quantity' => 42, 'reorder_level' => 15, 'unit_price' => 235.00, 'supplier' => 'Metro Steel Depot'],
        ['name' => 'Corrugated Roofing Sheet', 'sku' => 'HRD-002', 'description' => 'Eight-foot galvanized roofing sheet.', 'category' => 'Hardware', 'quantity' => 18, 'reorder_level' => 8, 'unit_price' => 520.00, 'supplier' => 'Metro Steel Depot'],
        ['name' => 'Door Lock Set', 'sku' => 'HRD-003', 'description' => 'Stainless steel entrance door lock.', 'category' => 'Hardware', 'quantity' => 7, 'reorder_level' => 8, 'unit_price' => 640.00, 'supplier' => 'SecureHome Products'],
        ['name' => 'Chrome Water Faucet', 'sku' => 'PLB-002', 'description' => 'Single-handle chrome basin faucet.', 'category' => 'Plumbing', 'quantity' => 9, 'reorder_level' => 5, 'unit_price' => 780.00, 'supplier' => 'AquaFlow Trading'],
        ['name' => 'Measuring Tape 5 m', 'sku' => 'TLS-004', 'description' => 'Five-meter retractable measuring tape.', 'category' => 'Tools', 'quantity' => 22, 'reorder_level' => 8, 'unit_price' => 180.00, 'supplier' => 'Handy Tools Co.'],
        ['name' => 'Sandpaper Assorted Pack', 'sku' => 'TLS-005', 'description' => 'Ten-sheet assorted-grit sandpaper pack.', 'category' => 'Tools', 'quantity' => 5, 'reorder_level' => 10, 'unit_price' => 75.00, 'supplier' => 'Color House'],
    ];

    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        foreach ($this->products as $attributes) {
            $product = Product::firstOrNew(['sku' => $attributes['sku']]);
            $isNew = ! $product->exists;
            $product->fill(array_diff_key($attributes, ['quantity' => true]));

            if ($isNew) {
                $product->quantity = 0;
            }

            $product->save();

            if ($isNew && $attributes['quantity'] > 0) {
                InventoryTransaction::create([
                    'product_id' => $product->getKey(),
                    'type' => 'stock_in',
                    'quantity' => $attributes['quantity'],
                    'reference_document' => 'Seeded opening stock',
                    'reference_number' => 'Seeded opening stock',
                    'user_id' => $admin->getKey(),
                ]);
            }
        }
    }
}
