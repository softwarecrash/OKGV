<?php

namespace App\Services;

use App\Enums\BillingPeriodStatus;
use App\Enums\FeatureModule;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MailCampaignStatus;
use App\Enums\MeterReadingSubmissionStatus;
use App\Enums\RegistrationRequestStatus;
use App\Enums\UserRole;
use App\Enums\WaitingListStatus;
use App\Enums\WorkEventStatus;
use App\Enums\WorkHourSubmissionStatus;
use App\Models\InventoryLoan;
use App\Models\Invoice;
use App\Models\MailCampaign;
use App\Models\MeterReadingSubmission;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\WorkEvent;
use App\Models\WorkHour;
use App\Models\WorkHourSubmission;

final class ActionIndicatorService
{
    /**
     * @return array{
     *     registrations: int,
     *     meter_readings: int,
     *     invoices: int,
     *     work_hours: int,
     *     work_events: int,
     *     work_hour_submissions: int,
     *     waiting_list: int,
     *     inventory: int,
     *     members_group: int,
     *     meters_group: int,
     *     finance_group: int,
     *     communication_group: int,
     *     dunning_notices: int,
     *     total: int
     * }
     */
    public function forUser(User $user): array
    {
        $registrations = FeatureModule::TenantPortal->enabled()
            && $user->canReviewTenantRegistrations()
            ? RegistrationRequest::query()
                ->where('status', RegistrationRequestStatus::Pending)
                ->count()
            : 0;

        $meterReadings = match (true) {
            ! FeatureModule::Meters->enabled() => 0,
            $user->canReviewMeterReadingSubmissions() => MeterReadingSubmission::query()
                ->where('status', MeterReadingSubmissionStatus::Pending)
                ->count(),
            $user->role === UserRole::Tenant => MeterReadingSubmission::query()
                ->unresolvedRejectedForUser($user->id)
                ->count(),
            default => 0,
        };

        $invoices = match (true) {
            ! FeatureModule::Billing->enabled() => 0,
            $user->canManageBilling() => $this->dunnableInvoiceCount(),
            $user->role === UserRole::Tenant => Invoice::query()
                ->where('status', InvoiceStatus::Approved)
                ->whereIn('payment_status', [
                    InvoicePaymentStatus::Open,
                    InvoicePaymentStatus::Returned,
                ])
                ->where(function ($query) use ($user): void {
                    $query->whereHas('recipients.member', fn ($query) => $query
                        ->where('user_id', $user->id))
                        ->orWhereHas('member', fn ($query) => $query
                            ->where('user_id', $user->id));
                })
                ->count(),
            default => 0,
        };
        $dunningNotices = FeatureModule::Dunning->enabled()
            && $user->canManageBilling() ? $invoices : 0;
        $workHours = FeatureModule::WorkHours->enabled()
            && $user->canManageBilling()
            ? WorkHour::query()
                ->where('hours_missing', '>', 0)
                ->whereHas('billingPeriod', fn ($query) => $query
                    ->where('status', BillingPeriodStatus::Draft))
                ->count()
            : 0;
        $workEvents = FeatureModule::WorkEvents->enabled()
            && $user->canManageWorkEvents()
            ? WorkEvent::query()
                ->where('status', WorkEventStatus::Planned)
                ->where('ends_at', '<', now())
                ->count()
            : 0;
        $workHourSubmissions = match (true) {
            ! FeatureModule::WorkHours->enabled() => 0,
            $user->canManageWorkEvents() => WorkHourSubmission::query()
                ->where('status', WorkHourSubmissionStatus::Pending)
                ->count(),
            $user->role === UserRole::Tenant => WorkHourSubmission::query()
                ->where('submitted_by', $user->id)
                ->where('status', WorkHourSubmissionStatus::Rejected)
                ->count(),
            default => 0,
        };

        $failedCampaigns = FeatureModule::Communication->enabled()
            && $user->canManageCommunication()
            ? MailCampaign::query()->where('status', MailCampaignStatus::Failed)->count()
            : 0;
        $waitingList = FeatureModule::WaitingList->enabled()
            && $user->canManageWaitingList()
            ? WaitingListEntry::query()
                ->whereIn('status', WaitingListStatus::openValues())
                ->count()
            : 0;
        $inventory = FeatureModule::Inventory->enabled()
            && $user->canManageInventory()
            ? InventoryLoan::query()
                ->whereNull('returned_at')
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', today())
                ->count()
            : 0;

        return [
            'registrations' => $registrations,
            'meter_readings' => $meterReadings,
            'invoices' => $invoices,
            'work_hours' => $workHours,
            'work_events' => $workEvents,
            'work_hour_submissions' => $workHourSubmissions,
            'waiting_list' => $waitingList,
            'inventory' => $inventory,
            'members_group' => $registrations + $waitingList,
            'meters_group' => $meterReadings,
            'finance_group' => $invoices + $workHours + $workEvents + $workHourSubmissions,
            'communication_group' => $failedCampaigns,
            'dunning_notices' => $dunningNotices,
            'total' => $registrations + $waitingList + $meterReadings + $invoices + $workHours + $workEvents + $workHourSubmissions + $failedCampaigns + $inventory,
        ];
    }

    private function dunnableInvoiceCount(): int
    {
        return Invoice::query()
            ->with('activeDunningNotices')
            ->where('status', InvoiceStatus::Approved)
            ->whereIn('payment_status', [
                InvoicePaymentStatus::Open,
                InvoicePaymentStatus::Returned,
            ])
            ->whereDate('due_at', '<', today())
            ->get()
            ->filter(function (Invoice $invoice): bool {
                $latest = $invoice->activeDunningNotices->first();

                return $latest === null
                    || ($latest->level < 3 && $latest->due_at->isPast());
            })
            ->count();
    }
}
