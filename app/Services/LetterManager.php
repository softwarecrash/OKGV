<?php

namespace App\Services;

use App\Models\Letter;
use App\Models\Member;
use App\Models\User;

final class LetterManager
{
    public function __construct(
        private readonly AssociationDocumentProfile $associationProfile,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Letter
    {
        if (! empty($data['member_id'])) {
            $member = Member::query()->findOrFail($data['member_id']);
            $data = [
                ...$data,
                'recipient_name' => $member->full_name,
                'street' => $member->street,
                'zip' => $member->zip,
                'city' => $member->city,
            ];
        }

        $letter = Letter::create([
            ...$data,
            'association_snapshot' => $this->associationProfile->snapshot(),
            'created_by' => $actor->id,
        ]);

        AuditLogger::log('letter.created', $actor, $letter, [
            'member_id' => $letter->member_id,
        ]);

        return $letter;
    }
}
