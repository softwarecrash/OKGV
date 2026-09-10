<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['position', 'title', 'body'])]
class MemberAssemblyResolution extends Model
{
    public $timestamps = false;

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(MemberAssembly::class, 'member_assembly_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(MemberAssemblyVote::class);
    }
}
