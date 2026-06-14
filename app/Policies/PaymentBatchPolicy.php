<?php

namespace App\Policies;

use App\Enums\PaymentBatchStatus;
use App\Models\PaymentBatch;
use App\Models\User;

class PaymentBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canManageSepa();
    }

    public function view(User $user, PaymentBatch $batch): bool
    {
        return $user->role->canManageSepa();
    }

    public function create(User $user): bool
    {
        return $user->role->canManageSepa();
    }

    public function export(User $user, PaymentBatch $batch): bool
    {
        return $user->role->canManageSepa()
            && in_array($batch->status, [
                PaymentBatchStatus::Draft,
                PaymentBatchStatus::Exported,
            ], true);
    }

    public function submit(User $user, PaymentBatch $batch): bool
    {
        return $user->role->canManageSepa()
            && $batch->status === PaymentBatchStatus::Exported;
    }

    public function settle(User $user, PaymentBatch $batch): bool
    {
        return $user->role->canManageSepa()
            && in_array($batch->status, [
                PaymentBatchStatus::Submitted,
                PaymentBatchStatus::PartiallyReturned,
            ], true);
    }
}
