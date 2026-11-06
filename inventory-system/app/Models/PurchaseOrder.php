<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'pending', 'approved', 'sent', 'received', 'cancelled', 'rejected'];

    protected $fillable = [
        'reference_number',
        'supplier_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'subtotal',
        'tax',
        'total_amount',
        'notes',
        'terms',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'actual_delivery_date' => 'date',
            'approved_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['received', 'cancelled', 'rejected']);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function calculateTotal(): float
    {
        $subtotal = (float) $this->items()
            ->selectRaw('COALESCE(SUM(quantity_ordered * unit_price), 0) as subtotal')
            ->value('subtotal');

        return round($subtotal + (float) $this->tax, 2);
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeReceived(): bool
    {
        return in_array($this->status, ['approved', 'sent'], true)
            && $this->items()->whereColumn('received_quantity', '<', 'quantity_ordered')->exists();
    }

    public static function generateReferenceNumber(): string
    {
        do {
            $reference = 'PO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (static::withTrashed()->where('reference_number', $reference)->exists());

        return $reference;
    }
}
