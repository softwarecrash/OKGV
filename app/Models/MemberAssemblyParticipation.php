<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['member_id', 'user_id', 'attendance_mode', 'attended_at', 'recorded_by'])]
class MemberAssemblyParticipation extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['attended_at' => 'datetime'];
    }
}
