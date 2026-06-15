<?php

namespace Tests\Feature;

use App\Models\ApplicationSetting;
use App\Models\PermissionProfile;
use App\Models\User;
use App\Services\AssociationDocumentProfile;
use App\Services\LetterManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssociationSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_store_association_details_encrypted_bank_data_and_logo(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();
        $profile = PermissionProfile::query()->firstOrFail();

        $this->actingAs($administrator)
            ->put(route('application-settings.update'), $this->payload($profile, [
                'logo' => UploadedFile::fake()->image('vereinslogo.png', 400, 200),
                'bank_account_holder' => 'KGV Sonnental e. V.',
                'bank_name' => 'Gartenbank',
                'bank_iban' => 'DE89 3704 0044 0532 0130 00',
                'bank_bic' => 'COBADEFFXXX',
                'default_payment_term_days' => '21',
            ]))
            ->assertRedirect();

        $settings = ApplicationSetting::current()->fresh();
        $this->assertSame('Kleingartenverein Sonnental e. V.', $settings->association_name);
        $this->assertSame('DE89370400440532013000', $settings->bank_iban);
        $this->assertSame('3000', $settings->bank_iban_last_four);
        $this->assertSame(21, $settings->default_payment_term_days);
        $this->assertNotSame(
            'DE89370400440532013000',
            DB::table('application_settings')->value('bank_iban'),
        );
        Storage::disk('local')->assertExists($settings->logo_path);

        $this->get(route('association-logo.show'))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_blank_bank_fields_preserve_existing_secrets_and_explicit_clear_removes_them(): void
    {
        $administrator = User::factory()->administrator()->create();
        $profile = PermissionProfile::query()->firstOrFail();
        $settings = ApplicationSetting::current();
        $settings->update([
            'bank_account_holder' => 'Bestehender Verein',
            'bank_name' => 'Bestehende Bank',
            'bank_iban' => 'DE89370400440532013000',
            'bank_iban_last_four' => '1300',
            'bank_bic' => 'COBADEFFXXX',
        ]);

        $this->actingAs($administrator)
            ->put(route('application-settings.update'), $this->payload($profile))
            ->assertRedirect();

        $this->assertSame('DE89370400440532013000', $settings->fresh()->bank_iban);
        $this->assertSame('COBADEFFXXX', $settings->fresh()->bank_bic);

        $this->actingAs($administrator)
            ->put(route('application-settings.update'), $this->payload($profile, [
                'clear_bank_details' => '1',
            ]))
            ->assertRedirect();

        $settings->refresh();
        $this->assertNull($settings->bank_iban);
        $this->assertNull($settings->bank_bic);
        $this->assertNull($settings->bank_account_holder);
    }

    public function test_logo_can_be_removed_without_exposing_storage_path(): void
    {
        Storage::fake('local');
        $administrator = User::factory()->administrator()->create();
        $profile = PermissionProfile::query()->firstOrFail();
        $settings = ApplicationSetting::current();
        $path = UploadedFile::fake()->image('alt.png')->store('association/logo', 'local');
        $settings->update([
            'logo_path' => $path,
            'logo_original_name' => 'alt.png',
            'logo_mime' => 'image/png',
            'logo_size' => 100,
        ]);

        $this->actingAs($administrator)
            ->put(route('application-settings.update'), $this->payload($profile, [
                'remove_logo' => '1',
            ]))
            ->assertRedirect();

        $this->assertNull($settings->fresh()->logo_path);
        Storage::disk('local')->assertExists($path);
        $this->get(route('association-logo.show'))->assertNotFound();
    }

    public function test_non_administrator_cannot_change_association_settings(): void
    {
        $board = User::factory()->create();
        $profile = PermissionProfile::query()->firstOrFail();

        $this->actingAs($board)
            ->put(route('application-settings.update'), $this->payload($profile))
            ->assertForbidden();
    }

    public function test_document_profile_contains_association_and_payment_details(): void
    {
        ApplicationSetting::current()->update([
            'association_name' => 'KGV Dokumententest e. V.',
            'street' => 'Briefweg 7',
            'zip' => '12345',
            'city' => 'Musterstadt',
            'email' => 'kontakt@example.test',
            'bank_iban' => 'DE89370400440532013000',
            'bank_iban_last_four' => '1300',
        ]);

        $profile = app(AssociationDocumentProfile::class)->get();

        $this->assertSame('KGV Dokumententest e. V.', $profile['name']);
        $this->assertSame('Briefweg 7', $profile['street']);
        $this->assertSame('DE89370400440532013000', $profile['bank_iban']);
    }

    public function test_created_letter_keeps_historical_association_snapshot(): void
    {
        $creator = User::factory()->administrator()->create();
        $settings = ApplicationSetting::current();
        $settings->update([
            'association_name' => 'Alter Vereinsname e. V.',
            'street' => 'Alter Weg 1',
            'zip' => '11111',
            'city' => 'Altstadt',
        ]);
        $letter = app(LetterManager::class)->create([
            'recipient_name' => 'Erika Beispiel',
            'street' => 'Empfängerweg 2',
            'zip' => '22222',
            'city' => 'Zielstadt',
            'subject' => 'Historischer Absender',
            'body' => 'Testinhalt',
        ], $creator);

        $settings->update([
            'association_name' => 'Neuer Vereinsname e. V.',
            'street' => 'Neuer Weg 3',
        ]);

        $resolved = app(AssociationDocumentProfile::class)
            ->resolve($letter->fresh()->association_snapshot);

        $this->assertSame('Alter Vereinsname e. V.', $resolved['name']);
        $this->assertSame('Alter Weg 1', $resolved['street']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(PermissionProfile $profile, array $overrides = []): array
    {
        return [
            'system_name' => 'Sonnental',
            'association_name' => 'Kleingartenverein Sonnental e. V.',
            'street' => 'Gartenweg 1',
            'zip' => '99423',
            'city' => 'Weimar',
            'contact_name' => 'Der Vorstand',
            'phone' => '03643 123456',
            'email' => 'vorstand@sonnental.test',
            'website' => 'https://sonnental.example.test',
            'remove_logo' => '0',
            'bank_account_holder' => null,
            'bank_name' => null,
            'bank_iban' => null,
            'bank_bic' => null,
            'clear_bank_details' => '0',
            'default_payment_term_days' => '14',
            'document_footer' => 'Registergericht Musterstadt · VR 1234',
            'email_signature' => "Mit freundlichen Grüßen\nDer Vorstand",
            'default_board_permission_profile_id' => $profile->id,
            'default_work_hours_required' => '8.00',
            'default_work_hour_penalty_rate' => '15.00',
            ...$overrides,
        ];
    }
}
