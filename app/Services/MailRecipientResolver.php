<?php

namespace App\Services;

use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MailRecipientGroup;
use App\Enums\MemberStatus;
use App\Enums\MeterStatus;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class MailRecipientResolver
{
    /**
     * @return Collection<int, array{member_id: int|null, name: string, email: string}>
     */
    public function resolve(MailRecipientGroup $group): Collection
    {
        if (! $group->isAvailable()) {
            throw ValidationException::withMessages([
                'recipient_group' => 'Diese Empfängergruppe ist nicht verfügbar, weil das zugehörige Funktionsmodul deaktiviert ist.',
            ]);
        }

        $recipients = match ($group) {
            MailRecipientGroup::ActiveMembers => $this->activeMembers(),
            MailRecipientGroup::CurrentTenants => $this->currentTenants(),
            MailRecipientGroup::Board => $this->board(),
            MailRecipientGroup::OpenInvoices => $this->openInvoices(),
            MailRecipientGroup::MissingMeterReadings => $this->missingMeterReadings(),
        };

        return $recipients
            ->filter(fn (array $recipient): bool => filter_var($recipient['email'], FILTER_VALIDATE_EMAIL) !== false)
            ->unique(fn (array $recipient): string => mb_strtolower($recipient['email']))
            ->values();
    }

    private function activeMembers(): Collection
    {
        return Member::query()
            ->with('user')
            ->where('status', MemberStatus::Active)
            ->whereNull('archived_at')
            ->get()
            ->map(fn (Member $member): array => $this->memberRecipient($member));
    }

    private function currentTenants(): Collection
    {
        return Member::query()
            ->with('user')
            ->whereHas('parcelTenancies', fn ($query) => $query->activeOn())
            ->get()
            ->map(fn (Member $member): array => $this->memberRecipient($member));
    }

    private function board(): Collection
    {
        return User::query()
            ->whereIn('role', [UserRole::Administrator, UserRole::Board])
            ->whereNotNull('email_verified_at')
            ->get()
            ->map(fn (User $user): array => [
                'member_id' => $user->member?->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    private function openInvoices(): Collection
    {
        return Member::query()
            ->with('user')
            ->whereHas('invoiceRecipientSnapshots.invoice', fn ($query) => $query
                ->where('status', InvoiceStatus::Approved)
                ->whereIn('payment_status', [
                    InvoicePaymentStatus::Open,
                    InvoicePaymentStatus::Returned,
                ]))
            ->get()
            ->map(fn (Member $member): array => $this->memberRecipient($member));
    }

    private function missingMeterReadings(): Collection
    {
        $yearStart = now()->startOfYear()->toDateString();

        return Member::query()
            ->with('user')
            ->whereHas('parcelTenancies', fn ($tenancies) => $tenancies
                ->activeOn()
                ->whereHas('parcel.meters', fn ($meters) => $meters
                    ->where('status', MeterStatus::Active)
                    ->whereDoesntHave('readings', fn ($readings) => $readings
                        ->whereDate('reading_date', '>=', $yearStart))))
            ->get()
            ->map(fn (Member $member): array => $this->memberRecipient($member));
    }

    /**
     * @return array{member_id: int, name: string, email: string}
     */
    private function memberRecipient(Member $member): array
    {
        return [
            'member_id' => $member->id,
            'name' => $member->full_name,
            'email' => $member->email ?: ($member->user?->email ?? ''),
        ];
    }
}
