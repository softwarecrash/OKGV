<?php

namespace App\Services;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;

final class PaymentReminderPdfGenerator
{
    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['recipients', 'billingPeriod']);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('invoices.payment-reminder-pdf', compact('invoice'))->render(), 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();

        return $pdf->output();
    }
}
