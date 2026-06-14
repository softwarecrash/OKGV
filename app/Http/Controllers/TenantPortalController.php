<?php

namespace App\Http\Controllers;

use App\Enums\DocumentVisibility;
use App\Enums\InvoiceStatus;
use App\Enums\MeterStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\MeterReadingSubmission;
use App\Models\WorkHourSubmission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantPortalController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === UserRole::Tenant, 403);

        $member = $request->user()->member()
            ->with([
                'parcelTenancies' => fn ($query) => $query
                    ->activeOn()
                    ->with(['parcel.meters' => fn ($query) => $query
                        ->where('status', MeterStatus::Active)
                        ->orderBy('type')]),
            ])
            ->first();

        if (! $member) {
            return view('tenant-portal.index', [
                'member' => null,
                'invoices' => collect(),
                'documents' => collect(),
                'submissions' => collect(),
                'workHourSubmissions' => collect(),
            ]);
        }

        $invoices = Invoice::query()
            ->where('status', InvoiceStatus::Approved)
            ->where(function ($query) use ($request): void {
                $query->whereHas('recipients.member', fn ($query) => $query
                    ->where('user_id', $request->user()->id))
                    ->orWhereHas('member', fn ($query) => $query
                        ->where('user_id', $request->user()->id));
            })
            ->latest('issued_at')
            ->limit(5)
            ->get();

        $parcelIds = $member->parcelTenancies->pluck('parcel_id');
        $documents = Document::query()
            ->where('visibility', DocumentVisibility::Tenant)
            ->whereNotNull('published_at')
            ->where(function ($query) use ($member, $parcelIds): void {
                $query->where('member_id', $member->id)
                    ->orWhereIn('parcel_id', $parcelIds);
            })
            ->latest('published_at')
            ->limit(5)
            ->get();

        $submissions = MeterReadingSubmission::query()
            ->where('submitted_by', $request->user()->id)
            ->with('meter.parcel')
            ->latest()
            ->limit(5)
            ->get();
        $workHourSubmissions = WorkHourSubmission::query()
            ->where('submitted_by', $request->user()->id)
            ->with('parcel')
            ->latest()
            ->limit(5)
            ->get();

        return view('tenant-portal.index', compact(
            'member',
            'invoices',
            'documents',
            'submissions',
            'workHourSubmissions',
        ));
    }
}
