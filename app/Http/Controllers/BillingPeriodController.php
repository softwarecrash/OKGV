<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillingPeriodRequest;
use App\Models\BillingPeriod;
use App\Models\Member;
use App\Models\Parcel;
use App\Services\AuditLogger;
use App\Services\BillingCalculator;
use App\Services\BillingPeriodManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingPeriodController extends Controller
{
    public function __construct(
        private readonly BillingPeriodManager $periodManager,
        private readonly BillingCalculator $billingCalculator,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', BillingPeriod::class);

        return view('billing-periods.index', [
            'periods' => BillingPeriod::query()
                ->withCount('invoices')
                ->latest('starts_at')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', BillingPeriod::class);

        return view('billing-periods.create', [
            'billingPeriod' => new BillingPeriod,
        ]);
    }

    public function store(BillingPeriodRequest $request): RedirectResponse
    {
        $period = $this->periodManager->save($request->validated());
        AuditLogger::log('billing.period.created', $request->user(), $period);

        return redirect()->route('billing-periods.show', $period)
            ->with('status', 'Abrechnungsperiode wurde angelegt.');
    }

    public function show(BillingPeriod $billingPeriod): View
    {
        $this->authorize('view', $billingPeriod);
        $billingPeriod->load([
            'rates.assignments.member',
            'rates.assignments.parcel',
            'invoices.member',
        ]);

        return view('billing-periods.show', [
            'billingPeriod' => $billingPeriod,
            'members' => Member::query()->orderBy('last_name')->orderBy('first_name')->get(),
            'parcels' => Parcel::query()->orderBy('parcel_number')->get(),
        ]);
    }

    public function edit(BillingPeriod $billingPeriod): View
    {
        $this->authorize('update', $billingPeriod);
        abort_unless($billingPeriod->isMutable(), 403);

        return view('billing-periods.edit', compact('billingPeriod'));
    }

    public function update(
        BillingPeriodRequest $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $period = $this->periodManager->save($request->validated(), $billingPeriod);
        AuditLogger::log('billing.period.updated', $request->user(), $period);

        return redirect()->route('billing-periods.show', $period)
            ->with('status', 'Abrechnungsperiode wurde aktualisiert.');
    }

    public function calculate(
        Request $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $this->authorize('calculate', $billingPeriod);
        $this->billingCalculator->calculate($billingPeriod, $request->user());

        return back()->with('status', 'Abrechnungsperiode wurde berechnet.');
    }

    public function approve(
        Request $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $this->authorize('approve', $billingPeriod);
        $this->periodManager->approve($billingPeriod, $request->user());

        return back()->with('status', 'Rechnungen wurden freigegeben.');
    }

    public function archive(
        Request $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $this->authorize('archive', $billingPeriod);
        $this->periodManager->archive($billingPeriod, $request->user());

        return back()->with('status', 'Abrechnungsperiode wurde archiviert.');
    }
}
