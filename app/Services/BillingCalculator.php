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
use App\Models\WorkHour;
use Carbon\CarbonInterface;
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

            if (! $period->canBeCalculated()) {
                throw ValidationException::withMessages([
                    'status' => 'Freigegebene oder archivierte Abrechnungen können nicht neu berechnet werden.',
                ]);
            }

            if ($period->invoices()->where('status', InvoiceStatus::Approved)->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Die Periode enthält bereits freigegebene Rechnungen.',
                ]);
            }

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
            $workHours = $period->workHours()
                ->with('parcel')
                ->get()
                ->keyBy('parcel_id');
            $members = Member::query()
                ->with(['parcelTenancies.parcel'])
                ->orderBy('id')
                ->get();

            foreach ($members as $member) {
                $primaryTenancies = $member->parcelTenancies
                    ->where('is_primary', true)
                    ->values();

                $this->createInvoice(
                    $period,
                    $member,
                    $primaryTenancies,
                    $rates,
                    $workHours,
                );
            }

            if ($period->invoices()->doesntExist()) {
                throw ValidationException::withMessages([
                    'status' => 'Für diese Periode wurden keine abrechnungsrelevanten Mitglieder oder Pächter gefunden.',
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

    /**
     * @param  Collection<int, ParcelTenant>  $primaryTenancies
     * @param  Collection<int, BillingRate>  $rates
     * @param  Collection<int, WorkHour>  $workHours
     */
    private function createInvoice(
        BillingPeriod $period,
        Member $member,
        Collection $primaryTenancies,
        Collection $rates,
        Collection $workHours,
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

        $this->snapshotRecipients($invoice, $member, $primaryTenancies, $rates);
        $total = '0.00';

        foreach ($rates as $rate) {
            $total = bcadd(
                $total,
                match ($rate->scope) {
                    BillingRateScope::Member => $this->addMemberRate(
                        $invoice,
                        $rate,
                        $member,
                    ),
                    BillingRateScope::Parcel => $this->addParcelRate(
                        $invoice,
                        $rate,
                        $primaryTenancies,
                    ),
                    BillingRateScope::Assignment => $this->addAssignedRate(
                        $invoice,
                        $rate,
                        $member,
                        $primaryTenancies,
                    ),
                },
                2,
            );
        }

        $total = bcadd(
            $total,
            $this->addWorkHourPenalties(
                $invoice,
                $primaryTenancies
                    ->filter(fn (ParcelTenant $tenancy) => $this->overlap(
                        $tenancy->starts_at,
                        $tenancy->ends_at,
                        $period->ends_at,
                        $period->ends_at,
                    ))
                    ->pluck('parcel')
                    ->unique('id')
                    ->values(),
                $workHours,
            ),
            2,
        );

        if ($invoice->items()->doesntExist()) {
            $invoice->recipients()->delete();
            $invoice->delete();

            return;
        }

        $invoice->update(['total_amount' => $total]);
    }

    /**
     * @param  Collection<int, ParcelTenant>  $primaryTenancies
     * @param  Collection<int, BillingRate>  $rates
     */
    private function snapshotRecipients(
        Invoice $invoice,
        Member $member,
        Collection $primaryTenancies,
        Collection $rates,
    ): void {
        $recipients = collect([$member]);
        $serviceStart = $rates->min('service_starts_at');
        $serviceEnd = $rates->max('service_ends_at');

        if ($serviceStart && $serviceEnd) {
            foreach ($primaryTenancies as $primaryTenancy) {
                $primaryOverlap = $this->overlap(
                    $primaryTenancy->starts_at,
                    $primaryTenancy->ends_at,
                    $serviceStart,
                    $serviceEnd,
                );

                if (! $primaryOverlap) {
                    continue;
                }

                $recipients = $recipients->merge(
                    ParcelTenant::query()
                        ->where('parcel_id', $primaryTenancy->parcel_id)
                        ->whereDate('starts_at', '<=', $primaryOverlap['end'])
                        ->where(function ($query) use ($primaryOverlap): void {
                            $query->whereNull('ends_at')
                                ->orWhereDate('ends_at', '>=', $primaryOverlap['start']);
                        })
                        ->with('member')
                        ->get()
                        ->pluck('member'),
                );
            }
        }

        $orderedRecipients = $recipients
            ->filter()
            ->unique('id')
            ->sortByDesc(fn (Member $recipient): bool => $recipient->is($member))
            ->values();

        foreach ($orderedRecipients as $position => $recipient) {
            $invoice->recipients()->create([
                'member_id' => $recipient->id,
                'member_number' => $recipient->member_number,
                'first_name' => $recipient->first_name,
                'last_name' => $recipient->last_name,
                'street' => $recipient->street,
                'zip' => $recipient->zip,
                'city' => $recipient->city,
                'is_primary' => $recipient->is($member),
                'position' => $position,
            ]);
        }
    }

    private function addMemberRate(
        Invoice $invoice,
        BillingRate $rate,
        Member $member,
    ): string {
        $overlap = $this->overlap(
            $member->joined_at,
            $member->left_at,
            $rate->service_starts_at,
            $rate->service_ends_at,
        );

        if (! $overlap) {
            return '0.00';
        }

        $factor = $rate->prorate
            ? $this->prorationFactor($overlap, $rate)
            : '1.00000000';

        return $this->createItem(
            $invoice,
            $rate,
            null,
            $factor,
            $this->periodMetadata($rate, $overlap, $factor),
        );
    }

    /**
     * @param  Collection<int, ParcelTenant>  $primaryTenancies
     */
    private function addParcelRate(
        Invoice $invoice,
        BillingRate $rate,
        Collection $primaryTenancies,
    ): string {
        $total = '0.00';

        foreach ($primaryTenancies->groupBy('parcel_id') as $parcelTenancies) {
            /** @var Parcel $parcel */
            $parcel = $parcelTenancies->first()->parcel;
            $overlaps = $parcelTenancies
                ->map(fn (ParcelTenant $tenancy) => $this->overlap(
                    $tenancy->starts_at,
                    $tenancy->ends_at,
                    $rate->service_starts_at,
                    $rate->service_ends_at,
                ))
                ->filter()
                ->values();

            if ($overlaps->isEmpty()) {
                continue;
            }

            $quantity = match ($rate->calculation_type) {
                BillingRateType::Fixed, BillingRateType::Manual => $rate->prorate
                    ? $this->combinedProrationFactor($overlaps, $rate)
                    : '1.0000',
                BillingRateType::PerSquareMeter => $rate->prorate
                    ? bcmul(
                        $parcel->area_sqm,
                        $this->combinedProrationFactor($overlaps, $rate),
                        8,
                    )
                    : $parcel->area_sqm,
                BillingRateType::PerKilowattHour => $this->consumptionForOverlaps(
                    $parcel,
                    MeterType::Electricity,
                    $overlaps,
                ),
                BillingRateType::PerCubicMeter => $this->consumptionForOverlaps(
                    $parcel,
                    MeterType::Water,
                    $overlaps,
                ),
            };
            $factor = $rate->prorate
                ? $this->combinedProrationFactor($overlaps, $rate)
                : '1.00000000';

            $total = bcadd(
                $total,
                $this->createItem(
                    $invoice,
                    $rate,
                    $parcel,
                    $quantity,
                    $this->periodMetadata($rate, null, $factor, $overlaps),
                ),
                2,
            );
        }

        return $total;
    }

    /**
     * @param  Collection<int, ParcelTenant>  $primaryTenancies
     */
    private function addAssignedRate(
        Invoice $invoice,
        BillingRate $rate,
        Member $member,
        Collection $primaryTenancies,
    ): string {
        $total = '0.00';
        $assignments = $rate->assignments->filter(
            fn (BillingRateAssignment $assignment) => $assignment->member_id === $member->id
                || ($assignment->parcel_id
                    && $primaryTenancies->contains('parcel_id', $assignment->parcel_id)),
        );

        foreach ($assignments as $assignment) {
            if ($assignment->member_id) {
                $overlap = $this->overlap(
                    $member->joined_at,
                    $member->left_at,
                    $rate->service_starts_at,
                    $rate->service_ends_at,
                );

                if (! $overlap) {
                    continue;
                }

                $factor = $rate->prorate
                    ? $this->prorationFactor($overlap, $rate)
                    : '1.00000000';
                $quantity = $rate->prorate
                    ? bcmul($assignment->quantity, $factor, 8)
                    : $assignment->quantity;
                $metadata = $this->periodMetadata($rate, $overlap, $factor);
            } else {
                $parcelTenancies = $primaryTenancies
                    ->where('parcel_id', $assignment->parcel_id);
                $overlaps = $parcelTenancies
                    ->map(fn (ParcelTenant $tenancy) => $this->overlap(
                        $tenancy->starts_at,
                        $tenancy->ends_at,
                        $rate->service_starts_at,
                        $rate->service_ends_at,
                    ))
                    ->filter()
                    ->values();

                if ($overlaps->isEmpty()) {
                    continue;
                }

                $factor = $rate->prorate
                    ? $this->combinedProrationFactor($overlaps, $rate)
                    : '1.00000000';
                $quantity = $rate->prorate
                    ? bcmul($assignment->quantity, $factor, 8)
                    : $assignment->quantity;
                $metadata = $this->periodMetadata($rate, null, $factor, $overlaps);
            }

            $metadata['assignment_id'] = $assignment->id;
            $total = bcadd(
                $total,
                $this->createItem(
                    $invoice,
                    $rate,
                    $assignment->parcel,
                    $quantity,
                    $metadata,
                ),
                2,
            );
        }

        return $total;
    }

    /**
     * @param  Collection<int, Parcel>  $parcels
     * @param  Collection<int, WorkHour>  $workHours
     */
    private function addWorkHourPenalties(
        Invoice $invoice,
        Collection $parcels,
        Collection $workHours,
    ): string {
        $total = '0.00';

        foreach ($parcels as $parcel) {
            $workHour = $workHours->get($parcel->id);

            if (! $workHour || bccomp($workHour->penalty_amount, '0.00', 2) <= 0) {
                continue;
            }

            $invoice->items()->create([
                'billing_rate_id' => null,
                'parcel_id' => $parcel->id,
                'code' => 'WORK_HOURS_PENALTY',
                'description' => "Fehlende Arbeitsstunden - Parzelle {$parcel->parcel_number}",
                'calculation_type' => BillingRateType::Manual,
                'quantity' => $workHour->hours_missing,
                'unit_price' => $workHour->penalty_rate,
                'total_amount' => $workHour->penalty_amount,
                'metadata' => [
                    'work_hour_id' => $workHour->id,
                    'parcel_id' => $parcel->id,
                    'hours_required' => $workHour->hours_required,
                    'hours_done' => $workHour->hours_done,
                ],
            ]);

            $total = bcadd($total, $workHour->penalty_amount, 2);
        }

        return $total;
    }

    /**
     * @param  Collection<int, array{start: CarbonInterface, end: CarbonInterface}>  $overlaps
     */
    private function consumptionForOverlaps(
        Parcel $parcel,
        MeterType $type,
        Collection $overlaps,
    ): string {
        return $overlaps->reduce(
            fn (string $total, array $overlap): string => bcadd(
                $total,
                $this->consumptionCalculator->forParcel(
                    $parcel->id,
                    $type->value,
                    $overlap['start'],
                    $overlap['end'],
                ),
                4,
            ),
            '0.0000',
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function createItem(
        Invoice $invoice,
        BillingRate $rate,
        ?Parcel $parcel,
        string $quantity,
        array $metadata,
    ): string {
        $total = $this->roundMoney(bcmul($quantity, $rate->amount, 8));
        $description = $parcel
            ? "{$rate->name} - Parzelle {$parcel->parcel_number}"
            : $rate->name;
        $description .= sprintf(
            ' (%s–%s)',
            $rate->service_starts_at->format('d.m.Y'),
            $rate->service_ends_at->format('d.m.Y'),
        );

        $invoice->items()->create([
            'billing_rate_id' => $rate->id,
            'parcel_id' => $parcel?->id,
            'code' => $rate->code,
            'description' => $description,
            'calculation_type' => $rate->calculation_type,
            'quantity' => $quantity,
            'unit_price' => $rate->amount,
            'total_amount' => $total,
            'metadata' => $metadata,
        ]);

        return $total;
    }

    /**
     * @return array{start: CarbonInterface, end: CarbonInterface}|null
     */
    private function overlap(
        CarbonInterface $startsAt,
        ?CarbonInterface $endsAt,
        CarbonInterface $serviceStartsAt,
        CarbonInterface $serviceEndsAt,
    ): ?array {
        $start = $startsAt->gt($serviceStartsAt)
            ? $startsAt->copy()
            : $serviceStartsAt->copy();
        $end = $endsAt && $endsAt->lt($serviceEndsAt)
            ? $endsAt->copy()
            : $serviceEndsAt->copy();

        if ($start->gt($end)) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @param  array{start: CarbonInterface, end: CarbonInterface}  $overlap
     */
    private function prorationFactor(array $overlap, BillingRate $rate): string
    {
        $usedDays = $overlap['start']->diffInDays($overlap['end']) + 1;
        $serviceDays = $rate->service_starts_at->diffInDays($rate->service_ends_at) + 1;

        return bcdiv((string) $usedDays, (string) $serviceDays, 8);
    }

    /**
     * @param  Collection<int, array{start: CarbonInterface, end: CarbonInterface}>  $overlaps
     */
    private function combinedProrationFactor(
        Collection $overlaps,
        BillingRate $rate,
    ): string {
        return $overlaps->reduce(
            fn (string $total, array $overlap): string => bcadd(
                $total,
                $this->prorationFactor($overlap, $rate),
                8,
            ),
            '0.00000000',
        );
    }

    /**
     * @param  array{start: CarbonInterface, end: CarbonInterface}|null  $overlap
     * @param  Collection<int, array{start: CarbonInterface, end: CarbonInterface}>|null  $overlaps
     * @return array<string, mixed>
     */
    private function periodMetadata(
        BillingRate $rate,
        ?array $overlap,
        string $factor,
        ?Collection $overlaps = null,
    ): array {
        $usagePeriods = $overlaps
            ? $overlaps->map(fn (array $range): array => [
                'starts_at' => $range['start']->toDateString(),
                'ends_at' => $range['end']->toDateString(),
            ])->all()
            : [[
                'starts_at' => $overlap['start']->toDateString(),
                'ends_at' => $overlap['end']->toDateString(),
            ]];

        return [
            'settlement_type' => $rate->settlement_type->value,
            'service_starts_at' => $rate->service_starts_at->toDateString(),
            'service_ends_at' => $rate->service_ends_at->toDateString(),
            'prorated' => $rate->prorate,
            'proration_factor' => $factor,
            'usage_periods' => $usagePeriods,
        ];
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
