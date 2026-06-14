<?php

namespace App\Policies;

use App\Enums\PaymentBatchItemStatus;
use App\Enums\PaymentBatchStatus;
use App\Models\PaymentBatchItem;
use App\Models\User;

class PaymentBatchItemPolicy
{
    public function return(User $user, PaymentBatchItem $item): bool
    {
        return $user->canManageSepa()
            && $item->status !== PaymentBatchItemStatus::Returned
            && in_array($item->batch->status, [
                PaymentBatchStatus::Submitted,
                PaymentBatchStatus::Settled,
                PaymentBatchStatus::PartiallyReturned,
            ], true);
    }
}
