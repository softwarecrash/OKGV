<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\WorkHourSubmissionStatus;
use App\Models\ApplicationSetting;
use App\Models\BillingPeriod;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use App\Models\WorkHour;
use App\Models\WorkHourSubmission;
use App\Services\ActionIndicatorService;
use App\Services\WorkHourManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class WorkHourSubmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_tenant_is_redirected_from_tenant_submission_form(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('work-hour-submissions.create'))
            ->assertRedirect(route('work-hours.index'))
            ->assertSessionHasErrors('work_hours');
    }

    public function test_tenant_without_assigned_parcel_sees_an_explanation(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        Member::factory()->create(['user_id' => $tenant->id]);

        $this->actingAs($tenant)
            ->get(route('work-hour-submissions.create'))
            ->assertOk()
            ->assertSee('aktuell keine Parzelle zugeordnet');
    }

    public function test_period_accounts_are_created_automatically_from_global_defaults(): void
    {
        $administrator = User::factory()->administrator()->create();
        ApplicationSetting::current()->update([
            'default_work_hours_required' => '12.00',
            'default_work_hour_penalty_rate' => '18.50',
        ]);
        $firstParcel = $this->leasedParcel();
        $secondParcel = $this->leasedParcel();

        $this->actingAs($administrator)
            ->post(route('billing-periods.store'), [
                'name' => 'Abrechnung 2025',
                'starts_at' => '2025-01-01',
                'ends_at' => '2025-12-31',
                'due_at' => '2026-02-01',
            ])
            ->assertRedirect();

        $period = BillingPeriod::query()->where('name', 'Abrechnung 2025')->firstOrFail();
        $this->assertDatabaseCount('work_hours', 2);
        foreach ([$firstParcel, $secondParcel] as $parcel) {
            $this->assertDatabaseHas('work_hours', [
                'billing_period_id' => $period->id,
                'parcel_id' => $parcel->id,
                'base_hours_required' => 12,
                'hours_required' => 12,
                'occupancy_factor' => 1,
                'hours_required_overridden' => false,
                'penalty_rate' => 18.5,
            ]);
        }

        $this->actingAs($administrator)
            ->get(route('billing-periods.show', $period))
            ->assertOk()
            ->assertDontSee('Parzellenkonten vorbereiten')
            ->assertSee('Konten werden für im Zeitraum verpachtete Parzellen automatisch');
    }

    public function test_required_hours_are_prorated_for_midyear_occupancy(): void
    {
        $administrator = User::factory()->administrator()->create();
        ApplicationSetting::current()->update([
            'default_work_hours_required' => '10.00',
        ]);
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
        ]);
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => Member::factory(),
            'starts_at' => '2025-07-01',
            'ends_at' => '2025-12-31',
            'is_primary' => true,
        ]);

        app(WorkHourManager::class)->initializePeriod($period, $administrator);

        $workHour = WorkHour::query()->firstOrFail();
        $this->assertSame('10.00', $workHour->base_hours_required);
        $this->assertSame('0.50410958', $workHour->occupancy_factor);
        $this->assertSame('5.04', $workHour->hours_required);
        $this->assertFalse($workHour->hours_required_overridden);
    }

    public function test_continuous_tenant_change_keeps_the_full_parcel_obligation(): void
    {
        $administrator = User::factory()->administrator()->create();
        ApplicationSetting::current()->update([
            'default_work_hours_required' => '10.00',
        ]);
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
        ]);
        $parcel = Parcel::factory()->create();

        foreach ([
            ['2025-01-01', '2025-06-30'],
            ['2025-07-01', '2025-12-31'],
        ] as [$startsAt, $endsAt]) {
            ParcelTenant::factory()->create([
                'parcel_id' => $parcel->id,
                'member_id' => Member::factory(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'is_primary' => true,
            ]);
        }

        app(WorkHourManager::class)->initializePeriod($period, $administrator);

        $workHour = WorkHour::query()->firstOrFail();
        $this->assertSame('1.00000000', $workHour->occupancy_factor);
        $this->assertSame('10.00', $workHour->hours_required);
    }

    public function test_manual_required_hours_remain_unchanged_when_occupancy_changes(): void
    {
        $administrator = User::factory()->administrator()->create();
        ApplicationSetting::current()->update([
            'default_work_hours_required' => '10.00',
        ]);
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
        ]);
        $parcel = Parcel::factory()->create();
        $tenancy = ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => Member::factory(),
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
            'is_primary' => true,
        ]);
        $manager = app(WorkHourManager::class);
        $manager->initializePeriod($period, $administrator);
        $workHour = WorkHour::query()->firstOrFail();
        $manager->save($period, [
            'parcel_id' => $parcel->id,
            'hours_required' => '8.00',
            'hours_done' => '0.00',
            'penalty_rate' => '20.00',
        ], $administrator, $workHour);

        $tenancy->update(['starts_at' => '2025-07-01']);
        $manager->synchronizeTenancy($tenancy, $administrator);

        $workHour = $workHour->fresh();
        $this->assertSame('0.50410958', $workHour->occupancy_factor);
        $this->assertSame('8.00', $workHour->hours_required);
        $this->assertTrue($workHour->hours_required_overridden);
    }

    public function test_tenant_submission_with_private_photo_is_approved_for_parcel_account(): void
    {
        Storage::fake('local');
        [$tenant, $parcel, $period] = $this->tenantScenario();
        $reviewer = User::factory()->create(['role' => UserRole::GardenManager]);
        WorkHour::factory()->create([
            'billing_period_id' => $period->id,
            'parcel_id' => $parcel->id,
            'hours_required' => '10.00',
            'manual_hours_done' => '1.00',
            'hours_done' => '1.00',
            'hours_missing' => '9.00',
            'penalty_rate' => '20.00',
            'penalty_amount' => '180.00',
        ]);

        $this->actingAs($tenant)
            ->post(route('work-hour-submissions.store'), [
                'parcel_id' => $parcel->id,
                'worked_at' => '2025-06-10',
                'hours' => '3.50',
                'description' => 'Gemeinschaftsweg gereinigt.',
                'photo' => UploadedFile::fake()->image('nachweis.jpg'),
            ])
            ->assertRedirect(route('work-hour-submissions.index'));

        $submission = WorkHourSubmission::query()->firstOrFail();
        Storage::disk('local')->assertExists($submission->photo_path);
        $otherTenant = User::factory()->create(['role' => UserRole::Tenant]);

        $this->actingAs($tenant)
            ->get(route('work-hour-submissions.photo', $submission))
            ->assertOk();
        $this->actingAs($otherTenant)
            ->get(route('work-hour-submissions.photo', $submission))
            ->assertForbidden();
        $this->actingAs($reviewer)
            ->get(route('work-hour-submissions.photo', $submission))
            ->assertOk();

        $this->actingAs($reviewer)
            ->post(route('work-hour-submissions.approve', $submission), [
                'review_note' => 'Nachweis geprüft.',
            ])
            ->assertRedirect();

        $this->assertSame(WorkHourSubmissionStatus::Approved, $submission->fresh()->status);
        $workHour = WorkHour::query()->firstOrFail();
        $this->assertSame('3.50', $workHour->submission_hours_done);
        $this->assertSame('4.50', $workHour->hours_done);
        $this->assertSame('5.50', $workHour->hours_missing);
        $this->assertSame('110.00', $workHour->penalty_amount);
    }

    public function test_tenant_can_enter_whole_and_comma_decimal_hours_in_quarter_hour_steps(): void
    {
        [$tenant, $parcel] = $this->tenantScenario();

        $this->actingAs($tenant)
            ->get(route('work-hour-submissions.create'))
            ->assertOk()
            ->assertSee('min="0.25"', false)
            ->assertSee('step="0.25"', false)
            ->assertSee('zum Beispiel 1, 1,5 oder 2,25 Stunden');

        foreach (['1', '1,5'] as $index => $hours) {
            $this->actingAs($tenant)
                ->post(route('work-hour-submissions.store'), [
                    'parcel_id' => $parcel->id,
                    'worked_at' => '2025-06-'.($index + 10),
                    'hours' => $hours,
                    'description' => 'Gemeinschaftsfläche gepflegt.',
                ])
                ->assertRedirect(route('work-hour-submissions.index'))
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['1.00', '1.50'],
            WorkHourSubmission::query()->orderBy('id')->pluck('hours')->all(),
        );
    }

    public function test_tenant_receives_a_clear_message_for_non_quarter_hour_values(): void
    {
        [$tenant, $parcel] = $this->tenantScenario();

        $this->actingAs($tenant)
            ->from(route('work-hour-submissions.create'))
            ->post(route('work-hour-submissions.store'), [
                'parcel_id' => $parcel->id,
                'worked_at' => '2025-06-10',
                'hours' => '1.10',
                'description' => 'Gemeinschaftsfläche gepflegt.',
            ])
            ->assertRedirect(route('work-hour-submissions.create'))
            ->assertSessionHasErrors([
                'hours' => 'Bitte gib die Arbeitszeit in Viertelstunden ein, zum Beispiel 1, 1,5 oder 2,25 Stunden.',
            ]);
    }

    public function test_co_tenants_report_into_the_same_parcel_account(): void
    {
        [$firstTenant, $parcel, $period] = $this->tenantScenario();
        $secondUser = User::factory()->create();
        $secondMember = Member::factory()->create(['user_id' => $secondUser->id]);
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $secondMember->id,
            'starts_at' => '2020-01-01',
            'is_primary' => false,
        ]);
        $reviewer = User::factory()->create(['role' => UserRole::GardenManager]);

        foreach ([[$firstTenant, '2.00'], [$secondUser, '3.00']] as [$tenant, $hours]) {
            $this->actingAs($tenant)->post(route('work-hour-submissions.store'), [
                'parcel_id' => $parcel->id,
                'worked_at' => '2025-07-01',
                'hours' => $hours,
                'description' => 'Arbeit an der Gemeinschaftsanlage.',
            ])->assertRedirect();
        }

        foreach (WorkHourSubmission::all() as $submission) {
            $this->actingAs($reviewer)
                ->post(route('work-hour-submissions.approve', $submission))
                ->assertRedirect();
        }

        $this->assertDatabaseCount('work_hours', 1);
        $this->assertSame('5.00', WorkHour::query()->firstOrFail()->submission_hours_done);
    }

    public function test_rejected_submission_does_not_count_and_is_visible_to_tenant(): void
    {
        [$tenant, $parcel] = $this->tenantScenario();
        $reviewer = User::factory()->create(['role' => UserRole::GardenManager]);
        $this->actingAs($tenant)->post(route('work-hour-submissions.store'), [
            'parcel_id' => $parcel->id,
            'worked_at' => '2025-05-01',
            'hours' => '2.00',
            'description' => 'Nicht ausreichend belegte Tätigkeit.',
        ]);
        $submission = WorkHourSubmission::query()->firstOrFail();

        $this->actingAs($reviewer)
            ->post(route('work-hour-submissions.reject', $submission), [
                'review_note' => 'Tätigkeit nicht nachvollziehbar.',
            ])
            ->assertRedirect();

        $this->assertSame(WorkHourSubmissionStatus::Rejected, $submission->fresh()->status);
        $this->assertSame('0.00', WorkHour::query()->firstOrFail()->submission_hours_done);
        $this->actingAs($tenant)
            ->get(route('work-hour-submissions.index'))
            ->assertOk()
            ->assertSee('Ablehnungsgrund:')
            ->assertSee('Tätigkeit nicht nachvollziehbar.')
            ->assertSee('Erneute Meldung erforderlich')
            ->assertSee('Korrigierte Meldung einreichen');
        $this->actingAs($tenant)
            ->get(route('tenant-portal.index'))
            ->assertOk()
            ->assertSee('Ablehnungsgrund:')
            ->assertSee('Tätigkeit nicht nachvollziehbar.');

        $this->assertSame(
            1,
            app(ActionIndicatorService::class)->forUser($tenant)['work_hour_submissions'],
        );

        $this->actingAs($tenant)->post(route('work-hour-submissions.store'), [
            'parcel_id' => $parcel->id,
            'worked_at' => '2025-05-02',
            'hours' => '2.00',
            'description' => 'Korrigierte und belegte Tätigkeit.',
        ])->assertRedirect();

        $this->assertSame(
            0,
            app(ActionIndicatorService::class)->forUser($tenant)['work_hour_submissions'],
        );
        $this->actingAs($tenant)
            ->get(route('work-hour-submissions.index'))
            ->assertOk()
            ->assertSee('Ablehnungsgrund:')
            ->assertSee('Tätigkeit nicht nachvollziehbar.')
            ->assertDontSee('Erneute Meldung erforderlich');
    }

    public function test_reviewed_submission_is_immutable_and_cannot_be_deleted(): void
    {
        [$tenant, $parcel, $period] = $this->tenantScenario();
        $submission = WorkHourSubmission::create([
            'billing_period_id' => $period->id,
            'parcel_id' => $parcel->id,
            'submitted_by' => $tenant->id,
            'worked_at' => '2025-05-01',
            'hours' => '2.00',
            'description' => 'Bestätigte Tätigkeit.',
            'status' => WorkHourSubmissionStatus::Approved,
        ]);

        $this->expectException(LogicException::class);
        $submission->update(['hours' => '3.00']);
    }

    /**
     * @return array{User, Parcel, BillingPeriod}
     */
    private function tenantScenario(): array
    {
        $tenant = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => '2020-01-01',
            'is_primary' => true,
        ]);
        $period = BillingPeriod::factory()->create([
            'starts_at' => '2025-01-01',
            'ends_at' => '2025-12-31',
        ]);

        return [$tenant, $parcel, $period];
    }

    private function leasedParcel(): Parcel
    {
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => Member::factory(),
            'starts_at' => '2020-01-01',
            'is_primary' => true,
        ]);

        return $parcel;
    }
}
