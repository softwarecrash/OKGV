<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkHourRequest;
use App\Models\BillingPeriod;
use App\Models\Member;
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
                ->with(['member', 'billingPeriod'])
                ->when($periodId, fn ($query) => $query
                    ->where('billing_period_id', $periodId))
                ->orderByDesc(
                    BillingPeriod::query()
                        ->select('ends_at')
                        ->whereColumn('billing_periods.id', 'work_hours.billing_period_id'),
                )
                ->orderBy(
                    Member::query()
                        ->select('last_name')
                        ->whereColumn('members.id', 'work_hours.member_id'),
                )
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

        $assignedMemberIds = $billingPeriod->workHours()->pluck('member_id');

        return view('work-hours.create', [
            'billingPeriod' => $billingPeriod,
            'workHour' => new WorkHour,
            'members' => Member::query()
                ->whereNull('archived_at')
                ->whereNotIn('id', $assignedMemberIds)
                ->orderBy('last_name')
                ->orderBy('first_name')
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
            'workHour' => $workHour->load('member'),
            'members' => collect([$workHour->member]),
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
}
