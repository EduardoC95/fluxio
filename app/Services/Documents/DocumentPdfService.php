<?php

namespace App\Services\Documents;

use App\Models\CompanySetting;
use App\Models\Order;
use App\Models\Proposal;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentPdfService
{
    public function proposal(Proposal $proposal, array $document, ?CompanySetting $company)
    {
        return Pdf::loadView('pdf.document', [
            'documentType' => 'Proposta',
            'document' => $document,
            'company' => $company,
        ])->download(sprintf('%s.pdf', $proposal->number));
    }

    public function order(Order $order, array $document, ?CompanySetting $company)
    {
        return Pdf::loadView('pdf.document', [
            'documentType' => 'Encomenda',
            'document' => $document,
            'company' => $company,
        ])->download(sprintf('%s.pdf', $order->number));
    }
}
