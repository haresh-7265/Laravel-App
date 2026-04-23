<?php

namespace App\Listeners;

use App\Events\Order\OrderShipped;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePDF implements ShouldQueue
{

    public function handle(OrderShipped $event): void
    {
        if (!is_null($event->order->invoice_path))
            return;

        $pdf = Pdf::loadView('invoices.invoice', ['order' => $event->order]);

        $filename = "invoices/invoice-{$event->order->order_number}.pdf";

        Storage::disk('public')->put($filename, $pdf->output());

        $event->order->update(['invoice_path' => $filename]);
    }

    public function failed(OrderShipped $event, \Throwable $e): void
    {
        // log it
        Log::channel('order')->error("Invoice PDF failed for order #{$event->order->id}", [
            'error' => $e->getMessage(),
        ]);

        $event->order->update(['invoice_path' => null]);

    }

}
