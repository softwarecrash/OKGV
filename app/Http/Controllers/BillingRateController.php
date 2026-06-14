<?php

namespace App\Http\Controllers;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Http\Requests\BillingRateRequest;
use App\Models\BillingPeriod;
use App\Models\BillingRate;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingRateController extends Controller
{
    public function create(BillingPeriod $billingPeriod): View
    {
        $this->authorize('create', BillingRate::class);
        abort_unless($billingPeriod->isMutable(), 403);

        return view('billing-rates.create', [
            'billingPeriod' => $billingPeriod,
            'billingRate' => new BillingRate(['is_active' => true]),
            'types' => BillingRateType::cases(),
            'scopes' => BillingRateScope::cases(),
        ]);
    }

    public function store(
        BillingRateRequest $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        abort_unless($billingPeriod->isMutable(), 403);
        $rate = $billingPeriod->rates()->create($request->validated());
        AuditLogger::log('billing.rate.created', $request->user(), $rate);

        return redirect()->route('billing-periods.show', $billingPeriod)
            ->with('status', 'Preis wurde angelegt.');
    }

    public function edit(
        BillingPeriod $billingPeriod,
        BillingRate $billingRate,
    ): View {
        $this->ensureBelongsToPeriod($billingPeriod, $billingRate);
        $this->authorize('update', $billingRate);
        abort_unless($billingPeriod->isMutable(), 403);

        return view('billing-rates.edit', [
            'billingPeriod' => $billingPeriod,
            'billingRate' => $billingRate,
            'types' => BillingRateType::cases(),
            'scopes' => BillingRateScope::cases(),
        ]);
    }

    public function update(
        BillingRateRequest $request,
        BillingPeriod $billingPeriod,
        BillingRate $billingRate,
    ): RedirectResponse {
        $this->ensureBelongsToPeriod($billingPeriod, $billingRate);
        abort_unless($billingPeriod->isMutable(), 403);
        $billingRate->update($request->validated());
        AuditLogger::log('billing.rate.updated', $request->user(), $billingRate);

        return redirect()->route('billing-periods.show', $billingPeriod)
            ->with('status', 'Preis wurde aktualisiert.');
    }

    public function destroy(
        Request $request,
        BillingPeriod $billingPeriod,
        BillingRate $billingRate,
    ): RedirectResponse {
        $this->ensureBelongsToPeriod($billingPeriod, $billingRate);
        $this->authorize('delete', $billingRate);
        abort_unless($billingPeriod->isMutable(), 403);
        abort_if($billingRate->assignments()->exists(), 422, 'Preis besitzt Zuordnungen.');
        AuditLogger::log('billing.rate.deleted', $request->user(), $billingRate, [
            'code' => $billingRate->code,
            'billing_period_id' => $billingPeriod->id,
        ]);
        $billingRate->delete();

        return redirect()->route('billing-periods.show', $billingPeriod)
            ->with('status', 'Preis wurde gelöscht.');
    }

    private function ensureBelongsToPeriod(
        BillingPeriod $billingPeriod,
        BillingRate $billingRate,
    ): void {
        abort_unless($billingRate->billing_period_id === $billingPeriod->id, 404);
    }
}
