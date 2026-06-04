<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * Create a new message instance.
     */
    public function __construct(public Order $order)
    {
        $this->order->load('items.product');
        $this->onQueue('emails');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmation #' . $this->order->order_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.confirmation',
            with: [
                'url' => route('orders.show', $this->order),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->order->invoice_path) {
            if (Storage::disk('local')->exists($this->order->invoice_path)) {
                $attachments[] = Attachment::fromStorageDisk('public', $this->order->invoice_path)
                    ->as("invoice-{$this->order->order_number}.pdf")
                    ->withMime('application/pdf');
            } else {
                Log::channel('order')->warning("Invoice PDF missing for order {$this->order->order_number} at {$this->order->invoice_path}");
            }
        }

        return $attachments;
    }
}
