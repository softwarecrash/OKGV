<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkHourRequest;
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

    public function create(BillingPeriod $billingPeriod): View
    {
        $this->authorize('create', WorkHour::class);
        abort_unless($billingPeriod->isEditable(), 403);

        $assignedParcelIds = $billingPeriod->workHours()->pluck('parcel_id');

        return view('work-hours.create', [
            'billingPeriod' => $billingPeriod,
            'workHour' => new WorkHour,
            'parcels' => Parcel::query()
                ->whereNotIn('id', $assignedParcelIds)
                ->whereHas('tenancies', fn ($query) => $query->activeOn($billingPeriod->ends_at))
                ->orderBy('parcel_number')
                ->get(),
        ]);
    }

    public function store(
        WorkHourRequest $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $this->workHourManager->save(
            $billingPeriod,
            $request->validated(),
            $request->user(),
        );

        return redirect()->route('billing-periods.show', $billingPeriod)
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

        return redirect()->route('billing-periods.show', $period)
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
