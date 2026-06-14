<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\InvoicePdfGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoicePdfGenerator $pdfGenerator) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with(['member', 'billingPeriod'])
            ->when(
                ! $request->user()->role->canManageBilling(),
                fn ($query) => $query
                    ->whereHas('member', fn ($query) => $query
                        ->where('user_id', $request->user()->id))
                    ->where('status', 'approved'),
            )
            ->latest('issued_at')
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);
        $invoice->load(['member', 'billingPeriod', 'items.parcel', 'approver']);

        return view('invoices.show', compact('invoice'));
    }

    public function pdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        return response($this->pdfGenerator->render($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"Rechnung-{$invoice->invoice_number}.pdf\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
