<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Product $product)
    {
        $this->afterCommit();
    }

    public function build(): static
    {
        return $this->subject('Low stock alert: '.$this->product->name)
            ->view('emails.low-stock-alert');
    }
}
