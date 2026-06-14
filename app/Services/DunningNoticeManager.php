<?php

namespace App\Services;

use App\Enums\DunningNoticeStatus;
use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Models\DunningNotice;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DunningNoticeManager
{
    /**
     * @param  array{due_at: string, fee_amount: numeric-string|int|float, note?: string|null}  $data
     */
    public function create(Invoice $invoice, array $data, User $actor): DunningNotice
    {
        return DB::transaction(function () use ($invoice, $data, $actor): DunningNotice {
            $invoice = Invoice::query()
                ->with('recipients')
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $this->ensureInvoiceCanBeDunned($invoice);

            $latest = DunningNotice::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', DunningNoticeStatus::Issued)
                ->orderByDesc('level')
                ->lockForUpdate()
                ->first();

            if ($latest && ! $latest->due_at->isPast()) {
                throw ValidationException::withMessages([
                    'due_at' => "Die Frist der Mahnstufe {$latest->level} endet erst am {$latest->due_at->format('d.m.Y')}.",
                ]);
            }

            $level = ($latest?->level ?? 0) + 1;

            if ($level > 3) {
                throw ValidationException::withMessages([
                    'invoice' => 'Für diese Rechnung wurden bereits drei aktive Mahnstufen ausgestellt.',
                ]);
            }

            $previousFees = (float) DunningNotice::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', DunningNoticeStatus::Issued)
                ->sum('fee_amount');
            $fee = round((float) $data['fee_amount'], 2);

            $notice = DunningNotice::create([
                'invoice_id' => $invoice->id,
                'notice_number' => $this->noticeNumber(),
                'level' => $level,
                'status' => DunningNoticeStatus::Issued,
                'invoice_number' => $invoice->invoice_number,
                'issued_at' => today(),
                'due_at' => $data['due_at'],
                'invoice_amount' => $invoice->total_amount,
                'fee_amount' => $fee,
                'previous_fees_amount' => $previousFees,
                'total_due' => round((float) $invoice->total_amount + $previousFees + $fee, 2),
                'recipients' => $invoice->recipients->map(fn ($recipient): array => [
                    'member_id' => $recipient->member_id,
                    'member_number' => $recipient->member_number,
                    'first_name' => $recipient->first_name,
                    'last_name' => $recipient->last_name,
                    'street' => $recipient->street,
                    'zip' => $recipient->zip,
                    'city' => $recipient->city,
                    'is_primary' => $recipient->is_primary,
                    'position' => $recipient->position,
                ])->values()->all(),
                'note' => $data['note'] ?? null,
                'created_by' => $actor->id,
            ]);

            AuditLogger::log('dunning_notice.created', $actor, $notice, [
                'invoice_id' => $invoice->id,
                'level' => $level,
                'fee_amount' => number_format($fee, 2, '.', ''),
                'total_due' => number_format((float) $notice->total_due, 2, '.', ''),
            ]);

            return $notice;
        });
    }

    public function cancel(DunningNotice $notice, string $reason, User $actor): void
    {
        DB::transaction(function () use ($notice, $reason, $actor): void {
            $notice = DunningNotice::query()->lockForUpdate()->findOrFail($notice->id);

            if ($notice->status !== DunningNoticeStatus::Issued) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Diese Mahnung wurde bereits storniert.',
                ]);
            }

            $latestActiveId = DunningNotice::query()
                ->where('invoice_id', $notice->invoice_id)
                ->where('status', DunningNoticeStatus::Issued)
                ->orderByDesc('level')
                ->value('id');

            if ($latestActiveId !== $notice->id) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => 'Nur die aktuell höchste aktive Mahnstufe kann storniert werden.',
                ]);
            }

            $notice->update([
                'status' => DunningNoticeStatus::Cancelled,
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancellation_reason' => $reason,
            ]);

            AuditLogger::log('dunning_notice.cancelled', $actor, $notice, [
                'reason' => $reason,
                'level' => $notice->level,
            ]);
        });
    }

    public function nextLevel(Invoice $invoice): int
    {
        return ((int) $invoice->activeDunningNotices()->max('level')) + 1;
    }

    private function ensureInvoiceCanBeDunned(Invoice $invoice): void
    {
        if ($invoice->status !== InvoiceStatus::Approved) {
            throw ValidationException::withMessages([
                'invoice' => 'Nur freigegebene Rechnungen können gemahnt werden.',
            ]);
        }

        if (! $invoice->due_at->isPast()) {
            throw ValidationException::withMessages([
                'invoice' => 'Die ursprüngliche Zahlungsfrist ist noch nicht abgelaufen.',
            ]);
        }

        if (! in_array($invoice->payment_status, [
            InvoicePaymentStatus::Open,
            InvoicePaymentStatus::Returned,
        ], true)) {
            throw ValidationException::withMessages([
                'invoice' => 'Die Rechnung ist nicht offen und kann deshalb nicht gemahnt werden.',
            ]);
        }
    }

    private function noticeNumber(): string
    {
        do {
            $number = 'M-'.today()->format('Y').'-'.Str::upper(Str::random(8));
        } while (DunningNotice::query()->where('notice_number', $number)->exists());

        return $number;
    }
}
