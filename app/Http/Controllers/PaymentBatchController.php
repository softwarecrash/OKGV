<?php

namespace App\Http\Controllers;

use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Http\Requests\PaymentBatchRequest;
use App\Models\Invoice;
use App\Models\PaymentBatch;
use App\Models\SepaSetting;
use App\Services\Pain008Generator;
use App\Services\PaymentBatchManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PaymentBatchController extends Controller
{
    public function __construct(
        private readonly PaymentBatchManager $manager,
        private readonly Pain008Generator $generator,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PaymentBatch::class);

        return view('payment-batches.index', [
            'batches' => PaymentBatch::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PaymentBatch::class);

        return view('payment-batches.create', [
            'settingsReady' => SepaSetting::query()->exists(),
            'invoices' => Invoice::query()
                ->where('status', InvoiceStatus::Approved)
                ->whereIn('payment_status', [
                    InvoicePaymentStatus::Open,
                    InvoicePaymentStatus::Returned,
                ])
                ->with(['member.sepaMandates'])
                ->orderBy('due_at')
                ->get(),
        ]);
    }

    public function store(PaymentBatchRequest $request): RedirectResponse
    {
        $batch = $this->manager->create(
            $request->validated('invoice_ids'),
            $request->validated('requested_collection_date'),
            $request->user(),
        );

        return redirect()->route('payment-batches.show', $batch)
            ->with('status', 'Sammellastschrift wurde vorbereitet.');
    }

    public function show(PaymentBatch $paymentBatch): View
    {
        $this->authorize('view', $paymentBatch);

        return view('payment-batches.show', [
            'batch' => $paymentBatch->load(['items.invoice.member', 'creator']),
        ]);
    }

    public function export(
        Request $request,
        PaymentBatch $paymentBatch,
    ): Response {
        $this->authorize('export', $paymentBatch);
        $xml = $this->generator->generate($paymentBatch);
        $this->manager->markExported($paymentBatch, $xml, $request->user());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$paymentBatch->message_id.'.xml"',
        ]);
    }

    public function submit(Request $request, PaymentBatch $paymentBatch): RedirectResponse
    {
        $this->authorize('submit', $paymentBatch);
        $this->manager->markSubmitted($paymentBatch, $request->user());

        return back()->with('status', 'Sammler wurde als bei der Bank eingereicht markiert.');
    }

    public function settle(Request $request, PaymentBatch $paymentBatch): RedirectResponse
    {
        $this->authorize('settle', $paymentBatch);
        $this->manager->markSettled($paymentBatch, $request->user());

        return back()->with('status', 'Nicht zurückgegebene Lastschriften wurden als bezahlt markiert.');
    }
}
