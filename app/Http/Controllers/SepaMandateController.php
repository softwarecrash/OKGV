<?php

namespace App\Http\Controllers;

use App\Enums\NumberSequenceType;
use App\Enums\SepaMandateStatus;
use App\Enums\SepaMandateType;
use App\Http\Requests\SepaMandateRequest;
use App\Models\Member;
use App\Models\SepaMandate;
use App\Services\AuditLogger;
use App\Services\NumberSequenceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SepaMandateController extends Controller
{
    public function __construct(
        private readonly NumberSequenceManager $numberSequenceManager,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', SepaMandate::class);

        return view('sepa-mandates.index', [
            'mandates' => SepaMandate::query()
                ->with('member')
                ->latest('valid_from')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SepaMandate::class);

        return view('sepa-mandates.create', $this->formData(new SepaMandate([
            'status' => SepaMandateStatus::Active,
            'mandate_type' => SepaMandateType::Recurring,
            'signed_at' => now(),
            'valid_from' => now(),
        ])));
    }

    public function store(SepaMandateRequest $request): RedirectResponse
    {
        $mandate = DB::transaction(function () use ($request): SepaMandate {
            $data = $request->validated();
            $data['mandate_reference'] = $data['mandate_reference']
                ?: $this->numberSequenceManager->next(
                    NumberSequenceType::SepaMandate,
                    $data['signed_at'],
                );
            $data['iban_last_four'] = substr($data['iban'], -4);
            $mandate = SepaMandate::create($data);
            AuditLogger::log('sepa.mandate.created', $request->user(), $mandate);

            return $mandate;
        });

        return redirect()->route('sepa-mandates.index')
            ->with('status', 'SEPA-Mandat wurde angelegt.');
    }

    public function edit(SepaMandate $sepaMandate): View
    {
        $this->authorize('update', $sepaMandate);

        return view('sepa-mandates.edit', $this->formData($sepaMandate));
    }

    public function update(
        SepaMandateRequest $request,
        SepaMandate $sepaMandate,
    ): RedirectResponse {
        $data = $request->validated();
        $data['iban_last_four'] = substr($data['iban'], -4);
        $sepaMandate->update($data);

        AuditLogger::log('sepa.mandate.updated', $request->user(), $sepaMandate, [
            'changed_fields' => array_keys($sepaMandate->getChanges()),
        ]);

        return redirect()->route('sepa-mandates.index')
            ->with('status', 'SEPA-Mandat wurde aktualisiert.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(SepaMandate $mandate): array
    {
        return [
            'mandate' => $mandate,
            'members' => Member::query()->orderBy('last_name')->orderBy('first_name')->get(),
            'types' => SepaMandateType::cases(),
            'statuses' => SepaMandateStatus::cases(),
        ];
    }
}
