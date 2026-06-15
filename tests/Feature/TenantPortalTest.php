<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\DocumentVisibility;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingSubmissionStatus;
use App\Enums\MeterStatus;
use App\Enums\RegistrationRequestStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MeterReadingCorrection;
use App\Models\MeterReadingSubmission;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\RegistrationRequest;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_waits_for_board_approval(): void
    {
        $parcel = Parcel::factory()->create(['parcel_number' => 'A-17']);

        $this->post(route('tenant-registration.store'), [
            'first_name' => 'Erika',
            'last_name' => 'Mustermann',
            'email' => 'erika@example.test',
            'parcel_number' => 'A-17',
            'password' => 'SicheresPasswort123',
            'password_confirmation' => 'SicheresPasswort123',
        ])->assertRedirect(route('login'));

        $registrationRequest = RegistrationRequest::query()->firstOrFail();
        $this->assertSame(RegistrationRequestStatus::Pending, $registrationRequest->status);
        $this->assertTrue(Hash::check('SicheresPasswort123', $registrationRequest->password));
        $this->assertDatabaseCount('users', 0);

        $this->post(route('tenant-registration.store'), [
            'first_name' => 'Erika',
            'last_name' => 'Mustermann',
            'email' => 'erika@example.test',
            'parcel_number' => $parcel->parcel_number,
            'password' => 'SicheresPasswort123',
            'password_confirmation' => 'SicheresPasswort123',
        ])->assertSessionHasErrors('email');
    }

    public function test_board_can_approve_only_an_active_tenant_of_requested_parcel(): void
    {
        Notification::fake();

        $board = User::factory()->create(['role' => UserRole::Board]);
        $waterManager = User::factory()->create(['role' => UserRole::WaterManager]);
        $parcel = Parcel::factory()->create(['parcel_number' => 'B-08']);
        $member = Member::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => now()->subYear(),
        ]);
        $registrationRequest = RegistrationRequest::factory()->create([
            'parcel_id' => $parcel->id,
            'parcel_number' => $parcel->parcel_number,
            'email' => 'tenant@example.test',
            'password' => 'SicheresPasswort123',
        ]);

        $this->actingAs($waterManager)
            ->get(route('registration-requests.index'))
            ->assertForbidden();

        $this->actingAs($board)
            ->post(route('registration-requests.approve', $registrationRequest), [
                'member_id' => $member->id,
                'review_note' => 'Pachtvertrag geprüft',
            ])
            ->assertRedirect(route('registration-requests.index'));

        $user = User::query()->where('email', 'tenant@example.test')->firstOrFail();
        $this->assertSame(UserRole::Tenant, $user->role);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $this->assertSame($user->id, $member->fresh()->user_id);
        $this->assertNull($registrationRequest->fresh()->password);
        $this->assertSame(
            RegistrationRequestStatus::Approved,
            $registrationRequest->fresh()->status,
        );
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tenant.registration.approved',
            'subject_id' => $registrationRequest->id,
        ]);
    }

    public function test_approval_rejects_member_from_another_parcel(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        $requestedParcel = Parcel::factory()->create();
        $otherParcel = Parcel::factory()->create();
        $otherMember = Member::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $otherParcel->id,
            'member_id' => $otherMember->id,
        ]);
        $registrationRequest = RegistrationRequest::factory()->create([
            'parcel_id' => $requestedParcel->id,
            'parcel_number' => $requestedParcel->parcel_number,
        ]);

        $this->actingAs($board)
            ->post(route('registration-requests.approve', $registrationRequest), [
                'member_id' => $otherMember->id,
            ])
            ->assertSessionHasErrors('member_id');

        $this->assertDatabaseCount('users', 1);
        $this->assertSame(
            RegistrationRequestStatus::Pending,
            $registrationRequest->fresh()->status,
        );
    }

    public function test_board_rejection_removes_temporary_password_hash(): void
    {
        $board = User::factory()->create(['role' => UserRole::Board]);
        $registrationRequest = RegistrationRequest::factory()->create();

        $this->actingAs($board)
            ->post(route('registration-requests.reject', $registrationRequest), [
                'review_note' => 'Angaben konnten nicht bestätigt werden.',
            ])
            ->assertRedirect(route('registration-requests.index'));

        $registrationRequest->refresh();
        $this->assertSame(RegistrationRequestStatus::Rejected, $registrationRequest->status);
        $this->assertNull($registrationRequest->password);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'tenant.registration.rejected',
            'subject_id' => $registrationRequest->id,
        ]);
    }

    public function test_tenant_portal_only_shows_current_own_data_and_documents(): void
    {
        Storage::fake('local');
        [$tenant, $member, $parcel] = $this->tenantScenario();
        $administrator = User::factory()->administrator()->create();
        $foreignMember = Member::factory()->create();
        $foreignParcel = Parcel::factory()->create();
        Invoice::factory()->create([
            'member_id' => $member->id,
            'status' => InvoiceStatus::Approved,
            'invoice_number' => 'R-OWN',
        ]);
        Invoice::factory()->create([
            'member_id' => $foreignMember->id,
            'status' => InvoiceStatus::Approved,
            'invoice_number' => 'R-FOREIGN',
        ]);

        Storage::disk('local')->put('documents/own.pdf', 'own');
        Storage::disk('local')->put('documents/parcel.pdf', 'parcel');
        Storage::disk('local')->put('documents/foreign.pdf', 'foreign');
        $ownDocument = $this->document($administrator, 'Eigenes Dokument', 'documents/own.pdf', member: $member);
        $this->document($administrator, 'Parzellendokument', 'documents/parcel.pdf', parcel: $parcel);
        $foreignDocument = $this->document($administrator, 'Fremdes Dokument', 'documents/foreign.pdf', member: $foreignMember);
        $this->document(
            $administrator,
            'Interne Notiz',
            'documents/internal.pdf',
            member: $member,
            visibility: DocumentVisibility::Internal,
        );

        $this->actingAs($tenant)
            ->get(route('tenant-portal.index'))
            ->assertOk()
            ->assertSee($member->full_name)
            ->assertSee($parcel->parcel_number)
            ->assertSee('R-OWN')
            ->assertDontSee('R-FOREIGN')
            ->assertSee('Eigenes Dokument')
            ->assertDontSee('Fremdes Dokument')
            ->assertDontSee('Interne Notiz');

        $this->actingAs($tenant)
            ->get(route('tenant-portal.documents.download', $ownDocument))
            ->assertOk();
        $this->actingAs($tenant)
            ->get(route('tenant-portal.documents.download', $foreignDocument))
            ->assertForbidden();
    }

    public function test_former_tenant_cannot_access_parcel_meter(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => now()->subYears(2),
            'ends_at' => now()->subDay(),
        ]);
        $meter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'status' => MeterStatus::Active,
        ]);

        $this->actingAs($tenant)->get(route('meters.show', $meter))->assertForbidden();
        $this->actingAs($tenant)
            ->get(route('parcels.index'))
            ->assertOk()
            ->assertDontSee($parcel->parcel_number);
        $this->actingAs($tenant)
            ->get(route('meter-reading-submissions.create', $meter))
            ->assertForbidden();
        $this->actingAs($tenant)
            ->get(route('tenant-portal.index'))
            ->assertOk()
            ->assertDontSee($parcel->parcel_number);
    }

    public function test_tenant_submission_is_private_and_only_approved_value_becomes_reading(): void
    {
        Storage::fake('local');
        [$tenant, , $parcel] = $this->tenantScenario();
        $meter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'installed_at' => now()->subYear(),
            'start_reading' => '10.0000',
            'status' => MeterStatus::Active,
        ]);
        $foreignTenant = User::factory()->create(['role' => UserRole::Tenant]);
        $waterManager = User::factory()->create(['role' => UserRole::WaterManager]);

        $this->assertTrue($tenant->can('submitReading', $meter));
        $this->actingAs($tenant)
            ->post(route('meter-reading-submissions.store', $meter), [
                'reading_value' => '123.4567',
                'reading_date' => now()->toDateString(),
                'photo' => UploadedFile::fake()->image('zaehler.jpg'),
                'notes' => 'Stand gut erkennbar',
            ])
            ->assertRedirect(route('meter-reading-submissions.index'));

        $submission = MeterReadingSubmission::query()->firstOrFail();
        $this->assertSame(MeterReadingSubmissionStatus::Pending, $submission->status);
        $this->assertDatabaseCount('meter_readings', 0);
        Storage::disk('local')->assertExists($submission->photo_path);

        $this->actingAs($foreignTenant)
            ->get(route('meter-reading-submissions.photo', $submission))
            ->assertForbidden();
        $this->actingAs($waterManager)
            ->get(route('meter-reading-submissions.index'))
            ->assertOk()
            ->assertSee('Foto ansehen')
            ->assertSee('data-private-photo-modal', false)
            ->assertSee('data-private-photo-zoom-in', false)
            ->assertSee('data-private-photo-zoom-out', false)
            ->assertSee('data-private-photo-reset', false)
            ->assertSee(
                route('meter-reading-submissions.photo', $submission),
                false,
            );
        $photoResponse = $this->actingAs($waterManager)
            ->get(route('meter-reading-submissions.photo', $submission));
        $photoResponse
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringStartsWith(
            'inline;',
            $photoResponse->headers->get('Content-Disposition'),
        );
        $this->assertStringContainsString(
            'zaehler.jpg',
            $photoResponse->headers->get('Content-Disposition'),
        );
        $this->actingAs($tenant)
            ->post(route('meter-reading-submissions.approve', $submission))
            ->assertForbidden();

        $this->actingAs($waterManager)
            ->post(route('meter-reading-submissions.approve', $submission), [
                'review_note' => 'Foto geprüft',
            ])
            ->assertRedirect();

        $submission->refresh();
        $this->assertSame(MeterReadingSubmissionStatus::Approved, $submission->status);
        $this->assertNotNull($submission->meter_reading_id);
        $this->assertSame(
            MeterReadingSource::Tenant,
            $submission->meterReading->source,
        );
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'meter_reading.submission.approved',
            'subject_id' => $submission->id,
        ]);
    }

    public function test_meter_photo_rejects_executable_upload(): void
    {
        Storage::fake('local');
        [$tenant, , $parcel] = $this->tenantScenario();
        $meter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'installed_at' => now()->subYear(),
            'status' => MeterStatus::Active,
        ]);

        $this->assertTrue($tenant->can('submitReading', $meter));
        $this->actingAs($tenant)
            ->post(route('meter-reading-submissions.store', $meter), [
                'reading_value' => '25',
                'reading_date' => now()->toDateString(),
                'photo' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertDatabaseCount('meter_reading_submissions', 0);
    }

    public function test_implausible_submission_remains_pending_when_review_fails(): void
    {
        [$tenant, , $parcel] = $this->tenantScenario();
        $meter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'installed_at' => now()->subYear(),
            'start_reading' => '100.0000',
            'status' => MeterStatus::Active,
        ]);
        $waterManager = User::factory()->create(['role' => UserRole::WaterManager]);
        $previousReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_value' => '120.0000',
            'reading_date' => now()->subDay()->toDateString(),
        ]);
        MeterReadingCorrection::factory()->create([
            'meter_reading_id' => $previousReading->id,
            'corrected_value' => '130.0000',
            'corrected_by' => $waterManager->id,
        ]);
        $submission = MeterReadingSubmission::factory()->create([
            'meter_id' => $meter->id,
            'submitted_by' => $tenant->id,
            'reading_value' => '50.0000',
            'reading_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($waterManager)
            ->from(route('meter-reading-submissions.index'))
            ->post(route('meter-reading-submissions.approve', $submission));
        $response
            ->assertSessionHas(
                'review_error',
                'Der Zählerstand darf nicht kleiner als der vorherige Stand sein.',
            )
            ->assertSessionHas('review_submission_id', $submission->id);

        $this->followRedirects($response)
            ->assertOk()
            ->assertSee('Zählerstand konnte nicht bestätigt werden.')
            ->assertSee('Der Zählerstand darf nicht kleiner als der vorherige Stand sein.')
            ->assertSee('Vorheriger Stand')
            ->assertSee('130.0000')
            ->assertSee('Niedriger als der vorherige Stand')
            ->assertSee('table-danger', false);

        $this->assertSame(
            MeterReadingSubmissionStatus::Pending,
            $submission->fresh()->status,
        );
        $this->assertDatabaseCount('meter_readings', 1);
    }

    /**
     * @return array{User, Member, Parcel}
     */
    private function tenantScenario(): array
    {
        $tenant = User::factory()->create(['role' => UserRole::Tenant]);
        $member = Member::factory()->create(['user_id' => $tenant->id]);
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'parcel_id' => $parcel->id,
            'member_id' => $member->id,
            'starts_at' => now()->subYear(),
        ]);

        return [$tenant, $member, $parcel];
    }

    private function document(
        User $uploader,
        string $title,
        string $path,
        ?Member $member = null,
        ?Parcel $parcel = null,
        DocumentVisibility $visibility = DocumentVisibility::Tenant,
    ): Document {
        return Document::create([
            'member_id' => $member?->id,
            'parcel_id' => $parcel?->id,
            'uploaded_by' => $uploader->id,
            'title' => $title,
            'type' => DocumentType::Other,
            'visibility' => $visibility,
            'file_path' => $path,
            'original_name' => basename($path),
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'published_at' => now(),
        ]);
    }
}
