<?php

namespace App\Http\Controllers;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\BillingSettlementType;
use App\Http\Requests\BillingRateRequest;
use App\Models\BillingPeriod;
use App\Models\BillingRate;
use App\Models\BillingRateTemplate;
use App\Services\AuditLogger;
use App\Services\BillingPeriodManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingRateController extends Controller
{
    public function __construct(
        private readonly BillingPeriodManager $periodManager,
    ) {}

    public function create(BillingPeriod $billingPeriod): View
    {
        $this->authorize('create', BillingRate::class);
        abort_unless($billingPeriod->isEditable(), 403);

        $selectedTemplate = BillingRateTemplate::query()
            ->whereKey(request()->integer('template'))
            ->where('is_active', true)
            ->first();

        $billingRate = new BillingRate([
            'is_active' => true,
            'settlement_type' => BillingSettlementType::Arrears,
            'service_starts_at' => $billingPeriod->starts_at,
            'service_ends_at' => $billingPeriod->ends_at,
            'prorate' => false,
        ]);

        if ($selectedTemplate) {
            $serviceStartsAt = $selectedTemplate->settlement_type === BillingSettlementType::Advance
                ? $billingPeriod->starts_at->addYear()
                : $billingPeriod->starts_at;
            $serviceEndsAt = $selectedTemplate->settlement_type === BillingSettlementType::Advance
                ? $billingPeriod->ends_at->addYear()
                : $billingPeriod->ends_at;

            $billingRate->forceFill([
                'billing_rate_template_id' => $selectedTemplate->id,
                'code' => $selectedTemplate->code,
                'name' => $selectedTemplate->name,
                'description' => $selectedTemplate->description,
                'calculation_type' => $selectedTemplate->calculation_type,
                'scope' => $selectedTemplate->scope,
                'settlement_type' => $selectedTemplate->settlement_type,
                'service_starts_at' => $serviceStartsAt,
                'service_ends_at' => $serviceEndsAt,
                'amount' => $selectedTemplate->default_amount,
                'prorate' => $selectedTemplate->prorate,
            ]);
        }

        return view('billing-rates.create', [
            'billingPeriod' => $billingPeriod,
            'billingRate' => $billingRate,
            'selectedTemplate' => $selectedTemplate,
            'templates' => BillingRateTemplate::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'types' => BillingRateType::cases(),
            'scopes' => BillingRateScope::cases(),
            'settlementTypes' => BillingSettlementType::cases(),
        ]);
    }

    public function store(
        BillingRateRequest $request,
        BillingPeriod $billingPeriod,
    ): RedirectResponse {
        $rate = $this->periodManager->changeCalculationInputs(
            $billingPeriod,
            $request->user(),
            'billing_rate_created',
            function (BillingPeriod $period) use ($request): BillingRate {
                $rate = $period->rates()->create($request->validated());
                AuditLogger::log('billing.rate.created', $request->user(), $rate);

                return $rate;
            },
        );

        return redirect()->route('billing-periods.show', $billingPeriod)
            ->with('status', 'Preis wurde angelegt.');
    }

    public function edit(
        BillingPeriod $billingPeriod,
        BillingRate $billingRate,
    ): View {
        $this->ensureBelongsToPeriod($billingPeriod, $billingRate);
        $this->authorize('update', $billingRate);
        abort_unless($billingPeriod->isEditable(), 403);

        return view('billing-rates.edit', [
            'billingPeriod' => $billingPeriod,
            'billingRate' => $billingRate,
            'selectedTemplate' => null,
            'types' => BillingRateType::cases(),
            'scopes' => BillingRateScope::cases(),
            'settlementTypes' => BillingSettlementType::cases(),
        ]);
    }

    public function update(
        BillingRateRequest $request,
        BillingPeriod $billingPeriod,
        BillingRate $billingRate,
    ): RedirectResponse {
        $this->ensureBelongsToPeriod($billingPeriod, $billingRate);
        $this->periodManager->changeCalculationInputs(
            $billingPeriod,
            $request->user(),
            'billing_rate_updated',
            function () use ($billingRate, $request): void {
                $billingRate->update(
                    $request->safe()->except('billing_rate_template_id'),
                );
                AuditLogger::log('billing.rate.updated', $request->user(), $billingRate);
            },
        );

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
        abort_if($billingRate->assignments()->exists(), 422, 'Preis besitzt Zuordnungen.');
        $this->periodManager->changeCalculationInputs(
            $billingPeriod,
            $request->user(),
            'billing_rate_deleted',
            function () use ($billingRate, $billingPeriod, $request): void {
                AuditLogger::log('billing.rate.deleted', $request->user(), $billingRate, [
                    'code' => $billingRate->code,
                    'billing_period_id' => $billingPeriod->id,
                ]);
                $billingRate->delete();
            },
        );

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
