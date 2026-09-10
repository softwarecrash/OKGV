<?php

namespace App\Models;

use App\Enums\FeatureModule;
use App\Enums\TaskRecurrence;
use App\Enums\UserPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BoardFollowUp extends Task
{
    public function scopeActionableFor(Builder $query, User $user): Builder
    {
        if (! $user->can('viewAny', BoardMeeting::class)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereNotNull('board_resolution_id')->open()
            ->when(! FeatureModule::Tasks->enabled(), fn (Builder $query) => $query->where('recurrence', TaskRecurrence::None))
            ->whereDate(DB::raw('COALESCE(remind_at, due_at)'), '<=', today())
            ->when(! $user->hasPermission(UserPermission::ManageBoardWork) && ! $user->hasPermission(UserPermission::ManageTasks), fn (Builder $query) => $query->where('assigned_to', $user->id));
    }
}
