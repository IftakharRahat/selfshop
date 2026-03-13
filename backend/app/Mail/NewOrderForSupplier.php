<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderForSupplier extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public Vendor $vendor;
    public $orderProducts;
    public ?string $customerName;

    public function __construct(Order $order, Vendor $vendor, $orderProducts, ?string $customerName = null)
    {
        $this->order = $order;
        $this->vendor = $vendor;
        $this->orderProducts = $orderProducts;
        $this->customerName = $customerName;
    }

    public function build()
    {
        return $this->subject('New Order Received – ' . $this->order->invoiceID)
                    ->view('emails.supplier_new_order');
    }
}
