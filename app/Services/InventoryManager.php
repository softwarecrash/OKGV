<?php

namespace App\Services;

use App\Enums\InventoryItemStatus;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class InventoryManager
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function issue(InventoryItem $item, array $data, User $actor): InventoryLoan
    {
        return DB::transaction(function () use ($item, $data, $actor): InventoryLoan {
            $lockedItem = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);

            if ($lockedItem->status !== InventoryItemStatus::Available
                || $lockedItem->openLoans()->exists()) {
                throw ValidationException::withMessages([
                    'inventory_item' => 'Dieser Gegenstand ist nicht verfügbar und kann nicht ausgegeben werden.',
                ]);
            }

            if (! empty($data['member_id'])) {
                $data['borrower_name'] = Member::query()
                    ->whereNull('archived_at')
                    ->findOrFail($data['member_id'])
                    ->full_name;
            }

            $loan = $lockedItem->loans()->create([
                ...$data,
                'issued_by' => $actor->id,
            ]);
            $lockedItem->update(['status' => InventoryItemStatus::Issued]);

            AuditLogger::log('inventory.item_issued', $actor, $lockedItem, [
                'loan_id' => $loan->id,
                'member_id' => $loan->member_id,
                'issued_at' => $loan->issued_at->toDateString(),
                'due_at' => $loan->due_at?->toDateString(),
            ]);

            return $loan;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function return(InventoryItem $item, InventoryLoan $loan, array $data, User $actor): void
    {
        DB::transaction(function () use ($item, $loan, $data, $actor): void {
            $lockedItem = InventoryItem::query()->lockForUpdate()->findOrFail($item->id);
            $lockedLoan = InventoryLoan::query()->lockForUpdate()->findOrFail($loan->id);

            if ($lockedLoan->inventory_item_id !== $lockedItem->id || $lockedLoan->returned_at !== null) {
                throw ValidationException::withMessages([
                    'inventory_item' => 'Diese Ausgabe ist bereits abgeschlossen oder gehört nicht zum Gegenstand.',
                ]);
            }

            $lockedLoan->update([
                'returned_at' => $data['returned_at'],
                'returned_by' => $actor->id,
                'condition_on_return' => $data['condition_on_return'] ?? null,
            ]);
            $lockedItem->update([
                'status' => InventoryItemStatus::from($data['return_status']),
            ]);

            AuditLogger::log('inventory.item_returned', $actor, $lockedItem, [
                'loan_id' => $lockedLoan->id,
                'returned_at' => $lockedLoan->returned_at->toDateString(),
                'status' => $lockedItem->status->value,
            ]);
        });
    }
}
