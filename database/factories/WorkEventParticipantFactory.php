<?php

namespace Database\Factories;

use App\Enums\WorkEventParticipantStatus;
use App\Models\Member;
use App\Models\WorkEvent;
use App\Models\WorkEventParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkEventParticipant>
 */
class WorkEventParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'work_event_id' => WorkEvent::factory(),
            'member_id' => Member::factory(),
            'status' => WorkEventParticipantStatus::Registered,
            'hours' => '0.00',
        ];
    }
}
