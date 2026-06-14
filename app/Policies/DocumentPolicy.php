<?php

namespace App\Policies;

use App\Enums\DocumentVisibility;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($user->role !== UserRole::Tenant
            || $document->visibility !== DocumentVisibility::Tenant
            || $document->published_at === null) {
            return false;
        }

        $member = $user->member;

        if (! $member) {
            return false;
        }

        if ($document->member_id === $member->id) {
            return true;
        }

        return $document->parcel_id !== null
            && $member->parcelTenancies()
                ->activeOn()
                ->where('parcel_id', $document->parcel_id)
                ->exists();
    }
}
