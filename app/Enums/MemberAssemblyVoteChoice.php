<?php

namespace App\Enums;

enum MemberAssemblyVoteChoice: string
{
    case Yes = 'yes';
    case No = 'no';
    case Abstain = 'abstain';

    public function label(): string
    {
        return match ($this) {
            self::Yes => 'Ja', self::No => 'Nein', self::Abstain => 'Enthaltung',
        };
    }
}
