<?php

namespace App\Http\Controllers;

use App\Enums\FeatureModule;
use App\Http\Requests\BillingPeriodRequest;
use App\Models\ApplicationSetting;
use App\Models\BillingPeriod;
use App\Models\Member;
use App\Models\Parcel;
use App\Services\AuditLogger;
use App\Services\BillingCalculator;
use App\Services\BillingPeriodManager;
use App\Services\WorkHourManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingPeriodController extends Controller
{
    public function __construct(
        private readonly BillingPeriodManager $periodManager,
        private readonly BillingCalculator $billingCalculator,
        private readonly WorkHourManager $workHourManager,
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
            'defaultPaymentTermDays' => ApplicationSetting::current()->default_payment_term_days,
        ]);
    }

    public function store(BillingPeriodRequest $request): RedirectResponse
    {
        [$period, $createdAccounts] = DB::transaction(function () use ($request): array {
            $period = $this->periodManager->save($request->validated());
            $createdAccounts = FeatureModule::WorkHours->enabled()
                ? $this->workHourManager->initializePeriod($period, $request->user())
                : 0;
            AuditLogger::log('billing.period.created', $request->user(), $period);

            return [$period, $createdAccounts];
        });

        return redirect()->route('billing-periods.show', $period)
            ->with(
                'status',
                "Abrechnungsperiode wurde angelegt. {$createdAccounts} Arbeitsstundenkonten wurden automatisch eingerichtet.",
            );
    }

    public function show(BillingPeriod $billingPeriod): View
    {
        $this->authorize('view', $billingPeriod);
        $relations = [
            'rates.assignments.member',
            'rates.assignments.parcel',
            'invoices.member',
        ];
        if (FeatureModule::WorkHours->enabled()) {
            $relations[] = 'workHours.parcel';
        }
        $billingPeriod->load($relations);

        return view('billing-periods.show', [
            'billingPeriod' => $billingPeriod,
            'members' => Member::query()->orderBy('last_name')->orderBy('first_name')->get(),
            'parcels' => Parcel::query()->orderBy('parcel_number')->get(),
        ]);
    }

    public function edit(BillingPeriod $billingPeriod): View
    {
        $this->authorize('update', $billingPeriod);
        abort_unless($billingPeriod->isEditable(), 403);

        return view('billing-periods.edit', [
            'billingPeriod' => $billingPeriod,
            'defaultPaymentTermDays' => ApplicationSetting::current()->default_payment_term_days,
        ]);
    }

    public function update(
        BillingPeriodRequest $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        [$period, $createdAccounts] = DB::transaction(
            function () use ($request, $billingPeriod): array {
                $period = $this->periodManager->save(
                    $request->validated(),
                    $billingPeriod,
                    $request->user(),
                );
                $createdAccounts = FeatureModule::WorkHours->enabled()
                    ? $this->workHourManager->initializePeriod($period, $request->user())
                    : 0;
                AuditLogger::log('billing.period.updated', $request->user(), $period);

                return [$period, $createdAccounts];
            },
        );

        return redirect()->route('billing-periods.show', $period)
            ->with(
                'status',
                "Abrechnungsperiode wurde aktualisiert. {$createdAccounts} fehlende Arbeitsstundenkonten wurden automatisch ergänzt.",
            );
    }

    public function calculate(
        Request $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $this->authorize('calculate', $billingPeriod);
        $this->billingCalculator->calculate($billingPeriod, $request->user());

        return back()->with('status', 'Zwischenstand wurde berechnet. Die Rechnungsentwürfe können geprüft und bei Bedarf neu berechnet werden.');
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
