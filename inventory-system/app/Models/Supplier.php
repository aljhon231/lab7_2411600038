<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'payment_terms',
        'lead_time_days',
        'minimum_order_amount',
        'minimum_order_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'lead_time_days' => 'integer',
            'minimum_order_amount' => 'decimal:2',
            'minimum_order_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithLeadTime(Builder $query, int $days): Builder
    {
        return $query->where('lead_time_days', '<=', max(0, $days));
    }

    public function getFullContactAttribute(): string
    {
        return implode(' · ', array_filter([
            $this->contact_person,
            $this->email,
            $this->phone,
        ]));
    }

    public function getPerformanceRatingAttribute(): ?float
    {
        if (! Schema::hasTable('purchase_orders')
            || ! Schema::hasColumn('purchase_orders', 'actual_delivery_date')
            || ! Schema::hasColumn('purchase_orders', 'expected_delivery_date')) {
            return null;
        }

        $completedOrders = DB::table('purchase_orders')
            ->where('supplier_id', $this->getKey())
            ->where('status', 'received')
            ->whereNotNull('actual_delivery_date')
            ->whereNotNull('expected_delivery_date');
        $total = (clone $completedOrders)->count();

        if ($total === 0) {
            return null;
        }

        $onTime = (clone $completedOrders)
            ->whereColumn('actual_delivery_date', '<=', 'expected_delivery_date')
            ->count();

        return round(($onTime / $total) * 100, 2);
    }
}
