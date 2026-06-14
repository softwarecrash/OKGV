<?php

namespace App\Models;

use App\Enums\DocumentVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id',
    'parcel_id',
    'uploaded_by',
    'title',
    'type',
    'visibility',
    'file_path',
    'original_name',
    'mime_type',
    'file_size',
    'published_at',
])]
class Document extends Model
{
    protected function casts(): array
    {
        return [
            'visibility' => DocumentVisibility::class,
            'published_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
