<?php

namespace App\Services;

use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MailCampaignStatus;
use App\Enums\MeterReadingSubmissionStatus;
use App\Enums\RegistrationRequestStatus;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\MailCampaign;
use App\Models\MeterReadingSubmission;
use App\Models\RegistrationRequest;
use App\Models\User;

final class ActionIndicatorService
{
    /**
     * @return array{
     *     registrations: int,
     *     meter_readings: int,
     *     invoices: int,
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
        $registrations = $user->canReviewTenantRegistrations()
            ? RegistrationRequest::query()
                ->where('status', RegistrationRequestStatus::Pending)
                ->count()
            : 0;

        $meterReadings = match (true) {
            $user->canReviewMeterReadingSubmissions() => MeterReadingSubmission::query()
                ->where('status', MeterReadingSubmissionStatus::Pending)
                ->count(),
            $user->role === UserRole::Tenant => MeterReadingSubmission::query()
                ->where('submitted_by', $user->id)
                ->where('status', MeterReadingSubmissionStatus::Rejected)
                ->count(),
            default => 0,
        };

        $invoices = match (true) {
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
        $dunningNotices = $user->canManageBilling() ? $invoices : 0;

        $failedCampaigns = $user->canManageCommunication()
            ? MailCampaign::query()->where('status', MailCampaignStatus::Failed)->count()
            : 0;

        return [
            'registrations' => $registrations,
            'meter_readings' => $meterReadings,
            'invoices' => $invoices,
            'members_group' => $registrations,
            'meters_group' => $meterReadings,
            'finance_group' => $invoices,
            'communication_group' => $failedCampaigns,
            'dunning_notices' => $dunningNotices,
            'total' => $registrations + $meterReadings + $invoices + $failedCampaigns,
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
