<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PurchaseOrder $purchaseOrder)
    {
        $this->afterCommit();
    }

    public function build(): static
    {
        return $this->subject('Purchase order submitted: '.$this->purchaseOrder->reference_number)
            ->view('emails.purchase-order-created');
    }
}
