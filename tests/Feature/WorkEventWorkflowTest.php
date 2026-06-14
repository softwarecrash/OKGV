<?php

namespace Tests\Feature;

use App\Enums\BillingPeriodStatus;
use App\Enums\BillingRateScope;
use App\Enums\BillingRateType;
use App\Enums\UserRole;
use App\Enums\WorkEventParticipantStatus;
use App\Enums\WorkEventStatus;
use App\Models\BillingPeriod;
use App\Models\BillingRate;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use App\Models\WorkEvent;
use App\Models\WorkEventParticipant;
use App\Models\WorkHour;
use App\Services\ActionIndicatorService;
use App\Services\BillingCalculator;
use App\Services\BillingPeriodManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class WorkEventWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_participation_is_added_to_manual_hours(): void
    {
        [$administrator, $period, $member, $parcel] = $this->scenario();
        $workHour = WorkHour::factory()->create([
            'billing_period_id' => $period->id,
            'parcel_id' => $parcel->id,
            'hours_required' => '10.00',
            'manual_hours_done' => '2.00',
            'event_hours_done' => '0.00',
            'hours_done' => '2.00',
            'hours_missing' => '8.00',
            'penalty_rate' => '20.00',
            'penalty_amount' => '160.00',
        ]);
        $event = $this->completedEvent($period, $administrator);

        $this->actingAs($administrator)
            ->post(route('work-events.participants.store', $event), [
                'member_id' => $member->id,
                'parcel_id' => $parcel->id,
                'status' => WorkEventParticipantStatus::Confirmed->value,
                'hours' => '3.50',
            ])
            ->assertRedirect();

        $workHour->refresh();
        $this->assertSame('2.00', $workHour->manual_hours_done);
        $this->assertSame('3.50', $workHour->event_hours_done);
        $this->assertSame('5.50', $workHour->hours_done);
        $this->assertSame('4.50', $workHour->hours_missing);
        $this->assertSame('90.00', $workHour->penalty_amount);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'work_event_participant.created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'work_hours.event_hours_synchronized',
            'subject_id' => $workHour->id,
        ]);
    }

    public function test_changing_participation_to_absent_removes_transferred_hours(): void
    {
        [$administrator, $period, $member, $parcel] = $this->scenario();
        $workHour = WorkHour::factory()->create([
            'billing_period_id' => $period->id,
            'parcel_id' => $parcel->id,
            'hours_required' => '10.00',
            'manual_hours_done' => '1.00',
            'event_hours_done' => '0.00',
            'hours_done' => '1.00',
            'hours_missing' => '9.00',
            'penalty_rate' => '10.00',
            'penalty_amount' => '90.00',
        ]);
        $event = $this->completedEvent($period, $administrator);
        $participant = WorkEventParticipant::factory()->create([
            'work_event_id' => $event->id,
            'member_id' => $member->id,
            'parcel_id' => $parcel->id,
            'status' => WorkEventParticipantStatus::Confirmed,
            'hours' => '4.00',
        ]);

        $this->actingAs($administrator)
            ->put(route('work-event-participants.update', $participant), [
                'member_id' => $member->id,
                'parcel_id' => $parcel->id,
                'status' => WorkEventParticipantStatus::Absent->value,
                'hours' => '4.00',
            ])
            ->assertRedirect();

        $this->assertSame('0.00', $participant->fresh()->hours);
        $this->assertSame('0.00', $workHour->fresh()->event_hours_done);
        $this->assertSame('1.00', $workHour->fresh()->hours_done);
    }

    public function test_participation_creates_missing_work_hour_account(): void
    {
        [$administrator, $period, $member, $parcel] = $this->scenario();
        $event = $this->completedEvent($period, $administrator);

        $this->actingAs($administrator)
            ->post(route('work-events.participants.store', $event), [
                'member_id' => $member->id,
                'parcel_id' => $parcel->id,
                'status' => WorkEventParticipantStatus::Confirmed->value,
                'hours' => '2.25',
            ])
            ->assertRedirect();

        $workHour = WorkHour::query()->firstOrFail();
        $this->assertSame('0.00', $workHour->manual_hours_done);
        $this->assertSame('2.25', $workHour->event_hours_done);
        $this->assertSame('2.25', $workHour->hours_done);
        $this->assertSame('0.00', $workHour->penalty_amount);
    }

    public function test_cancelling_event_removes_hours_and_discards_calculated_drafts(): void
    {
        [$administrator, $period, $member, $parcel] = $this->scenario(withBilling: true);
        $workHour = WorkHour::factory()->create([
            'billing_period_id' => $period->id,
            'parcel_id' => $parcel->id,
            'hours_required' => '10.00',
            'manual_hours_done' => '0.00',
            'event_hours_done' => '4.00',
            'hours_done' => '4.00',
            'hours_missing' => '6.00',
            'penalty_rate' => '10.00',
            'penalty_amount' => '60.00',
        ]);
        $event = $this->completedEvent($period, $administrator);
        WorkEventParticipant::factory()->create([
            'work_event_id' => $event->id,
            'member_id' => $member->id,
            'parcel_id' => $parcel->id,
            'status' => WorkEventParticipantStatus::Confirmed,
            'hours' => '4.00',
        ]);
        app(BillingCalculator::class)->calculate($period, $administrator);

        $this->actingAs($administrator)
            ->put(route('work-events.update', $event), [
                'title' => $event->title,
                'starts_at' => $event->starts_at->format('Y-m-d H:i:s'),
                'ends_at' => $event->ends_at->format('Y-m-d H:i:s'),
                'status' => WorkEventStatus::Cancelled->value,
            ])
            ->assertRedirect(route('work-events.show', $event));

        $this->assertSame(BillingPeriodStatus::Draft, $period->fresh()->status);
        $this->assertDatabaseCount('invoices', 0);
        $this->assertSame('0.00', $workHour->fresh()->event_hours_done);
        $this->assertSame('10.00', $workHour->fresh()->hours_missing);
    }

    public function test_unfinished_event_cannot_confirm_participation(): void
    {
        [$administrator, $period, $member, $parcel] = $this->scenario();
        $event = WorkEvent::factory()->create([
            'billing_period_id' => $period->id,
            'created_by' => $administrator->id,
            'status' => WorkEventStatus::Planned,
        ]);

        $this->actingAs($administrator)
            ->post(route('work-events.participants.store', $event), [
                'member_id' => $member->id,
                'parcel_id' => $parcel->id,
                'status' => WorkEventParticipantStatus::Confirmed->value,
                'hours' => '3.00',
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('work_event_participants', 0);
    }

    public function test_garden_manager_can_manage_events_without_billing_access(): void
    {
        $gardenManager = User::factory()->create([
            'role' => UserRole::GardenManager,
        ]);
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
        ]);

        $this->actingAs($gardenManager)
            ->get(route('work-events.index'))
            ->assertOk();
        $this->actingAs($gardenManager)
            ->get(route('billing-periods.index'))
            ->assertForbidden();
        $this->actingAs($gardenManager)
            ->get(route('billing-periods.work-events.create', $period))
            ->assertOk();
    }

    public function test_overdue_planned_event_has_action_indicator(): void
    {
        $gardenManager = User::factory()->create([
            'role' => UserRole::GardenManager,
        ]);
        WorkEvent::factory()->create([
            'starts_at' => now()->subDay()->subHours(3),
            'ends_at' => now()->subDay(),
            'status' => WorkEventStatus::Planned,
        ]);

        $indicators = app(ActionIndicatorService::class)->forUser($gardenManager);

        $this->assertSame(1, $indicators['work_events']);
        $this->assertSame(1, $indicators['finance_group']);
    }

    public function test_approved_event_and_participants_are_immutable(): void
    {
        [$administrator, $period, $member, $parcel] = $this->scenario(withBilling: true);
        $event = $this->completedEvent($period, $administrator);
        WorkEventParticipant::factory()->create([
            'work_event_id' => $event->id,
            'member_id' => $member->id,
            'parcel_id' => $parcel->id,
        ]);
        app(BillingCalculator::class)->calculate($period, $administrator);
        app(BillingPeriodManager::class)->approve($period->fresh(), $administrator);

        $this->expectException(LogicException::class);
        $event->update(['title' => 'Nachträglich geändert']);
    }

    public function test_approved_participation_is_immutable_at_model_level(): void
    {
        [$administrator, $period, $member, $parcel] = $this->scenario(withBilling: true);
        $event = $this->completedEvent($period, $administrator);
        $participant = WorkEventParticipant::factory()->create([
            'work_event_id' => $event->id,
            'member_id' => $member->id,
            'parcel_id' => $parcel->id,
        ]);
        app(BillingCalculator::class)->calculate($period, $administrator);
        app(BillingPeriodManager::class)->approve($period->fresh(), $administrator);

        $this->expectException(LogicException::class);
        $participant->update(['hours' => '2.00']);
    }

    /**
     * @return array{User, BillingPeriod, Member, Parcel}
     */
    private function scenario(bool $withBilling = false): array
    {
        $administrator = User::factory()->administrator()->create();
        $period = BillingPeriod::factory()->create([
            'name' => 'Abrechnung 2025',
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
            'due_at' => '2026-02-01',
        ]);
        $member = Member::factory()->create();
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => '2020-01-01',
            'ends_at' => null,
            'is_primary' => true,
        ]);

        if ($withBilling) {
            BillingRate::factory()->create([
                'billing_period_id' => $period->id,
                'code' => 'MEMBER_FEE',
                'calculation_type' => BillingRateType::Fixed,
                'scope' => BillingRateScope::Member,
                'amount' => '1.0000',
            ]);
        }

        return [$administrator, $period, $member, $parcel];
    }

    private function completedEvent(BillingPeriod $period, User $creator): WorkEvent
    {
        return WorkEvent::factory()->create([
            'billing_period_id' => $period->id,
            'created_by' => $creator->id,
            'starts_at' => '2025-05-10 09:00:00',
            'ends_at' => '2025-05-10 13:00:00',
            'status' => WorkEventStatus::Completed,
        ]);
    }
}
