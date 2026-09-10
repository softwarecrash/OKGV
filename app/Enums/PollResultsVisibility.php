<?php

namespace App\Enums;

enum PollResultsVisibility: string
{
    case Participants = 'participants';
    case Managers = 'managers';

    public function label(): string
    {
        return $this === self::Participants ? 'Für die Zielgruppe nach Abschluss' : 'Vertraulich: nur Umfrageverwaltung';
    }
}
