<?php

namespace App\Jobs;

use App\Models\Admin;
use App\Models\Order;
use App\Notifications\JobFailedAlert;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(public Order $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! is_null($this->order->invoice_path)) {
            return;
        }

        $pdf = Pdf::loadView('invoices.invoice', ['order' => $this->order]);

        $filename = "invoices/invoice-{$this->order->order_number}.pdf";

        Storage::disk('public')->put($filename, $pdf->output());

        $this->order->update(['invoice_path' => $filename]);
    }

    public function failed(\Throwable $e): void
    {
        // Log the failure
        \Log::channel('order')->error("Failed to Generate Invoice Pdf for order #{$this->order->id}", [
            'error' => $e->getMessage(),
            'file'  => __FILE__,
            'line'  => __LINE__,
        ]);

        $this->order->update(['invoice_path' => null]);

        // Notify all admin users about the failure
        Notification::send(
            Admin::all(),
            new JobFailedAlert(
                jobName: 'GenerateInvoicePdf',
                errorMessage: $e->getMessage(),
                context: [
                    'order_id'     => $this->order->id,
                    'order_number' => $this->order->order_number,
                ]
            )
        );
    }
}
