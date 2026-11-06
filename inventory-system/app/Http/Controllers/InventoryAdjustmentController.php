<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryAdjustmentController extends Controller
{
    public function create(): View
    {
        return view('inventory-adjustments.create', [
            'products' => Product::query()->orderBy('name')->get(),
            'reasons' => ['Physical count discrepancy', 'Damaged goods', 'Theft or loss', 'System error', 'Other'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'physical_count' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'reason' => ['required', 'string', 'in:Physical count discrepancy,Damaged goods,Theft or loss,System error,Other'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $transaction = DB::transaction(function () use ($request, $validated): InventoryTransaction {
            $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
            $difference = $validated['physical_count'] - $product->quantity;

            if ($difference < -2147483648 || $difference > 2147483647) {
                throw ValidationException::withMessages([
                    'physical_count' => 'The adjustment is outside the supported quantity range.',
                ]);
            }

            if ($difference === 0) {
                throw ValidationException::withMessages([
                    'physical_count' => 'The physical count matches the current stock; no adjustment is needed.',
                ]);
            }

            return $product->inventoryTransactions()->create([
                'type' => 'adjustment',
                'quantity' => $difference,
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'reference_document' => 'Physical inventory adjustment',
                'reference_number' => 'Physical inventory adjustment',
                'user_id' => $request->user()->getKey(),
            ]);
        });

        return redirect()
            ->route('products.show', $transaction->product_id)
            ->with('success', 'Inventory adjusted successfully.');
    }
}
