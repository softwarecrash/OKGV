<?php

namespace App\Http\Controllers;

use App\Enums\WorkEventParticipantStatus;
use App\Enums\WorkEventStatus;
use App\Http\Requests\WorkEventRequest;
use App\Models\BillingPeriod;
use App\Models\Member;
use App\Models\WorkEvent;
use App\Services\WorkEventManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkEventController extends Controller
{
    public function __construct(
        private readonly WorkEventManager $manager,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', WorkEvent::class);
        $periodId = $request->integer('billing_period_id') ?: null;
        $status = WorkEventStatus::tryFrom($request->string('status')->toString());

        return view('work-events.index', [
            'workEvents' => WorkEvent::query()
                ->with('billingPeriod')
                ->withCount('participants')
                ->when($periodId, fn ($query) => $query->where('billing_period_id', $periodId))
                ->when($status, fn ($query) => $query->where('status', $status))
                ->orderByDesc('starts_at')
                ->paginate(25)
                ->withQueryString(),
            'periods' => BillingPeriod::query()->latest('starts_at')->get(),
            'statuses' => WorkEventStatus::cases(),
            'selectedPeriodId' => $periodId,
            'selectedStatus' => $status,
        ]);
    }

    public function create(BillingPeriod $billingPeriod): View
    {
        $this->authorize('create', WorkEvent::class);
        abort_unless($billingPeriod->isEditable(), 403);

        return view('work-events.create', [
            'billingPeriod' => $billingPeriod,
            'workEvent' => new WorkEvent([
                'status' => WorkEventStatus::Planned,
            ]),
            'statuses' => WorkEventStatus::cases(),
        ]);
    }

    public function store(
        WorkEventRequest $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $event = $this->manager->save(
            $billingPeriod,
            $request->validated(),
            $request->user(),
        );

        return redirect()->route('work-events.show', $event)
            ->with('status', 'Arbeitseinsatz wurde angelegt.');
    }

    public function show(WorkEvent $workEvent): View
    {
        $this->authorize('view', $workEvent);
        $workEvent->load(['billingPeriod', 'participants.member', 'participants.confirmer']);
        $assignedMemberIds = $workEvent->participants->pluck('member_id');

        return view('work-events.show', [
            'workEvent' => $workEvent,
            'members' => Member::query()
                ->whereNull('archived_at')
                ->whereNotIn('id', $assignedMemberIds)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
            'participantStatuses' => WorkEventParticipantStatus::cases(),
        ]);
    }

    public function edit(WorkEvent $workEvent): View
    {
        $this->authorize('update', $workEvent);

        return view('work-events.edit', [
            'billingPeriod' => $workEvent->billingPeriod,
            'workEvent' => $workEvent,
            'statuses' => WorkEventStatus::cases(),
        ]);
    }

    public function update(
        WorkEventRequest $request,
        WorkEvent $workEvent,
    ): RedirectResponse {
        $event = $this->manager->save(
            $workEvent->billingPeriod,
            $request->validated(),
            $request->user(),
            $workEvent,
        );

        return redirect()->route('work-events.show', $event)
            ->with('status', 'Arbeitseinsatz wurde aktualisiert.');
    }
}
