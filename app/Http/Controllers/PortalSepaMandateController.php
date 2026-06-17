<?php

namespace App\Http\Controllers;

use App\Enums\NumberSequenceType;
use App\Enums\SepaMandateStatus;
use App\Enums\SepaMandateType;
use App\Http\Requests\PortalSepaMandateRequest;
use App\Http\Requests\PortalSepaMandateRevocationRequest;
use App\Models\SepaMandate;
use App\Services\AuditLogger;
use App\Services\NumberSequenceManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortalSepaMandateController extends Controller
{
    public function __construct(
        private readonly NumberSequenceManager $numberSequenceManager,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasTenantAccess(), 403);

        $member = $request->user()->member()->firstOrFail();

        return view('tenant-portal.sepa-mandates.index', [
            'member' => $member,
            'mandates' => $member->sepaMandates()
                ->latest('valid_from')
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->hasTenantAccess(), 403);

        return view('tenant-portal.sepa-mandates.create');
    }

    public function store(PortalSepaMandateRequest $request): RedirectResponse
    {
        $mandate = DB::transaction(function () use ($request): SepaMandate {
            $member = $request->user()->member()->lockForUpdate()->firstOrFail();
            $data = $request->validated();
            $today = today();

            $mandate = SepaMandate::create([
                'member_id' => $member->id,
                'mandate_reference' => $this->numberSequenceManager->next(
                    NumberSequenceType::SepaMandate,
                    $today,
                ),
                'iban' => $data['iban'],
                'iban_last_four' => substr($data['iban'], -4),
                'bic' => $data['bic'] ?? null,
                'account_holder' => $data['account_holder'],
                'signed_at' => $today,
                'valid_from' => $today,
                'valid_until' => null,
                'mandate_type' => SepaMandateType::Recurring,
                'status' => SepaMandateStatus::Active,
                'created_by' => $request->user()->id,
            ]);

            AuditLogger::log('sepa.mandate.self_created', $request->user(), $mandate);

            return $mandate;
        });

        return redirect()
            ->route('tenant-portal.sepa-mandates.index')
            ->with('status', "SEPA-Mandat {$mandate->mandate_reference} wurde hinterlegt.");
    }

    public function revoke(
        PortalSepaMandateRevocationRequest $request,
        SepaMandate $sepaMandate,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $sepaMandate): void {
            $mandate = SepaMandate::query()->lockForUpdate()->findOrFail($sepaMandate->id);

            $mandate->update([
                'status' => SepaMandateStatus::Revoked,
                'valid_until' => today(),
                'revoked_at' => now(),
                'revoked_by' => $request->user()->id,
                'revocation_note' => $request->validated('revocation_note'),
            ]);

            AuditLogger::log('sepa.mandate.self_revoked', $request->user(), $mandate, [
                'revocation_note' => $request->validated('revocation_note'),
            ]);
        });

        return redirect()
            ->route('tenant-portal.sepa-mandates.index')
            ->with('status', 'SEPA-Mandat wurde widerrufen.');
    }
}
