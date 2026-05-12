<?php

namespace App\Mail;

use App\Models\CompanySetting;
use App\Models\SupplierInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupplierPaymentProofMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SupplierInvoice $invoice,
        public ?CompanySetting $company = null,
        public ?string $attachmentPath = null,
    ) {}

    public function build(): static
    {
        $mail = $this
            ->subject(sprintf('Comprovativo de Pagamento - Fatura "%s"', $this->invoice->number))
            ->view('emails.supplier-payment-proof');

        if ($this->attachmentPath) {
            $mail->attachFromStorageDisk('local', $this->attachmentPath);
        }

        return $mail;
    }
}
