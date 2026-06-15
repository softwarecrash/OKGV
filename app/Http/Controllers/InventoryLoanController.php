<?php

namespace App\Http\Controllers;

use App\Enums\InventoryItemStatus;
use App\Http\Requests\InventoryLoanRequest;
use App\Http\Requests\InventoryReturnRequest;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\Member;
use App\Services\InventoryManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InventoryLoanController extends Controller
{
    public function create(InventoryItem $inventoryItem): View
    {
        $this->authorize('issue', $inventoryItem);

        return view('inventory-loans.create', [
            'item' => $inventoryItem,
            'members' => Member::query()
                ->whereNull('archived_at')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    public function store(
        InventoryLoanRequest $request,
        InventoryItem $inventoryItem,
        InventoryManager $manager,
    ): RedirectResponse {
        $manager->issue($inventoryItem, $request->validated(), $request->user());

        return redirect()->route('inventory-items.show', $inventoryItem)
            ->with('status', 'Gegenstand wurde ausgegeben.');
    }

    public function editReturn(
        InventoryItem $inventoryItem,
        InventoryLoan $inventoryLoan,
    ): View {
        $this->authorize('return', $inventoryItem);
        abort_unless(
            $inventoryLoan->inventory_item_id === $inventoryItem->id
                && $inventoryLoan->returned_at === null,
            404,
        );

        return view('inventory-loans.return', [
            'item' => $inventoryItem,
            'loan' => $inventoryLoan,
            'statuses' => InventoryItemStatus::returnStatuses(),
        ]);
    }

    public function updateReturn(
        InventoryReturnRequest $request,
        InventoryItem $inventoryItem,
        InventoryLoan $inventoryLoan,
        InventoryManager $manager,
    ): RedirectResponse {
        $manager->return(
            $inventoryItem,
            $inventoryLoan,
            $request->validated(),
            $request->user(),
        );

        return redirect()->route('inventory-items.show', $inventoryItem)
            ->with('status', 'Rückgabe wurde erfasst.');
    }
}
