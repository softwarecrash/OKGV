<?php

namespace App\Services;

use App\Enums\BillingPeriodStatus;
use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Models\BillingPeriod;
use App\Models\BillingRate;
use App\Models\BillingRateAssignment;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BillingCalculator
{
    public function __construct(
        private readonly ConsumptionCalculator $consumptionCalculator,
    ) {}

    public function calculate(BillingPeriod $period, User $actor): BillingPeriod
    {
        return DB::transaction(function () use ($period, $actor): BillingPeriod {
            $period = BillingPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if ($period->status !== BillingPeriodStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Nur eine Abrechnungsperiode im Entwurf kann berechnet werden.',
                ]);
            }

            if ($period->invoices()->where('status', InvoiceStatus::Approved)->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Die Periode enthält bereits freigegebene Rechnungen.',
                ]);
            }

            $this->ensureNoTenantChanges($period);
            $period->invoices()->each(function (Invoice $invoice): void {
                $invoice->items()->delete();
                $invoice->recipients()->delete();
                $invoice->delete();
            });

            $rates = $period->rates()
                ->where('is_active', true)
                ->with(['assignments.member', 'assignments.parcel'])
                ->orderBy('id')
                ->get();

            $tenancies = ParcelTenant::query()
                ->where('is_primary', true)
                ->activeOn($period->ends_at)
                ->with(['member', 'parcel'])
                ->orderBy('member_id')
                ->orderBy('parcel_id')
                ->get()
                ->groupBy('member_id');

            foreach ($tenancies as $memberTenancies) {
                $member = $memberTenancies->first()->member;
                $parcels = $memberTenancies->pluck('parcel')->unique('id')->values();
                $contractParties = ParcelTenant::query()
                    ->whereIn('parcel_id', $parcels->pluck('id'))
                    ->activeOn($period->ends_at)
                    ->with('member')
                    ->orderByDesc('is_primary')
                    ->orderBy('starts_at')
                    ->get()
                    ->pluck('member')
                    ->unique('id')
                    ->values();

                $this->createInvoice($period, $member, $contractParties, $parcels, $rates);
            }

            if ($period->invoices()->doesntExist()) {
                throw ValidationException::withMessages([
                    'status' => 'Für diese Periode wurden keine abrechnungsrelevanten Hauptpächter gefunden.',
                ]);
            }

            $period->update([
                'status' => BillingPeriodStatus::Calculated,
                'calculated_at' => now(),
            ]);

            AuditLogger::log(
                action: 'billing.period.calculated',
                actor: $actor,
                subject: $period,
                metadata: ['invoice_count' => $period->invoices()->count()],
            );

            return $period->refresh();
        });
    }

    private function ensureNoTenantChanges(BillingPeriod $period): void
    {
        $changedParcel = ParcelTenant::query()
            ->select('parcel_id')
            ->where('is_primary', true)
            ->whereDate('starts_at', '<=', $period->ends_at)
            ->where(function ($query) use ($period): void {
                $query->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $period->starts_at);
            })
            ->groupBy('parcel_id')
            ->havingRaw('COUNT(*) > 1')
            ->with('parcel:id,parcel_number')
            ->first();

        if ($changedParcel) {
            throw ValidationException::withMessages([
                'status' => "Parzelle {$changedParcel->parcel->parcel_number} hat innerhalb der Periode einen Pächterwechsel.",
            ]);
        }
    }

    /**
     * @param  Collection<int, Parcel>  $parcels
     * @param  Collection<int, Member>  $contractParties
     * @param  Collection<int, BillingRate>  $rates
     */
    private function createInvoice(
        BillingPeriod $period,
        Member $member,
        Collection $contractParties,
        Collection $parcels,
        Collection $rates,
    ): void {
        $invoice = Invoice::create([
            'billing_period_id' => $period->id,
            'member_id' => $member->id,
            'invoice_number' => $this->nextInvoiceNumber($period),
            'status' => InvoiceStatus::Draft,
            'issued_at' => $period->ends_at,
            'due_at' => $period->due_at,
            'total_amount' => '0.00',
        ]);

        $orderedRecipients = $contractParties
            ->sortByDesc(fn (Member $contractParty): bool => $contractParty->is($member))
            ->values();

        foreach ($orderedRecipients as $position => $contractParty) {
            $invoice->recipients()->create([
                'member_id' => $contractParty->id,
                'member_number' => $contractParty->member_number,
                'first_name' => $contractParty->first_name,
                'last_name' => $contractParty->last_name,
                'street' => $contractParty->street,
                'zip' => $contractParty->zip,
                'city' => $contractParty->city,
                'is_primary' => $contractParty->is($member),
                'position' => $position,
            ]);
        }

        $total = '0.00';

        foreach ($rates as $rate) {
            $total = bcadd(
                $total,
                match ($rate->scope) {
                    BillingRateScope::Member => $this->addMemberRate($invoice, $rate),
                    BillingRateScope::Parcel => $this->addParcelRate($invoice, $rate, $parcels, $period),
                    BillingRateScope::Assignment => $this->addAssignedRate(
                        $invoice,
                        $rate,
                        $member,
                        $parcels,
                    ),
                },
                2,
            );
        }

        $invoice->update(['total_amount' => $total]);
    }

    private function addMemberRate(Invoice $invoice, BillingRate $rate): string
    {
        return $this->createItem($invoice, $rate, null, '1.0000');
    }

    /**
     * @param  Collection<int, Parcel>  $parcels
     */
    private function addParcelRate(
        Invoice $invoice,
        BillingRate $rate,
        Collection $parcels,
        BillingPeriod $period,
    ): string {
        $total = '0.00';

        foreach ($parcels as $parcel) {
            $quantity = match ($rate->calculation_type) {
                BillingRateType::Fixed, BillingRateType::Manual => '1.0000',
                BillingRateType::PerSquareMeter => $parcel->area_sqm,
                BillingRateType::PerKilowattHour => $this->consumptionCalculator->forParcel(
                    $parcel->id,
                    MeterType::Electricity->value,
                    $period->starts_at,
                    $period->ends_at,
                ),
                BillingRateType::PerCubicMeter => $this->consumptionCalculator->forParcel(
                    $parcel->id,
                    MeterType::Water->value,
                    $period->starts_at,
                    $period->ends_at,
                ),
            };

            $total = bcadd(
                $total,
                $this->createItem($invoice, $rate, $parcel, $quantity),
                2,
            );
        }

        return $total;
    }

    /**
     * @param  Collection<int, Parcel>  $parcels
     */
    private function addAssignedRate(
        Invoice $invoice,
        BillingRate $rate,
        Member $member,
        Collection $parcels,
    ): string {
        $total = '0.00';
        $parcelIds = $parcels->pluck('id')->all();

        $assignments = $rate->assignments->filter(
            fn (BillingRateAssignment $assignment) => $assignment->member_id === $member->id
                || ($assignment->parcel_id && in_array($assignment->parcel_id, $parcelIds, true)),
        );

        foreach ($assignments as $assignment) {
            $total = bcadd(
                $total,
                $this->createItem(
                    $invoice,
                    $rate,
                    $assignment->parcel,
                    $assignment->quantity,
                    ['assignment_id' => $assignment->id],
                ),
                2,
            );
        }

        return $total;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function createItem(
        Invoice $invoice,
        BillingRate $rate,
        ?Parcel $parcel,
        string $quantity,
        array $metadata = [],
    ): string {
        $total = $this->roundMoney(bcmul($quantity, $rate->amount, 8));
        $description = $parcel
            ? "{$rate->name} - Parzelle {$parcel->parcel_number}"
            : $rate->name;

        $invoice->items()->create([
            'billing_rate_id' => $rate->id,
            'parcel_id' => $parcel?->id,
            'code' => $rate->code,
            'description' => $description,
            'calculation_type' => $rate->calculation_type,
            'quantity' => $quantity,
            'unit_price' => $rate->amount,
            'total_amount' => $total,
            'metadata' => $metadata ?: null,
        ]);

        return $total;
    }

    private function nextInvoiceNumber(BillingPeriod $period): string
    {
        $year = $period->ends_at->format('Y');
        $prefix = "{$year}-";
        $lastNumber = Invoice::query()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('invoice_number')
            ->lockForUpdate()
            ->value('invoice_number');
        $sequence = $lastNumber ? ((int) substr($lastNumber, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function roundMoney(string $value): string
    {
        $cents = bcdiv(bcadd(bcmul($value, '100', 8), '0.5', 8), '1', 0);

        return bcdiv($cents, '100', 2);
    }
}
