<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillingRateAssignmentRequest;
use App\Models\BillingRate;
use App\Models\BillingRateAssignment;
use App\Services\AuditLogger;
use App\Services\BillingPeriodManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingRateAssignmentController extends Controller
{
    public function __construct(
        private readonly BillingPeriodManager $periodManager,
    ) {}

    public function store(
        BillingRateAssignmentRequest $request,
        BillingRate $billingRate,
    ): RedirectResponse {
        $period = $billingRate->billingPeriod;
        $assignment = $this->periodManager->changeCalculationInputs(
            $period,
            $request->user(),
            'billing_rate_assignment_created',
            function () use ($billingRate, $request): BillingRateAssignment {
                $assignment = $billingRate->assignments()->create($request->validated());
                AuditLogger::log('billing.rate_assignment.created', $request->user(), $assignment);

                return $assignment;
            },
        );

        return redirect()->route('billing-periods.show', $period)
            ->with('status', 'Preiszuordnung wurde angelegt.');
    }

    public function destroy(
        Request $request,
        BillingRateAssignment $billingRateAssignment,
    ): RedirectResponse {
        $this->authorize('delete', $billingRateAssignment);
        $period = $billingRateAssignment->billingRate->billingPeriod;
        $this->periodManager->changeCalculationInputs(
            $period,
            $request->user(),
            'billing_rate_assignment_deleted',
            function () use ($billingRateAssignment, $request): void {
                AuditLogger::log(
                    'billing.rate_assignment.deleted',
                    $request->user(),
                    $billingRateAssignment,
                    ['billing_rate_id' => $billingRateAssignment->billing_rate_id],
                );
                $billingRateAssignment->delete();
            },
        );

        return redirect()->route('billing-periods.show', $period)
            ->with('status', 'Preiszuordnung wurde gelöscht.');
    }
}
