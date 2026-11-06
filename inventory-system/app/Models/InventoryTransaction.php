<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'type',
        'quantity',
        'reference_document',
        'reference_number',
        'notes',
        'reason',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeStockIn(Builder $query): Builder
    {
        return $query->where('type', 'stock_in');
    }

    public function scopeStockOut(Builder $query): Builder
    {
        return $query->where('type', 'stock_out');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->latest();
    }

    public function getReferenceNumberAttribute(?string $value): ?string
    {
        return $value ?? $this->attributes['reference_document'] ?? null;
    }

    public function signedQuantity(): int
    {
        return match ($this->type) {
            'stock_in' => abs($this->quantity),
            'stock_out' => -abs($this->quantity),
            'adjustment' => $this->quantity,
            default => 0,
        };
    }
}
