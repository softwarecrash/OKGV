<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['system_name', 'default_board_permission_profile_id'])]
class ApplicationSetting extends Model
{
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
