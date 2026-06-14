<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'system_name',
    'default_board_permission_profile_id',
    'default_work_hours_required',
    'default_work_hour_penalty_rate',
])]
class ApplicationSetting extends Model
{
    protected function casts(): array
    {
        return [
            'default_work_hours_required' => 'decimal:2',
            'default_work_hour_penalty_rate' => 'decimal:2',
        ];
    }

    public function defaultBoardPermissionProfile(): BelongsTo
    {
        return $this->belongsTo(
            PermissionProfile::class,
            'default_board_permission_profile_id',
        );
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'system_name' => config('app.name', 'OKGV'),
        ]);
    }
}
