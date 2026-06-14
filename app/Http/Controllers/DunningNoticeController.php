<?php

namespace App\Http\Controllers;

use App\Enums\DunningNoticeStatus;
use App\Http\Requests\CancelDunningNoticeRequest;
use App\Http\Requests\DunningNoticeRequest;
use App\Models\DunningNotice;
use App\Models\Invoice;
use App\Services\ActionIndicatorService;
use App\Services\DunningNoticeManager;
use App\Services\DunningNoticePdfGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DunningNoticeController extends Controller
{
    public function __construct(
        private readonly DunningNoticeManager $manager,
        private readonly DunningNoticePdfGenerator $pdfGenerator,
        private readonly ActionIndicatorService $actionIndicators,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DunningNotice::class);

        $notices = DunningNotice::query()
            ->with(['invoice.member', 'creator'])
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->latest('issued_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $eligibleInvoices = $this->actionIndicators
            ->forUser($request->user())['dunning_notices'];

        return view('dunning-notices.index', [
            'notices' => $notices,
            'eligibleInvoices' => $eligibleInvoices,
            'statuses' => DunningNoticeStatus::cases(),
        ]);
    }

    public function create(Invoice $invoice): View
    {
        $this->authorize('create', DunningNotice::class);
        $invoice->load(['recipients', 'activeDunningNotices']);
        abort_unless($invoice->canReceivePaymentReminder(), 422);

        $nextLevel = $this->manager->nextLevel($invoice);
        abort_if($nextLevel > 3, 422);
        $latestActiveNotice = $invoice->activeDunningNotices->first();
        abort_if($latestActiveNotice && ! $latestActiveNotice->due_at->isPast(), 422);
        $previousFees = (float) $invoice->activeDunningNotices()->sum('fee_amount');

        return view('dunning-notices.create', compact('invoice', 'nextLevel', 'previousFees'));
    }

    public function store(
        DunningNoticeRequest $request,
        Invoice $invoice,
    ): RedirectResponse {
        $notice = $this->manager->create($invoice, $request->validated(), $request->user());

        return redirect()->route('dunning-notices.show', $notice)
            ->with('status', "Mahnstufe {$notice->level} wurde ausgestellt.");
    }

    public function show(DunningNotice $dunningNotice): View
    {
        $this->authorize('view', $dunningNotice);
        $dunningNotice->load(['invoice', 'creator', 'canceller']);

        return view('dunning-notices.show', ['notice' => $dunningNotice]);
    }

    public function pdf(DunningNotice $dunningNotice): Response
    {
        $this->authorize('view', $dunningNotice);

        return response($this->pdfGenerator->render($dunningNotice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"Mahnung-{$dunningNotice->notice_number}.pdf\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function cancel(
        CancelDunningNoticeRequest $request,
        DunningNotice $dunningNotice,
    ): RedirectResponse {
        $this->manager->cancel(
            $dunningNotice,
            $request->validated('cancellation_reason'),
            $request->user(),
        );

        return redirect()->route('dunning-notices.show', $dunningNotice)
            ->with('status', 'Mahnung wurde storniert und bleibt historisch erhalten.');
    }
}
