<?php

namespace App\Enums;

enum PollType: string
{
    case Survey = 'survey';
    case Dates = 'dates';

    public function label(): string
    {
        return $this === self::Survey ? 'Umfrage' : 'Terminabfrage';
    }
}
