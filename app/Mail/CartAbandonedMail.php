<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CartAbandonedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Collection $cartItems,
        public readonly float $cartTotal,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'You left something behind! 🛒');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.cart.abandoned');
    }
}
