<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PurchaseOrderStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public PurchaseOrder $purchaseOrder, public string $message)
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
            'purchase_order_id' => $this->purchaseOrder->getKey(),
            'reference_number' => $this->purchaseOrder->reference_number,
            'message' => $this->message,
            'url' => route('purchase-orders.show', $this->purchaseOrder),
        ];
    }
}
