<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManageInventory();
    }

    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->canManageInventory();
    }

    public function create(User $user): bool
    {
        return $user->canManageInventory();
    }

    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->canManageInventory();
    }

    public function issue(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->canManageInventory();
    }

    public function return(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->canManageInventory();
    }
}
