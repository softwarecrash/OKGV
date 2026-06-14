<?php

namespace App\Http\Controllers;

use App\Http\Requests\BillingRateAssignmentRequest;
use App\Models\BillingRate;
use App\Models\BillingRateAssignment;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingRateAssignmentController extends Controller
{
    public function store(
        BillingRateAssignmentRequest $request,
        BillingRate $billingRate,
    ): RedirectResponse {
        $assignment = $billingRate->assignments()->create($request->validated());
        AuditLogger::log('billing.rate_assignment.created', $request->user(), $assignment);

        return redirect()->route('billing-periods.show', $billingRate->billingPeriod)
            ->with('status', 'Preiszuordnung wurde angelegt.');
    }

    public function destroy(
        Request $request,
        BillingRateAssignment $billingRateAssignment,
    ): RedirectResponse {
        $this->authorize('delete', $billingRateAssignment);
        $period = $billingRateAssignment->billingRate->billingPeriod;
        abort_unless($period->isMutable(), 403);
        AuditLogger::log(
            'billing.rate_assignment.deleted',
            $request->user(),
            $billingRateAssignment,
            ['billing_rate_id' => $billingRateAssignment->billing_rate_id],
        );
        $billingRateAssignment->delete();

        return redirect()->route('billing-periods.show', $period)
            ->with('status', 'Preiszuordnung wurde gelöscht.');
    }
}
