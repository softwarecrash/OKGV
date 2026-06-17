<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class InvoicePdfArchive
{
    public function __construct(
        private readonly InvoicePdfGenerator $pdfGenerator,
    ) {}

    public function store(Invoice $invoice): Invoice
    {
        $invoice->loadMissing('billingPeriod');

        $directory = sprintf(
            'invoices/%s',
            $invoice->billingPeriod?->starts_at?->format('Y') ?? 'unknown',
        );
        $filename = sprintf(
            'rechnung-%s.pdf',
            Str::slug($invoice->invoice_number, '-'),
        );
        $path = "{$directory}/{$filename}";

        Storage::disk('local')->put($path, $this->pdfGenerator->render($invoice));

        $invoice->forceFill([
            'pdf_path' => $path,
            'pdf_generated_at' => now(),
        ])->save();

        return $invoice->refresh();
    }

    public function content(Invoice $invoice): string
    {
        if ($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path)) {
            return Storage::disk('local')->get($invoice->pdf_path);
        }

        return $this->pdfGenerator->render($invoice);
    }
}
