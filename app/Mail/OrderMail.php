<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public function __construct(
        public readonly Order $order,
        public string $event // 'placed' | 'shipped' | 'paid' | 'delivered'
    ) {
    }

    public function envelope(): Envelope
    {
        $subjects = [
            'placed' => 'Order Placed - #' . $this->order->order_number,
            'shipped' => 'Order Shipped - #' . $this->order->order_number,
            'paid' => 'Payment Confirmed - #' . $this->order->order_number,
            'delivered' => 'Order Delivered - #' . $this->order->order_number,
        ];

        return new Envelope(subject: $subjects[$this->event], to:$this->order->shipping_email);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.orders.order'); // single view
    }
}
