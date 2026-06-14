<?php

namespace App\Policies;

use App\Models\MeterReading;
use App\Models\User;

class MeterReadingPolicy
{
    public function view(User $user, MeterReading $meterReading): bool
    {
        return $user->can('view', $meterReading->meter);
    }

    public function create(User $user): bool
    {
        return $user->canManageMeters();
    }
}
