<?php

namespace App\Models;

use App\Enums\NumberSequenceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'type',
    'format',
    'padding',
    'next_value',
    'reset_yearly',
    'last_year',
])]
class NumberSequence extends Model
{
    protected function casts(): array
    {
        return [
            'type' => NumberSequenceType::class,
            'padding' => 'integer',
            'next_value' => 'integer',
            'reset_yearly' => 'boolean',
            'last_year' => 'integer',
        ];
    }
}
