<?php

namespace App\Http\Controllers;

use App\Enums\InventoryItemStatus;
use App\Http\Requests\InventoryItemRequest;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', InventoryItem::class);

        $items = InventoryItem::query()
            ->with('openLoans')
            ->search($request->string('q')->trim()->toString())
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where('status', $request->string('status')->toString()),
            )
            ->when(
                $request->filled('category'),
                fn ($query) => $query->where('category', $request->string('category')->toString()),
            )
            ->orderBy('inventory_number')
            ->paginate(20)
            ->withQueryString();

        return view('inventory-items.index', [
            'items' => $items,
            'statuses' => InventoryItemStatus::cases(),
            'categories' => InventoryItem::query()
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'overdueCount' => InventoryLoan::query()
                ->whereNull('returned_at')
                ->whereNotNull('due_at')
                ->whereDate('due_at', '<', today())
                ->count(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', InventoryItem::class);

        return view('inventory-items.create', [
            'item' => new InventoryItem(['status' => InventoryItemStatus::Available]),
            'statuses' => InventoryItemStatus::manuallySelectable(),
        ]);
    }

    public function store(InventoryItemRequest $request): RedirectResponse
    {
        $item = InventoryItem::create($request->validated());
        AuditLogger::log('inventory.item_created', $request->user(), $item);

        return redirect()->route('inventory-items.show', $item)
            ->with('status', 'Inventargegenstand wurde angelegt.');
    }

    public function show(InventoryItem $inventoryItem): View
    {
        $this->authorize('view', $inventoryItem);
        $inventoryItem->load(['loans.member', 'loans.issuer', 'loans.receiver']);

        return view('inventory-items.show', [
            'item' => $inventoryItem,
            'openLoan' => $inventoryItem->loans->firstWhere('returned_at', null),
        ]);
    }

    public function edit(InventoryItem $inventoryItem): View
    {
        $this->authorize('update', $inventoryItem);

        return view('inventory-items.edit', [
            'item' => $inventoryItem,
            'statuses' => InventoryItemStatus::manuallySelectable(),
        ]);
    }

    public function update(
        InventoryItemRequest $request,
        InventoryItem $inventoryItem,
    ): RedirectResponse {
        $data = $request->validated();
        if ($inventoryItem->status === InventoryItemStatus::Issued) {
            unset($data['status']);
        }

        $before = $inventoryItem->only([
            'inventory_number',
            'name',
            'category',
            'status',
            'location',
            'purchased_at',
            'purchase_price',
            'serial_number',
        ]);
        $inventoryItem->update($data);

        AuditLogger::log('inventory.item_updated', $request->user(), $inventoryItem, [
            'before' => $before,
            'changed_fields' => array_keys($inventoryItem->getChanges()),
        ]);

        return redirect()->route('inventory-items.show', $inventoryItem)
            ->with('status', 'Inventargegenstand wurde aktualisiert.');
    }
}
