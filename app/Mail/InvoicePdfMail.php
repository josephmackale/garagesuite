<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoicePdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $pdfBinary
    ) {}

    public function build()
    {
        $subject = 'Invoice ' . ($this->invoice->invoice_number ?? ('INV-'.$this->invoice->id));

        return $this->subject($subject)
            ->view('emails.invoice-pdf', ['invoice' => $this->invoice])
            ->attachData(
                $this->pdfBinary,
                ($this->invoice->invoice_number ?? ('INV-'.$this->invoice->id)) . '.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
