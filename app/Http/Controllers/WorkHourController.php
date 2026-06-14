<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkHourRequest;
use App\Models\ApplicationSetting;
use App\Models\BillingPeriod;
use App\Models\Parcel;
use App\Models\WorkHour;
use App\Services\WorkHourManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkHourController extends Controller
{
    public function __construct(
        private readonly WorkHourManager $workHourManager,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', WorkHour::class);
        $periodId = $request->integer('billing_period_id') ?: null;

        return view('work-hours.index', [
            'workHours' => WorkHour::query()
                ->with(['parcel', 'billingPeriod'])
                ->when($periodId, fn ($query) => $query
                    ->where('billing_period_id', $periodId))
                ->orderByDesc(
                    BillingPeriod::query()
                        ->select('ends_at')
                        ->whereColumn('billing_periods.id', 'work_hours.billing_period_id'),
                )
                ->orderBy('parcel_id')
                ->paginate(25)
                ->withQueryString(),
            'periods' => BillingPeriod::query()->latest('starts_at')->get(),
            'selectedPeriodId' => $periodId,
        ]);
    }

    public function create(Request $request, BillingPeriod $billingPeriod): View
    {
        $this->authorize('create', WorkHour::class);
        abort_unless($billingPeriod->isEditable(), 403);

        $assignedParcelIds = $billingPeriod->workHours()->pluck('parcel_id');
        $parcels = Parcel::query()
            ->whereNotIn('id', $assignedParcelIds)
            ->whereHas('tenancies', fn ($query) => $query->activeOn($billingPeriod->ends_at))
            ->orderBy('parcel_number')
            ->get();
        $selectedParcelId = $request->integer('parcel_id');
        $settings = ApplicationSetting::current();
        $workHour = new WorkHour([
            'hours_required' => $settings->default_work_hours_required,
            'manual_hours_done' => '0.00',
            'penalty_rate' => $settings->default_work_hour_penalty_rate,
        ]);

        if ($selectedParcelId && $parcels->contains('id', $selectedParcelId)) {
            $workHour->parcel_id = $selectedParcelId;
        }

        return view('work-hours.create', [
            'billingPeriod' => $billingPeriod,
            'workHour' => $workHour,
            'parcels' => $parcels,
            'returnTo' => $workHour->parcel_id
                && $request->string('return_to')->toString() === 'parcel'
                ? 'parcel'
                : null,
        ]);
    }

    public function store(
        WorkHourRequest $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $workHour = $this->workHourManager->save(
            $billingPeriod,
            $request->validated(),
            $request->user(),
        );

        $redirect = $request->validated('return_to') === 'parcel'
            ? redirect()->route('parcels.show', $workHour->parcel_id)
            : redirect()->route('billing-periods.show', $billingPeriod);

        return $redirect
            ->with('status', 'Arbeitsstundenkonto wurde angelegt.');
    }

    public function edit(WorkHour $workHour): View
    {
        $this->authorize('update', $workHour);
        abort_unless($workHour->billingPeriod->isEditable(), 403);

        return view('work-hours.edit', [
            'billingPeriod' => $workHour->billingPeriod,
            'workHour' => $workHour->load('parcel'),
            'parcels' => collect([$workHour->parcel]),
            'returnTo' => null,
        ]);
    }

    public function update(
        WorkHourRequest $request,
        WorkHour $workHour,
    ): RedirectResponse {
        $period = $workHour->billingPeriod;
        $this->workHourManager->save(
            $period,
            $request->validated(),
            $request->user(),
            $workHour,
        );

        $redirect = $request->validated('return_to') === 'parcel'
            ? redirect()->route('parcels.show', $workHour->parcel_id)
            : redirect()->route('billing-periods.show', $period);

        return $redirect
            ->with('status', 'Arbeitsstundenkonto wurde aktualisiert.');
    }

    public function initialize(
        Request $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $this->authorize('create', WorkHour::class);
        $count = $this->workHourManager->initializePeriod(
            $billingPeriod,
            $request->user(),
        );

        return back()->with(
            'status',
            $count > 0
                ? "{$count} Parzellenkonten wurden aus den Vereinsvorgaben angelegt."
                : 'Für alle aktuell vergebenen Parzellen bestehen bereits Arbeitsstundenkonten.',
        );
    }
}
