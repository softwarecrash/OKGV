<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\GardenInspection;
use App\Models\Member;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GardenInspectionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_garden_manager_records_private_finding_and_tenant_sees_only_own_parcel(): void
    {
        Storage::fake('local');
        $manager = User::factory()->create(['role' => UserRole::GardenManager]);
        $tenant = User::factory()->create();
        $member = Member::factory()->create(['user_id' => $tenant->id, 'joined_at' => now()->subYear()]);
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create(['parcel_id' => $parcel->id, 'member_id' => $member->id, 'starts_at' => now()->subMonth()]);
        $this->actingAs($manager)->post(route('garden-inspections.store'), ['title' => 'Frühjahrsbegehung', 'inspected_at' => now()->toDateTimeString(), 'inspectors' => 'Gartenwart'])->assertRedirect();
        $inspection = GardenInspection::firstOrFail();
        $this->post(route('garden-inspections.findings.store', $inspection), ['parcel_id' => $parcel->id, 'category' => 'safety', 'description' => 'Bitte den Weg bis Ende des Monats freiräumen.', 'due_at' => now()->addMonth()->toDateString(), 'photo' => UploadedFile::fake()->image('weg.jpg')])->assertRedirect();
        $finding = $inspection->findings()->firstOrFail();
        Storage::disk('local')->assertExists($finding->photo_path);
        $this->actingAs($tenant)->get(route('garden-inspections.show', $inspection))->assertOk()->assertSee('freiräumen')->assertDontSee('Interne Notiz');
        $this->actingAs($manager)->post(route('garden-inspection-findings.resolve', $finding))->assertRedirect();
        $this->assertNotNull($finding->fresh()->resolved_at);
        $this->post(route('garden-inspections.finalize', $inspection))->assertRedirect();
        $this->assertNotNull($inspection->fresh()->finalized_at);
        Storage::disk('local')->assertExists($inspection->fresh()->pdf_path);
    }
}
