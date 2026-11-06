<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->getKey(),
            'product_name' => $this->product->name,
            'product_sku' => $this->product->sku,
            'quantity' => $this->product->quantity,
            'reorder_level' => $this->product->reorder_level,
            'suggested_action' => 'Reorder this product.',
            'url' => route('products.show', $this->product),
        ];
    }

}
