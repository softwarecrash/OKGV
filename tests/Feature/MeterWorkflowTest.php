<?php

namespace Tests\Feature;

use App\Enums\MeterReadingSource;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Parcel;
use App\Models\User;
use App\Services\ConsumptionCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeterWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_meter_creation_prevents_second_active_meter_of_same_type(): void
    {
        $administrator = User::factory()->administrator()->create();
        $parcel = Parcel::factory()->create();
        Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'type' => MeterType::Water,
            'status' => MeterStatus::Active,
        ]);

        $this->actingAs($administrator)->post(route('meters.store'), [
            'parcel_id' => $parcel->id,
            'type' => MeterType::Water->value,
            'meter_number' => 'W-NEW',
            'installed_at' => '2026-01-01',
            'start_reading' => '0',
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('meters', ['meter_number' => 'W-NEW']);
    }

    public function test_readings_are_append_only_and_must_be_monotonic(): void
    {
        $administrator = User::factory()->administrator()->create();
        $meter = Meter::factory()->create([
            'installed_at' => '2025-01-01',
            'start_reading' => '100.0000',
        ]);

        $this->actingAs($administrator)->post(route('meter-readings.store'), [
            'meter_id' => $meter->id,
            'reading_value' => '120.0000',
            'reading_date' => '2025-06-01',
            'source' => MeterReadingSource::Board->value,
        ])->assertRedirect(route('meters.show', $meter));

        $this->actingAs($administrator)->post(route('meter-readings.store'), [
            'meter_id' => $meter->id,
            'reading_value' => '110.0000',
            'reading_date' => '2025-07-01',
            'source' => MeterReadingSource::Board->value,
        ])->assertSessionHasErrors('reading_value');

        $reading = MeterReading::query()->firstOrFail();
        $this->put('/meter-readings/'.$reading->id, [])->assertNotFound();
        $this->delete('/meter-readings/'.$reading->id)->assertNotFound();
        $this->assertDatabaseCount('meter_readings', 1);
    }

    public function test_meter_replacement_closes_old_meter_and_creates_new_meter(): void
    {
        $administrator = User::factory()->administrator()->create();
        $oldMeter = Meter::factory()->create([
            'type' => MeterType::Electricity,
            'meter_number' => 'E-OLD',
            'installed_at' => '2020-01-01',
            'start_reading' => '10.0000',
            'status' => MeterStatus::Active,
        ]);

        $this->actingAs($administrator)->post(route('meters.replace.store', $oldMeter), [
            'replaced_at' => '2026-02-15',
            'end_reading' => '500.0000',
            'meter_number' => 'E-NEW',
            'start_reading' => '0.0000',
        ])->assertRedirect();

        $oldMeter->refresh();
        $newMeter = Meter::query()->where('meter_number', 'E-NEW')->firstOrFail();

        $this->assertSame(MeterStatus::Replaced, $oldMeter->status);
        $this->assertSame('2026-02-15', $oldMeter->removed_at->toDateString());
        $this->assertSame($oldMeter->parcel_id, $newMeter->parcel_id);
        $this->assertSame($oldMeter->type, $newMeter->type);
        $this->assertSame(MeterStatus::Active, $newMeter->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'meter.replaced',
            'subject_id' => $oldMeter->id,
        ]);
    }

    public function test_replacement_cannot_cut_off_a_later_reading(): void
    {
        $administrator = User::factory()->administrator()->create();
        $meter = Meter::factory()->create([
            'installed_at' => '2025-01-01',
            'start_reading' => '0.0000',
            'status' => MeterStatus::Active,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => '2025-08-01',
            'reading_value' => '80.0000',
        ]);

        $this->actingAs($administrator)->post(route('meters.replace.store', $meter), [
            'replaced_at' => '2025-07-01',
            'end_reading' => '70.0000',
            'meter_number' => 'REPLACEMENT',
            'start_reading' => '0.0000',
        ])->assertSessionHasErrors('replaced_at');

        $this->assertSame(MeterStatus::Active, $meter->fresh()->status);
        $this->assertDatabaseMissing('meters', ['meter_number' => 'REPLACEMENT']);
    }

    public function test_consumption_is_summed_across_replaced_meters(): void
    {
        $parcel = Parcel::factory()->create();
        $oldMeter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'type' => MeterType::Water,
            'installed_at' => '2025-01-01',
            'removed_at' => '2025-06-30',
            'start_reading' => '10.0000',
            'end_reading' => '40.0000',
            'status' => MeterStatus::Replaced,
        ]);
        $newMeter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'type' => MeterType::Water,
            'installed_at' => '2025-06-30',
            'start_reading' => '5.0000',
            'status' => MeterStatus::Active,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $newMeter->id,
            'reading_date' => '2025-12-31',
            'reading_value' => '25.0000',
        ]);

        $consumption = app(ConsumptionCalculator::class)->forParcel(
            $parcel->id,
            MeterType::Water->value,
            CarbonImmutable::parse('2025-01-01'),
            CarbonImmutable::parse('2025-12-31'),
        );

        $this->assertSame('50.0000', $consumption);
        $this->assertDatabaseHas('meters', ['id' => $oldMeter->id]);
    }
}
