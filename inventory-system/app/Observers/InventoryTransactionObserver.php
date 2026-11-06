<?php

namespace App\Observers;

use App\Mail\LowStockAlert;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class InventoryTransactionObserver
{
    public function creating(InventoryTransaction $transaction): void
    {
        if ($transaction->type === 'adjustment' && (int) $transaction->quantity === 0) {
            throw ValidationException::withMessages([
                'quantity' => 'An adjustment must change stock by a nonzero amount.',
            ]);
        }

        if (in_array($transaction->type, ['stock_in', 'stock_out'], true) && (int) $transaction->quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'Stock-in and stock-out quantities must be positive.',
            ]);
        }
    }

    public function created(InventoryTransaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $product = Product::query()
                ->lockForUpdate()
                ->findOrFail($transaction->product_id);
            $newQuantity = $product->quantity + $transaction->signedQuantity();

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'This transaction would reduce inventory below zero.',
                ]);
            }

            $product->quantity = $newQuantity;
            $product->save();
            $this->updateLowStockNotificationState($product);
        });
    }

    private function updateLowStockNotificationState(Product $product): void
    {
        if (! $product->isLowStock()) {
            if ($product->low_stock_notified) {
                $product->forceFill(['low_stock_notified' => false])->saveQuietly();
            }

            return;
        }

        if ($product->low_stock_notified) {
            return;
        }

        $administrators = User::query()
            ->where(fn ($query) => $query->where('is_admin', true)->orWhere('role', 'admin'))
            ->get();
        if ($administrators->isEmpty()) {
            return;
        }

        Notification::send($administrators, new LowStockNotification($product));
        if (config('mail.default') === 'smtp'
            && filled(config('mail.mailers.smtp.host'))
            && filled(config('mail.from.address'))) {
            foreach ($administrators as $administrator) {
                Mail::to($administrator->email)->queue(new LowStockAlert($product));
            }
        }
        $product->forceFill(['low_stock_notified' => true])->saveQuietly();
    }
}
