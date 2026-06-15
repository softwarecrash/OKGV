<?php

namespace App\Services;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;

final class InvoicePdfGenerator
{
    public function __construct(
        private readonly AssociationDocumentProfile $profile,
    ) {}

    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['member', 'recipients', 'billingPeriod', 'items.parcel']);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $association = $this->profile->resolve($invoice->association_snapshot);
        $pdf->loadHtml(view('invoices.pdf', compact('invoice', 'association'))->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }
}
