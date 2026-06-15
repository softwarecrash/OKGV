<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrivacySettingRequest;
use App\Models\Member;
use App\Models\PrivacyErasureRequest;
use App\Services\AuditLogger;
use App\Services\PrivacyDataExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivacyController extends Controller
{
    public function index(Request $request): View
    {
        $member = $request->user()->member;
        $privacySetting = $member?->privacySetting()->firstOrNew([
            'member_id' => $member->id,
        ]);

        $coTenants = $member === null
            ? collect()
            : $this->coTenants($member);

        $erasureRequests = $request->user()->canManagePrivacy()
            ? PrivacyErasureRequest::query()
                ->with(['member', 'requester', 'reviewer'])
                ->latest('requested_at')
                ->paginate(20)
            : $member?->privacyErasureRequests()
                ->with('reviewer')
                ->latest('requested_at')
                ->paginate(20);

        return view('privacy.index', [
            'member' => $member,
            'privacySetting' => $privacySetting,
            'coTenants' => $coTenants,
            'erasureRequests' => $erasureRequests,
            'retentionYears' => (int) config('privacy.retention_years'),
        ]);
    }

    public function update(PrivacySettingRequest $request): RedirectResponse
    {
        $member = $request->user()->member;
        $settings = $member->privacySetting()->updateOrCreate(
            ['member_id' => $member->id],
            [
                ...$request->validated(),
                'consented_at' => collect($request->validated())->contains(true)
                    ? now()
                    : null,
            ],
        );

        AuditLogger::log('privacy.sharing_updated', $request->user(), $member, [
            'shared_fields' => collect($request->validated())
                ->filter()
                ->keys()
                ->values()
                ->all(),
        ]);

        return redirect()->route('privacy.index')
            ->with('status', $settings->sharesAnything()
                ? 'Deine freiwilligen Datenfreigaben wurden gespeichert.'
                : 'Alle Datenfreigaben wurden widerrufen.');
    }

    public function export(
        Request $request,
        Member $member,
        PrivacyDataExportService $exportService,
    ): StreamedResponse {
        abort_unless(
            $request->user()->canManagePrivacy() || $member->user_id === $request->user()->id,
            Response::HTTP_FORBIDDEN,
        );

        $payload = $exportService->build($member);
        AuditLogger::log('privacy.data_exported', $request->user(), $member);
        $filename = "okgv-auskunft-mitglied-{$member->id}-".today()->format('Y-m-d').'.json';

        return response()->streamDownload(
            static function () use ($payload): void {
                echo json_encode(
                    $payload,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                );
            },
            $filename,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    private function coTenants(Member $member)
    {
        $parcelIds = $member->parcelTenancies()
            ->activeOn()
            ->pluck('parcel_id');

        if ($parcelIds->isEmpty()) {
            return collect();
        }

        return Member::query()
            ->whereKeyNot($member->id)
            ->whereHas('parcelTenancies', fn ($query) => $query
                ->activeOn()
                ->whereIn('parcel_id', $parcelIds))
            ->whereHas('privacySetting', fn ($query) => $query
                ->where('share_name', true)
                ->orWhere('share_email', true)
                ->orWhere('share_phone', true)
                ->orWhere('share_mobile', true)
                ->orWhere('share_address', true))
            ->with('privacySetting')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}
