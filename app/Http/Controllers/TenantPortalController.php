<?php

namespace App\Http\Controllers;

use App\Enums\DocumentVisibility;
use App\Enums\FeatureModule;
use App\Enums\InvoiceStatus;
use App\Enums\MeterStatus;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\MeterReadingSubmission;
use App\Models\WorkHourSubmission;
use App\Services\ActionIndicatorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantPortalController extends Controller
{
    public function __construct(
        private readonly ActionIndicatorService $actionIndicatorService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasTenantAccess(), 403);
        $actionIndicators = $this->actionIndicatorService->forUser($request->user());

        $member = $request->user()->member()
            ->with([
                'parcelTenancies' => function ($query): void {
                    $query->activeOn();

                    if (FeatureModule::Meters->enabled()) {
                        $query->with(['parcel.meters' => fn ($query) => $query
                            ->where('status', MeterStatus::Active)
                            ->orderBy('type')]);
                    } else {
                        $query->with('parcel');
                    }
                },
            ])
            ->first();

        if (! $member) {
            return view('tenant-portal.index', [
                'member' => null,
                'invoices' => collect(),
                'documents' => collect(),
                'submissions' => collect(),
                'workHourSubmissions' => collect(),
                'actionIndicators' => $actionIndicators,
            ]);
        }

        $invoices = FeatureModule::Billing->enabled()
            ? Invoice::query()
                ->where('status', InvoiceStatus::Approved)
                ->where(function ($query) use ($request): void {
                    $query->whereHas('recipients.member', fn ($query) => $query
                        ->where('user_id', $request->user()->id))
                        ->orWhereHas('member', fn ($query) => $query
                            ->where('user_id', $request->user()->id));
                })
                ->latest('issued_at')
                ->limit(5)
                ->get()
            : collect();

        $parcelIds = $member->parcelTenancies->pluck('parcel_id');
        $documents = FeatureModule::Documents->enabled()
            ? Document::query()
                ->where('visibility', DocumentVisibility::Tenant)
                ->whereNotNull('published_at')
                ->where(function ($query) use ($member, $parcelIds): void {
                    $query->where('member_id', $member->id)
                        ->orWhereIn('parcel_id', $parcelIds);
                })
                ->latest('published_at')
                ->limit(5)
                ->get()
            : collect();

        $submissions = FeatureModule::Meters->enabled()
            ? MeterReadingSubmission::query()
                ->where('submitted_by', $request->user()->id)
                ->with('meter.parcel')
                ->latest()
                ->limit(5)
                ->get()
            : collect();
        $workHourSubmissions = FeatureModule::WorkHours->enabled()
            ? WorkHourSubmission::query()
                ->where('submitted_by', $request->user()->id)
                ->with('parcel')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        return view('tenant-portal.index', compact(
            'member',
            'invoices',
            'documents',
            'submissions',
            'workHourSubmissions',
            'actionIndicators',
        ));
    }
}
