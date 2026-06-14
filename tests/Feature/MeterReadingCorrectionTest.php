<?php

namespace Tests\Feature;

use App\Enums\MeterReadingSource;
use App\Enums\MeterType;
use App\Enums\UserRole;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MeterReadingCorrection;
use App\Models\Parcel;
use App\Models\User;
use App\Services\ConsumptionCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class MeterReadingCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_must_have_explicit_permission_to_correct_reading(): void
    {
        $administrator = User::factory()->administrator()->create([
            'can_correct_meter_readings' => false,
        ]);
        $reading = MeterReading::factory()->create();

        $this->actingAs($administrator)
            ->get(route('meter-reading-corrections.create', $reading))
            ->assertForbidden();

        $this->actingAs($administrator)
            ->post(route('meter-reading-corrections.store', $reading), [
                'corrected_value' => '50.0000',
                'reason' => 'Der gemeldete Wert enthielt einen Tippfehler.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('meter_reading_corrections', 0);
    }

    public function test_authorized_board_account_can_append_audited_correction(): void
    {
        $board = User::factory()->create([
            'role' => UserRole::Board,
            'can_correct_meter_readings' => true,
        ]);
        $meter = Meter::factory()->create([
            'installed_at' => '2025-01-01',
            'start_reading' => '10.0000',
        ]);
        $reading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => '2025-12-31',
            'reading_value' => '120.0000',
            'source' => MeterReadingSource::Tenant,
        ]);

        $this->actingAs($board)
            ->post(route('meter-reading-corrections.store', $reading), [
                'corrected_value' => '102.0000',
                'reason' => 'Der Pächter hat die letzten beiden Ziffern vertauscht.',
            ])
            ->assertRedirect(route('meters.show', $meter));

        $reading->refresh()->load('corrections');
        $this->assertSame('120.0000', $reading->reading_value);
        $this->assertSame('102.0000', $reading->effective_reading_value);
        $this->assertDatabaseHas('meter_reading_corrections', [
            'meter_reading_id' => $reading->id,
            'corrected_value' => 102,
            'corrected_by' => $board->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'meter_reading.corrected',
            'subject_id' => $reading->id,
        ]);
    }

    public function test_correction_changes_consumption_without_changing_original_reading(): void
    {
        $board = User::factory()->create([
            'role' => UserRole::Board,
            'can_correct_meter_readings' => true,
        ]);
        $parcel = Parcel::factory()->create();
        $meter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'type' => MeterType::Water,
            'installed_at' => '2025-01-01',
            'start_reading' => '10.0000',
        ]);
        $reading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => '2025-12-31',
            'reading_value' => '100.0000',
        ]);

        $this->actingAs($board)->post(route('meter-reading-corrections.store', $reading), [
            'corrected_value' => '80.0000',
            'reason' => 'Ablesefoto bestätigt den niedrigeren tatsächlichen Stand.',
        ])->assertRedirect();

        $consumption = app(ConsumptionCalculator::class)->forParcel(
            $parcel->id,
            MeterType::Water->value,
            CarbonImmutable::parse('2025-01-01'),
            CarbonImmutable::parse('2025-12-31'),
        );

        $this->assertSame('70.0000', $consumption);
        $this->assertSame('100.0000', $reading->fresh()->reading_value);
    }

    public function test_only_administrator_can_assign_permission_to_eligible_roles(): void
    {
        $administrator = User::factory()->administrator()->create();
        $board = User::factory()->create(['role' => UserRole::Board]);
        $otherBoard = User::factory()->create(['role' => UserRole::Board]);
        $waterManager = User::factory()->create(['role' => UserRole::WaterManager]);

        $this->actingAs($board)
            ->put(route('user-permissions.update', $otherBoard), [
                'can_correct_meter_readings' => true,
            ])
            ->assertForbidden();

        $this->actingAs($administrator)
            ->put(route('user-permissions.update', $waterManager), [
                'can_correct_meter_readings' => true,
            ])
            ->assertSessionHasErrors('can_correct_meter_readings');

        $this->actingAs($administrator)
            ->put(route('user-permissions.update', $board), [
                'can_correct_meter_readings' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($board->fresh()->canCorrectMeterReadings());
        $this->assertFalse($waterManager->fresh()->canCorrectMeterReadings());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user.meter_reading_correction_permission.updated',
            'subject_id' => $board->id,
        ]);
    }

    public function test_corrections_are_append_only(): void
    {
        $correction = MeterReadingCorrection::factory()->create();

        $this->expectException(LogicException::class);
        $correction->update(['reason' => 'Manipulierter Grund']);
    }
}
