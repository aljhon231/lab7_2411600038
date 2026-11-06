<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\SearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    private const CATEGORIES = [
        'Lumber',
        'Hardware',
        'Tools',
        'Electrical',
        'Plumbing',
        'Paint',
    ];

    public function index(Request $request, SearchService $searchService): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'category' => ['nullable', 'string', 'max:255'],
        ]);
        $products = $searchService->products($filters)
            ->with('supplierRecord')
            ->withCount('inventoryTransactions')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
        $suppliers = Supplier::active()->orderBy('company_name')->get(['id', 'company_name']);

        return view('products.index', compact('products', 'suppliers'));
    }

    public function create(): View
    {
        return view('products.create', [
            'categories' => self::CATEGORIES,
            'suppliers' => Supplier::active()->orderBy('company_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'package_size' => $request->input('package_size', 1),
            'is_active' => $request->input('is_active', true),
        ]);
        $validated = $request->validate($this->validationRules());

        $product = DB::transaction(function () use ($request, $validated) {
            $openingQuantity = $validated['quantity'];
            $product = Product::create([...$validated, 'quantity' => 0]);

            if ($openingQuantity > 0) {
                $product->inventoryTransactions()->create([
                    'type' => 'stock_in',
                    'quantity' => $openingQuantity,
                    'reference_document' => 'Opening stock',
                    'reference_number' => 'Opening stock',
                    'user_id' => $request->user()->getKey(),
                ]);
            }

            return $product;
        });

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product added successfully.');
    }

    public function show(Request $request, Product $product): View
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $allMovementTotal = $product->inventoryTransactions()->selectRaw(
            "COALESCE(SUM(CASE WHEN type = 'stock_in' THEN ABS(quantity) WHEN type = 'stock_out' THEN -ABS(quantity) ELSE quantity END), 0) as movement_total"
        )->first()->movement_total;
        $openingBalance = $product->quantity - (int) $allMovementTotal;

        $historyQuery = $product->inventoryTransactions()
            ->with('user')
            ->when($filters['start_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['end_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
        $transactions = (clone $historyQuery)
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        if ($transactions->isNotEmpty()) {
            $first = $transactions->first();
            $balanceBeforePage = (int) $product->inventoryTransactions()
                ->where(function ($query) use ($first) {
                    $query->where('created_at', '<', $first->created_at)
                        ->orWhere(function ($query) use ($first) {
                            $query->where('created_at', $first->created_at)
                                ->where('id', '<', $first->getKey());
                        });
                })
                ->selectRaw(
                    "COALESCE(SUM(CASE WHEN type = 'stock_in' THEN ABS(quantity) WHEN type = 'stock_out' THEN -ABS(quantity) ELSE quantity END), 0) as movement_total"
                )
                ->first()->movement_total + $openingBalance;

            foreach ($transactions as $transaction) {
                $balanceBeforePage += $transaction->signedQuantity();
                $transaction->running_balance = $balanceBeforePage;
            }
        }

        return view('products.show', compact('product', 'transactions'));
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => self::CATEGORIES,
            'suppliers' => Supplier::active()->orderBy('company_name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $request->merge([
            'package_size' => $request->input('package_size', 1),
            'is_active' => $request->input('is_active', true),
        ]);
        $validated = $request->validate($this->validationRules($product));

        DB::transaction(function () use ($request, $product, $validated) {
            $lockedProduct = Product::query()
                ->lockForUpdate()
                ->findOrFail($product->getKey());
            $quantityChange = $validated['quantity'] - $lockedProduct->quantity;

            $targetQuantity = $validated['quantity'];
            $lockedProduct->update(collect($validated)->except('quantity')->all());

            if ($quantityChange !== 0) {
                $lockedProduct->inventoryTransactions()->create([
                    'type' => $quantityChange > 0 ? 'stock_in' : 'stock_out',
                    'quantity' => abs($quantityChange),
                    'reference_document' => 'Product edit adjustment',
                    'reference_number' => 'Product edit adjustment',
                    'notes' => 'Quantity changed from '.$lockedProduct->quantity.' to '.$targetQuantity.' through product editing.',
                    'user_id' => $request->user()->getKey(),
                ]);
            }
        });

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->inventoryTransactions()->exists()) {
            return redirect()
                ->route('products.index')
                ->withErrors(['product' => 'Products with transaction history cannot be deleted.']);
        }

        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function validationRules(?Product $product = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($product),
            ],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'package_size' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
