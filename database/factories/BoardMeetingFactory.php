<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BoardMeetingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'Vorstandssitzung',
            'scheduled_at' => now()->subDay(),
            'location' => 'Vereinshaus',
            'attendees' => 'Sitzungsleitung und Vorstand',
            'minutes' => 'Die Beschlussfähigkeit wurde festgestellt.',
        ];
    }
}
