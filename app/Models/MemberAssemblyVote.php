<?php

namespace App\Models;

use App\Enums\MemberAssemblyVoteChoice;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['member_id', 'user_id', 'choice', 'voted_at', 'recorded_by'])]
class MemberAssemblyVote extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['choice' => MemberAssemblyVoteChoice::class, 'voted_at' => 'datetime'];
    }
}
