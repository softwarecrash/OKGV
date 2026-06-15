<?php

namespace Tests\Feature;

use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSource;
use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\TenantTransition;
use App\Models\User;
use App\Services\PrivacyDataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class TenantTransitionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_transition_preserves_history_readings_documents_and_claims(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();
        [$parcel, $outgoingPrimary, $outgoingCoTenant, $meter] = $this->occupiedParcel();
        $incomingPrimary = Member::factory()->create();
        $incomingCoTenant = Member::factory()->create();
        $invoice = Invoice::factory()->create([
            'billing_period_id' => BillingPeriod::factory(),
            'member_id' => $outgoingPrimary->member_id,
            'status' => InvoiceStatus::Approved,
            'payment_status' => InvoicePaymentStatus::Open,
            'total_amount' => '125.50',
            'due_at' => today()->subWeek(),
            'approved_at' => now(),
            'approved_by' => $administrator->id,
        ]);

        $this->actingAs($administrator)
            ->get(route('tenant-transitions.create', ['parcel_id' => $parcel->id]))
            ->assertOk()
            ->assertSee('Pächterwechsel durchführen')
            ->assertSee($outgoingPrimary->member->full_name)
            ->assertSee($meter->meter_number);

        $response = $this->actingAs($administrator)
            ->post(route('tenant-transitions.store'), [
                'parcel_id' => $parcel->id,
                'transfer_date' => today()->toDateString(),
                'incoming_primary_member_id' => $incomingPrimary->id,
                'incoming_co_member_ids' => [$incomingCoTenant->id],
                'meter_readings' => [$meter->id => '150.2500'],
                'photos' => [
                    UploadedFile::fake()->image('garten.jpg'),
                ],
                'documents' => [
                    UploadedFile::fake()->createWithContent(
                        'uebergabe.pdf',
                        "%PDF-1.4\nÜbergabe",
                    ),
                ],
                'notes' => 'Schlüssel wurden vollständig übergeben.',
                'confirm_open_claims' => '1',
            ]);

        $transition = TenantTransition::query()->firstOrFail();
        $response->assertRedirect(route('tenant-transitions.show', $transition));

        $this->assertSame(
            today()->subDay()->toDateString(),
            $outgoingPrimary->fresh()->ends_at->toDateString(),
        );
        $this->assertSame(
            today()->subDay()->toDateString(),
            $outgoingCoTenant->fresh()->ends_at->toDateString(),
        );
        $newPrimaryTenancy = ParcelTenant::query()
            ->where('parcel_id', $parcel->id)
            ->where('member_id', $incomingPrimary->id)
            ->firstOrFail();
        $newCoTenancy = ParcelTenant::query()
            ->where('parcel_id', $parcel->id)
            ->where('member_id', $incomingCoTenant->id)
            ->firstOrFail();
        $this->assertSame(today()->toDateString(), $newPrimaryTenancy->starts_at->toDateString());
        $this->assertTrue($newPrimaryTenancy->is_primary);
        $this->assertSame(today()->toDateString(), $newCoTenancy->starts_at->toDateString());
        $this->assertFalse($newCoTenancy->is_primary);
        $handoverReading = MeterReading::query()
            ->where('meter_id', $meter->id)
            ->where('source', MeterReadingSource::Board)
            ->firstOrFail();
        $this->assertSame(today()->toDateString(), $handoverReading->reading_date->toDateString());
        $this->assertSame('150.2500', $handoverReading->reading_value);
        $this->assertSame($invoice->id, $transition->open_claims_snapshot[0]['invoice_id']);
        $this->assertSame('125.50', $transition->open_claims_snapshot[0]['total_amount']);
        $this->assertDatabaseCount('tenant_transition_documents', 2);
        $this->assertDatabaseCount('documents', 2);
        foreach ($transition->documents as $document) {
            Storage::disk('local')->assertExists($document->file_path);
        }
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tenant_transition.completed',
            'subject_id' => $transition->id,
        ]);
        $this->assertSame(InvoicePaymentStatus::Open, $invoice->fresh()->payment_status);
        $privacyExport = app(PrivacyDataExportService::class)->build($outgoingPrimary->member);
        $this->assertSame($transition->id, $privacyExport['tenant_transitions'][0]['id']);

        $this->actingAs($administrator)
            ->get(route('tenant-transitions.show', $transition))
            ->assertOk()
            ->assertSee('Schlüssel wurden vollständig übergeben.')
            ->assertSee('125,50 €');

        $document = $transition->documents->first();
        $this->actingAs($administrator)
            ->get(route('tenant-transitions.documents.download', [$transition, $document]))
            ->assertOk();
        $this->actingAs(User::factory()->create(['role' => UserRole::Tenant]))
            ->get(route('tenant-transitions.documents.download', [$transition, $document]))
            ->assertForbidden();
    }

    public function test_invalid_handover_reading_rolls_back_every_change_and_file(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();
        [$parcel, $outgoingPrimary, $outgoingCoTenant, $meter] = $this->occupiedParcel();
        $incomingPrimary = Member::factory()->create();
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => today()->subWeek(),
            'reading_value' => '140.0000',
        ]);

        $this->actingAs($administrator)
            ->from(route('tenant-transitions.create'))
            ->post(route('tenant-transitions.store'), [
                'parcel_id' => $parcel->id,
                'transfer_date' => today()->toDateString(),
                'incoming_primary_member_id' => $incomingPrimary->id,
                'meter_readings' => [$meter->id => '130.0000'],
                'photos' => [UploadedFile::fake()->image('nicht-speichern.jpg')],
                'confirm_open_claims' => '1',
            ])
            ->assertRedirect(route('tenant-transitions.create'))
            ->assertSessionHasErrors("meter_readings.{$meter->id}");

        $this->assertNull($outgoingPrimary->fresh()->ends_at);
        $this->assertNull($outgoingCoTenant->fresh()->ends_at);
        $this->assertDatabaseMissing('parcel_tenants', [
            'member_id' => $incomingPrimary->id,
            'parcel_id' => $parcel->id,
        ]);
        $this->assertDatabaseCount('tenant_transitions', 0);
        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('tenant-transitions'));
    }

    public function test_transition_requires_master_data_management_permission(): void
    {
        $withoutPermission = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ViewAllMasterData->value],
        ]);
        $withPermission = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [
                UserPermission::ViewAllMasterData->value,
                UserPermission::ManageMasterData->value,
            ],
        ]);

        $this->actingAs($withoutPermission)
            ->get(route('tenant-transitions.create'))
            ->assertForbidden();
        $this->actingAs($withPermission)
            ->get(route('tenant-transitions.create'))
            ->assertOk()
            ->assertSee('Pächterwechsel durchführen');
    }

    public function test_future_transition_cannot_be_completed_in_advance(): void
    {
        $administrator = User::factory()->administrator()->create();
        [$parcel, , , $meter] = $this->occupiedParcel();

        $this->actingAs($administrator)
            ->post(route('tenant-transitions.store'), [
                'parcel_id' => $parcel->id,
                'transfer_date' => today()->addDay()->toDateString(),
                'incoming_primary_member_id' => Member::factory()->create()->id,
                'meter_readings' => [$meter->id => '150.0000'],
                'confirm_open_claims' => '1',
            ])
            ->assertSessionHasErrors('transfer_date');

        $this->assertDatabaseCount('tenant_transitions', 0);
    }

    public function test_completed_transition_is_immutable(): void
    {
        [$parcel, $outgoingPrimary] = $this->occupiedParcel();
        $incomingPrimary = ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'starts_at' => today(),
            'is_primary' => false,
        ]);
        $transition = TenantTransition::create([
            'parcel_id' => $parcel->id,
            'outgoing_primary_tenancy_id' => $outgoingPrimary->id,
            'incoming_primary_tenancy_id' => $incomingPrimary->id,
            'transfer_date' => today(),
            'outgoing_members_snapshot' => [],
            'incoming_members_snapshot' => [],
            'completed_by' => User::factory()->administrator()->create()->id,
            'completed_at' => now(),
        ]);

        $this->expectException(LogicException::class);
        $transition->update(['notes' => 'Nachträgliche Änderung']);
    }

    /**
     * @return array{Parcel, ParcelTenant, ParcelTenant, Meter}
     */
    private function occupiedParcel(): array
    {
        $parcel = Parcel::factory()->create();
        $outgoingPrimary = ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'starts_at' => today()->subYears(2),
            'ends_at' => null,
            'is_primary' => true,
        ]);
        $outgoingCoTenant = ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'starts_at' => today()->subYear(),
            'ends_at' => null,
            'is_primary' => false,
        ]);
        $meter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'type' => MeterType::Water,
            'installed_at' => today()->subYears(2),
            'removed_at' => null,
            'start_reading' => '100.0000',
            'status' => MeterStatus::Active,
        ]);

        return [$parcel, $outgoingPrimary, $outgoingCoTenant, $meter];
    }
}
