<?php

namespace App\Services;

use App\Enums\BillingPeriodStatus;
use App\Enums\InvoiceStatus;
use App\Models\BillingPeriod;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class BillingPeriodManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(
        array $data,
        ?BillingPeriod $period = null,
        ?User $actor = null,
    ): BillingPeriod {
        return DB::transaction(function () use ($data, $period, $actor): BillingPeriod {
            BillingPeriod::query()->lockForUpdate()->get();

            if ($period) {
                $period = BillingPeriod::query()->lockForUpdate()->findOrFail($period->id);
            }

            if ($period && ! $period->isEditable()) {
                throw ValidationException::withMessages([
                    'status' => 'Nur Entwürfe und berechnete Zwischenstände dürfen geändert werden.',
                ]);
            }

            $overlapExists = BillingPeriod::query()
                ->whereDate('starts_at', '<=', $data['ends_at'])
                ->whereDate('ends_at', '>=', $data['starts_at'])
                ->when($period, fn ($query) => $query->whereKeyNot($period->id))
                ->exists();

            if ($overlapExists) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Der Zeitraum überschneidet sich mit einer bestehenden Abrechnungsperiode.',
                ]);
            }

            $period ??= new BillingPeriod;
            $period->fill($data)->save();

            if ($period->status === BillingPeriodStatus::Calculated) {
                $this->discardCalculation(
                    $period,
                    $actor,
                    'billing_period_updated',
                );
            }

            return $period;
        });
    }

    public function changeCalculationInputs(
        BillingPeriod $period,
        User $actor,
        string $reason,
        Closure $change,
    ): mixed {
        return DB::transaction(function () use ($period, $actor, $reason, $change): mixed {
            $period = BillingPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if (! $period->isEditable()) {
                throw ValidationException::withMessages([
                    'status' => 'Freigegebene oder archivierte Abrechnungen können nicht mehr geändert werden.',
                ]);
            }

            $result = $change($period);

            if ($period->status === BillingPeriodStatus::Calculated) {
                $this->discardCalculation($period, $actor, $reason);
            }

            return $result;
        });
    }

    public function approve(BillingPeriod $period, User $actor): BillingPeriod
    {
        return DB::transaction(function () use ($period, $actor): BillingPeriod {
            $period = BillingPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if ($period->status !== BillingPeriodStatus::Calculated) {
                throw ValidationException::withMessages([
                    'status' => 'Nur eine berechnete Abrechnungsperiode kann freigegeben werden.',
                ]);
            }

            $invoices = $period->invoices()
                ->where('status', InvoiceStatus::Draft)
                ->lockForUpdate()
                ->get();

            if ($invoices->isEmpty()) {
                throw ValidationException::withMessages([
                    'status' => 'Die Abrechnungsperiode enthält keine Rechnungsentwürfe.',
                ]);
            }

            $approvedAt = now();

            foreach ($invoices as $invoice) {
                $invoice->update([
                    'status' => InvoiceStatus::Approved,
                    'approved_at' => $approvedAt,
                    'approved_by' => $actor->id,
                ]);
            }

            $period->update([
                'status' => BillingPeriodStatus::Approved,
                'approved_at' => $approvedAt,
            ]);

            AuditLogger::log(
                action: 'billing.period.approved',
                actor: $actor,
                subject: $period,
                metadata: ['invoice_count' => $invoices->count()],
            );

            return $period->refresh();
        });
    }

    public function archive(BillingPeriod $period, User $actor): BillingPeriod
    {
        return DB::transaction(function () use ($period, $actor): BillingPeriod {
            $period = BillingPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if ($period->status !== BillingPeriodStatus::Approved) {
                throw ValidationException::withMessages([
                    'status' => 'Nur eine freigegebene Abrechnungsperiode kann archiviert werden.',
                ]);
            }

            $period->update([
                'status' => BillingPeriodStatus::Archived,
                'archived_at' => now(),
            ]);

            AuditLogger::log(
                action: 'billing.period.archived',
                actor: $actor,
                subject: $period,
            );

            return $period;
        });
    }

    private function discardCalculation(
        BillingPeriod $period,
        ?User $actor,
        string $reason,
    ): void {
        if ($period->invoices()->where('status', InvoiceStatus::Approved)->exists()) {
            throw ValidationException::withMessages([
                'status' => 'Die Periode enthält bereits freigegebene Rechnungen und kann nicht zurückgesetzt werden.',
            ]);
        }

        $drafts = $period->invoices()
            ->where('status', InvoiceStatus::Draft)
            ->lockForUpdate()
            ->get();

        foreach ($drafts as $invoice) {
            $invoice->items()->delete();
            $invoice->recipients()->delete();
            $invoice->delete();
        }

        $period->update([
            'status' => BillingPeriodStatus::Draft,
            'calculated_at' => null,
        ]);

        if ($actor) {
            AuditLogger::log(
                action: 'billing.period.calculation_discarded',
                actor: $actor,
                subject: $period,
                metadata: [
                    'invoice_count' => $drafts->count(),
                    'reason' => $reason,
                ],
            );
        }
    }
}
