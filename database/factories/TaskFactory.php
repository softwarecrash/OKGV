<?php

namespace Database\Factories;

use App\Enums\TaskRecurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => 'Vereinsunterlagen prüfen', 'description' => 'Unterlagen vervollständigen.', 'due_at' => today()->addWeek(), 'recurrence' => TaskRecurrence::None];
    }
}
