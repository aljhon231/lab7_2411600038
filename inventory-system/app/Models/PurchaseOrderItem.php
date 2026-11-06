<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity_ordered',
        'unit_price',
        'received_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'integer',
            'unit_price' => 'decimal:2',
            'received_quantity' => 'integer',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getLineTotalAttribute(): float
    {
        return round($this->quantity_ordered * (float) $this->unit_price, 2);
    }

    public function getOutstandingQuantityAttribute(): int
    {
        return max(0, $this->quantity_ordered - $this->received_quantity);
    }
}
