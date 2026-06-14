<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\PaymentReminderPdfGenerator;
use Illuminate\Http\Response;

class PaymentReminderController extends Controller
{
    public function __construct(
        private readonly PaymentReminderPdfGenerator $pdfGenerator,
    ) {}

    public function pdf(Invoice $invoice): Response
    {
        $this->authorize('reminder', $invoice);
        abort_unless($invoice->canReceivePaymentReminder(), 422);

        return response($this->pdfGenerator->render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"Zahlungserinnerung-{$invoice->invoice_number}.pdf\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
