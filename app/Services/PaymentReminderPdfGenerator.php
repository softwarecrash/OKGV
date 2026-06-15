<?php

namespace App\Services;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;

final class PaymentReminderPdfGenerator
{
    public function __construct(
        private readonly AssociationDocumentProfile $profile,
    ) {}

    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['recipients', 'billingPeriod']);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $association = $this->profile->get();
        $pdf->loadHtml(view('invoices.payment-reminder-pdf', compact('invoice', 'association'))->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }
}
