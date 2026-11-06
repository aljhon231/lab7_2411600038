<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort' => ['nullable', Rule::in(['company_name', 'lead_time_days', 'minimum_order_amount', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $sort = $filters['sort'] ?? 'company_name';
        $direction = $filters['direction'] ?? 'asc';

        $supplierQuery = Supplier::query();
        if ($request->boolean('show_archived') && $request->user()->isAdmin()) {
            $supplierQuery->withTrashed()->whereNotNull('deleted_at');
        }

        $suppliers = $supplierQuery
            ->withCount('products')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('company_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $status): void {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('suppliers.create', ['supplier' => new Supplier]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::query()->create($request->validate($this->validationRules()));
        Cache::forget('supplier.performance');

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load(['products' => fn ($query) => $query->orderBy('name')]);
        $recentPurchaseOrders = Schema::hasTable('purchase_orders')
            ? $supplier->purchaseOrders()->latest()->limit(5)->get()
            : collect();

        return view('suppliers.show', compact('supplier', 'recentPurchaseOrders'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validate($this->validationRules($supplier)));
        Cache::forget('supplier.performance');

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if (Schema::hasTable('purchase_orders')
            && $supplier->purchaseOrders()->whereIn('status', ['draft', 'pending', 'approved', 'sent'])->exists()) {
            return redirect()
                ->route('suppliers.index')
                ->withErrors(['supplier' => 'This supplier cannot be deleted while purchase orders are awaiting completion.']);
        }

        $supplier->delete();
        Cache::forget('supplier.performance');

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier archived successfully.');
    }

    public function restore(int $supplier): RedirectResponse
    {
        abort_unless(request()->user()->isAdmin(), 403);
        $archivedSupplier = Supplier::withTrashed()->findOrFail($supplier);
        if (Supplier::query()->where('email', $archivedSupplier->email)->exists()) {
            return redirect()->route('suppliers.index', ['show_archived' => 1])
                ->withErrors(['supplier' => 'A current supplier already uses this email address.']);
        }

        $archivedSupplier->restore();
        Cache::forget('supplier.performance');

        return redirect()->route('suppliers.index')->with('success', 'Supplier restored successfully.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function validationRules(?Supplier $supplier = null): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($supplier),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+().\\-\\s]{7,30}$/'],
            'address' => ['nullable', 'string', 'max:5000'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'lead_time_days' => ['required', 'integer', 'min:0', 'max:65535'],
            'minimum_order_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'minimum_order_quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
