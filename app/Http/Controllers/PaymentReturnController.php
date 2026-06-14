<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentReturnRequest;
use App\Models\PaymentBatchItem;
use App\Services\PaymentBatchManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentReturnController extends Controller
{
    public function __construct(
        private readonly PaymentBatchManager $manager,
    ) {}

    public function create(PaymentBatchItem $paymentBatchItem): View
    {
        $this->authorize('return', $paymentBatchItem);

        return view('payment-returns.create', ['item' => $paymentBatchItem->load('invoice.member')]);
    }

    public function store(
        PaymentReturnRequest $request,
        PaymentBatchItem $paymentBatchItem,
    ): RedirectResponse {
        $this->manager->markReturned(
            $paymentBatchItem,
            $request->validated('return_reason_code'),
            $request->validated('return_reason_text'),
            $request->validated('returned_at'),
            $request->user(),
        );

        return redirect()->route('payment-batches.show', $paymentBatchItem->payment_batch_id)
            ->with('status', 'Rücklastschrift wurde erfasst und die Rechnung wieder geöffnet.');
    }
}
