<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventoryTransactionController extends Controller
{
    public function index(Request $request, SearchService $searchService): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => ['nullable', 'in:stock_in,stock_out,adjustment'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $transactions = $searchService->transactions($filters)
            ->with(['product', 'user'])
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $products = Product::query()->orderBy('name')->get(['id', 'name']);
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('inventory-transactions.index', compact('transactions', 'products', 'users'));
    }

    public function create(): View
    {
        $products = Product::query()->orderBy('name')->get();

        return view('inventory-transactions.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'in:stock_in,stock_out'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'reference_document' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($validated['product_id']);

            if ($validated['type'] === 'stock_out' && $validated['quantity'] > $product->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => 'The stock-out quantity cannot be greater than the available stock.',
                ]);
            }

            $product->inventoryTransactions()->create([
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reference_document' => $validated['reference_number'] ?? $validated['reference_document'] ?? null,
                'reference_number' => $validated['reference_number'] ?? $validated['reference_document'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'user_id' => $request->user()->getKey(),
            ]);
        });

        return redirect()
            ->route('inventory-transactions.index')
            ->with('success', 'Inventory transaction recorded successfully.');
    }

    public function show(InventoryTransaction $inventory_transaction): View
    {
        $inventory_transaction->load(['product', 'user']);

        return view('inventory-transactions.show', ['transaction' => $inventory_transaction]);
    }

    public function destroy(Request $request, InventoryTransaction $inventory_transaction): RedirectResponse
    {
        DB::transaction(function () use ($request, $inventory_transaction): void {
            $transaction = InventoryTransaction::query()
                ->lockForUpdate()
                ->findOrFail($inventory_transaction->getKey());
            $product = Product::query()->lockForUpdate()->findOrFail($transaction->product_id);
            $reversal = -$transaction->signedQuantity();

            if ($product->quantity + $reversal < 0) {
                throw ValidationException::withMessages([
                    'transaction' => 'This transaction cannot be reversed because it would result in negative stock.',
                ]);
            }

            $product->inventoryTransactions()->create([
                'type' => 'adjustment',
                'quantity' => $reversal,
                'reference_document' => 'Reversal of transaction #'.$transaction->getKey(),
                'reference_number' => 'Reversal of transaction #'.$transaction->getKey(),
                'notes' => 'Reversal before authorized deletion of transaction #'.$transaction->getKey().'.',
                'reason' => 'Transaction reversal',
                'user_id' => $request->user()->getKey(),
            ]);

            $transaction->delete();
        });

        return redirect()
            ->route('inventory-transactions.index')
            ->with('success', 'Transaction reversed and archived successfully.');
    }
}
