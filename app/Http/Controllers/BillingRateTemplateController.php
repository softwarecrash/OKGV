<?php

namespace App\Http\Controllers;

use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\BillingSettlementType;
use App\Http\Requests\BillingRateTemplateRequest;
use App\Models\BillingRateTemplate;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BillingRateTemplateController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', BillingRateTemplate::class);

        return view('billing-rate-templates.index', [
            'templates' => BillingRateTemplate::query()
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', BillingRateTemplate::class);

        return view('billing-rate-templates.create', [
            'template' => new BillingRateTemplate([
                'is_active' => true,
                'settlement_type' => BillingSettlementType::Arrears,
                'prorate' => false,
            ]),
            'types' => BillingRateType::availableCases(),
            'scopes' => BillingRateScope::cases(),
            'settlementTypes' => BillingSettlementType::cases(),
        ]);
    }

    public function store(BillingRateTemplateRequest $request): RedirectResponse
    {
        $template = BillingRateTemplate::create($request->validated());
        AuditLogger::log('billing.rate_template.created', $request->user(), $template);

        return redirect()->route('billing-rate-templates.index')
            ->with('status', 'Preisvorlage wurde angelegt.');
    }

    public function edit(BillingRateTemplate $billingRateTemplate): View
    {
        $this->authorize('update', $billingRateTemplate);

        return view('billing-rate-templates.edit', [
            'template' => $billingRateTemplate,
            'types' => BillingRateType::availableCases(),
            'scopes' => BillingRateScope::cases(),
            'settlementTypes' => BillingSettlementType::cases(),
        ]);
    }

    public function update(
        BillingRateTemplateRequest $request,
        BillingRateTemplate $billingRateTemplate,
    ): RedirectResponse {
        $billingRateTemplate->update($request->validated());
        AuditLogger::log(
            'billing.rate_template.updated',
            $request->user(),
            $billingRateTemplate,
        );

        return redirect()->route('billing-rate-templates.index')
            ->with('status', 'Preisvorlage wurde aktualisiert.');
    }
}
