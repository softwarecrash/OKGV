<?php

namespace Tests\Feature;

use App\Enums\InvoicePaymentStatus;
use App\Enums\InvoiceStatus;
use App\Enums\MailCampaignStatus;
use App\Enums\MailRecipientGroup;
use App\Enums\MailRecipientStatus;
use App\Enums\UserPermission;
use App\Enums\UserRole;
use App\Mail\CampaignMessage;
use App\Mail\SmtpTestMessage;
use App\Models\AuditLog;
use App\Models\BillingPeriod;
use App\Models\CommunicationSetting;
use App\Models\Invoice;
use App\Models\InvoiceRecipient;
use App\Models\Letter;
use App\Models\MailCampaign;
use App\Models\Member;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\User;
use App\Services\MailRecipientResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CommunicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_smtp_credentials_are_encrypted_and_not_shown_again(): void
    {
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->put(route('communication-settings.update'), [
                'smtp_enabled' => true,
                'smtp_scheme' => 'smtp',
                'smtp_host' => 'smtp.example.test',
                'smtp_port' => 587,
                'smtp_username' => 'mailer@example.test',
                'smtp_password' => 'VerySecretPassword',
                'clear_credentials' => false,
                'from_address' => 'verein@example.test',
                'from_name' => 'KGV Test',
            ])
            ->assertRedirect();

        $raw = DB::table('communication_settings')->first();
        $this->assertNotSame('mailer@example.test', $raw->smtp_username);
        $this->assertNotSame('VerySecretPassword', $raw->smtp_password);
        $this->assertSame('mailer@example.test', CommunicationSetting::current()->smtp_username);

        $this->actingAs($administrator)
            ->get(route('application-settings.edit'))
            ->assertOk()
            ->assertSee('SMTP-Einstellungen')
            ->assertSee('mailer@example.test')
            ->assertDontSee('VerySecretPassword');
    }

    public function test_administrator_can_send_smtp_test_to_custom_validated_address(): void
    {
        Mail::fake();
        $administrator = User::factory()->administrator()->create();
        CommunicationSetting::create([
            'smtp_enabled' => true,
            'smtp_scheme' => 'smtp',
            'smtp_host' => 'smtp.example.test',
            'smtp_port' => 587,
            'from_address' => 'verein@example.test',
            'from_name' => 'KGV Test',
        ]);

        $this->actingAs($administrator)
            ->post(route('communication-settings.test'), [
                'test_email' => 'extern@example.test',
            ])
            ->assertRedirect(route('application-settings.edit', ['section' => 'smtp']))
            ->assertSessionHas(
                'status',
                fn (string $status): bool => str_contains(
                    $status,
                    'Der SMTP-Server hat die Testmail für extern@example.test angenommen.',
                ),
            );

        Mail::assertSent(SmtpTestMessage::class, fn ($mail) => $mail->hasTo('extern@example.test'));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'communication.smtp.tested',
            'user_id' => $administrator->id,
        ]);
        $auditLog = AuditLog::query()
            ->where('action', 'communication.smtp.tested')
            ->latest('id')
            ->firstOrFail();
        $this->assertArrayHasKey('message_id', $auditLog->metadata);

        $this->actingAs($administrator)
            ->from(route('application-settings.edit'))
            ->post(route('communication-settings.test'), [
                'test_email' => 'keine-adresse',
            ])
            ->assertRedirect(route('application-settings.edit'))
            ->assertSessionHasErrors('test_email');

        Mail::assertSent(SmtpTestMessage::class, 1);
    }

    public function test_demo_mode_blocks_smtp_settings_and_test_mails(): void
    {
        Mail::fake();
        config(['demo.enabled' => true]);
        $administrator = User::factory()->administrator()->create();

        $this->actingAs($administrator)
            ->get(route('application-settings.edit', ['section' => 'smtp']))
            ->assertOk()
            ->assertSee('Diese Installation läuft im Demo-Modus')
            ->assertSee('SMTP-Einstellungen speichern')
            ->assertSee('disabled', false);

        $this->actingAs($administrator)
            ->put(route('communication-settings.update'), [
                'smtp_enabled' => true,
                'smtp_scheme' => 'smtp',
                'smtp_host' => 'smtp.example.test',
                'smtp_port' => 587,
                'smtp_username' => 'mailer@example.test',
                'smtp_password' => 'VerySecretPassword',
                'clear_credentials' => false,
                'from_address' => 'verein@example.test',
                'from_name' => 'KGV Test',
            ])
            ->assertRedirect(route('application-settings.edit', ['section' => 'smtp']))
            ->assertSessionHasErrors([
                'smtp_enabled' => 'SMTP-Einstellungen sind im Demo-Modus gesperrt.',
            ]);

        $this->assertFalse(CommunicationSetting::current()->smtp_enabled);

        $this->actingAs($administrator)
            ->post(route('communication-settings.test'), [
                'test_email' => 'extern@example.test',
            ])
            ->assertRedirect(route('application-settings.edit', ['section' => 'smtp']))
            ->assertSessionHasErrors([
                'test_email' => 'Testmails sind im Demo-Modus deaktiviert.',
            ]);

        Mail::assertNothingSent();
    }

    public function test_smtp_test_rate_limit_returns_to_form_with_german_message(): void
    {
        Mail::fake();
        $administrator = User::factory()->administrator()->create();
        CommunicationSetting::create([
            'smtp_enabled' => true,
            'smtp_scheme' => 'smtp',
            'smtp_host' => 'smtp.example.test',
            'smtp_port' => 587,
            'from_address' => 'verein@example.test',
            'from_name' => 'KGV Test',
        ]);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->actingAs($administrator)
                ->post(route('communication-settings.test'), [
                    'test_email' => 'extern@example.test',
                ])
                ->assertRedirect(route('application-settings.edit', ['section' => 'smtp']));
        }

        $this->actingAs($administrator)
            ->post(route('communication-settings.test'), [
                'test_email' => 'extern@example.test',
            ])
            ->assertRedirect(route('application-settings.edit', ['section' => 'smtp']))
            ->assertSessionHasErrors([
                'test_email' => 'Zu viele Testmails in kurzer Zeit. Bitte warte etwa eine Minute und versuche es erneut.',
            ]);

        Mail::assertSent(SmtpTestMessage::class, 10);
    }

    public function test_campaign_deduplicates_recipients_and_records_delivery_history(): void
    {
        Mail::fake();
        $administrator = User::factory()->administrator()->create();
        CommunicationSetting::create([
            'smtp_enabled' => true,
            'smtp_scheme' => 'smtp',
            'smtp_host' => 'smtp.example.test',
            'smtp_port' => 587,
            'smtp_username' => 'mailer',
            'smtp_password' => 'secret',
            'from_address' => 'verein@example.test',
            'from_name' => 'KGV Test',
        ]);
        Member::factory()->create(['email' => 'same@example.test']);
        Member::factory()->create(['email' => 'same@example.test']);
        Member::factory()->create(['email' => null]);

        $this->actingAs($administrator)
            ->post(route('mail-campaigns.store'), [
                'recipient_group' => MailRecipientGroup::ActiveMembers->value,
                'subject' => 'Arbeitseinsatz',
                'body' => 'Bitte Termin vormerken.',
            ])
            ->assertRedirect();

        $campaign = MailCampaign::query()->firstOrFail();
        $this->actingAs($administrator)
            ->post(route('mail-campaigns.send', $campaign))
            ->assertRedirect(route('mail-campaigns.show', $campaign));

        $campaign->refresh();
        $this->assertSame(MailCampaignStatus::Sent, $campaign->status);
        $this->assertSame(1, $campaign->recipient_count);
        $this->assertSame(1, $campaign->sent_count);
        $this->assertSame(0, $campaign->failed_count);
        $this->assertDatabaseHas('mail_campaign_recipients', [
            'email' => 'same@example.test',
            'status' => MailRecipientStatus::Sent->value,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'mail_campaign.sent',
            'subject_id' => $campaign->id,
        ]);
        Mail::assertSent(CampaignMessage::class, 1);
    }

    public function test_account_without_communication_permission_is_denied(): void
    {
        $board = User::factory()->create([
            'role' => UserRole::Board,
            'permissions' => [UserPermission::ViewAllMasterData->value],
        ]);

        $this->actingAs($board)->get(route('mail-campaigns.index'))->assertForbidden();
        $this->actingAs($board)->get(route('letters.index'))->assertForbidden();
        $this->actingAs($board)->get(route('application-settings.edit'))->assertForbidden();
        $this->actingAs($board)
            ->put(route('communication-settings.update'), [
                'smtp_enabled' => false,
                'smtp_scheme' => 'smtp',
                'smtp_host' => 'smtp.example.test',
                'smtp_port' => 587,
                'smtp_username' => null,
                'smtp_password' => null,
                'clear_credentials' => false,
                'from_address' => 'verein@example.test',
                'from_name' => 'Verein',
            ])
            ->assertForbidden();
    }

    public function test_dynamic_recipient_groups_find_current_tenants_open_invoices_and_missing_readings(): void
    {
        $period = BillingPeriod::factory()->create([
            'starts_at' => now()->subYear()->startOfYear(),
            'ends_at' => now()->subYear()->endOfYear(),
        ]);
        $member = Member::factory()->create(['email' => 'tenant@example.test']);
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'member_id' => $member->id,
            'parcel_id' => $parcel->id,
            'starts_at' => now()->subYear(),
            'ends_at' => null,
        ]);
        $meter = Meter::factory()->create([
            'parcel_id' => $parcel->id,
            'installed_at' => $period->starts_at,
        ]);
        $invoice = $this->approvedInvoice(now()->subDay()->toDateString(), $member);

        $resolver = app(MailRecipientResolver::class);

        $this->assertSame(
            ['tenant@example.test'],
            $resolver->resolve(MailRecipientGroup::CurrentTenants)->pluck('email')->all(),
        );
        $this->assertSame(
            ['tenant@example.test'],
            $resolver->resolve(MailRecipientGroup::OpenInvoices)->pluck('email')->all(),
        );
        $this->assertSame(
            ['tenant@example.test'],
            $resolver->resolve(MailRecipientGroup::MissingMeterReadings)->pluck('email')->all(),
        );
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => $period->ends_at,
        ]);
        $this->assertEmpty(
            $resolver->resolve(MailRecipientGroup::MissingMeterReadings),
        );
        $this->assertTrue($invoice->canReceivePaymentReminder());
    }

    public function test_missing_meter_reading_group_stays_empty_during_running_period(): void
    {
        BillingPeriod::factory()->create([
            'starts_at' => now()->startOfYear(),
            'ends_at' => now()->endOfYear(),
        ]);
        $member = Member::factory()->create(['email' => 'tenant@example.test']);
        $parcel = Parcel::factory()->create();
        ParcelTenant::factory()->create([
            'member_id' => $member->id,
            'parcel_id' => $parcel->id,
            'starts_at' => now()->subYear(),
            'ends_at' => null,
        ]);
        Meter::factory()->create(['parcel_id' => $parcel->id]);

        $this->assertEmpty(
            app(MailRecipientResolver::class)
                ->resolve(MailRecipientGroup::MissingMeterReadings),
        );
    }

    public function test_letter_keeps_address_snapshot_and_generates_pdf(): void
    {
        $administrator = User::factory()->administrator()->create();
        $member = Member::factory()->create([
            'street' => 'Alter Weg 1',
            'zip' => '12345',
            'city' => 'Altstadt',
        ]);

        $this->actingAs($administrator)
            ->post(route('letters.store'), [
                'member_id' => $member->id,
                'subject' => 'Einladung',
                'body' => 'Hiermit laden wir dich ein.',
            ])
            ->assertRedirect();

        $letter = Letter::query()->firstOrFail();
        $member->update(['street' => 'Neuer Weg 2']);

        $this->assertSame('Alter Weg 1', $letter->fresh()->street);
        $this->actingAs($administrator)
            ->get(route('letters.pdf', $letter))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_letter_form_provides_member_address_for_automatic_input(): void
    {
        $administrator = User::factory()->administrator()->create();
        Member::factory()->create([
            'first_name' => 'Erika',
            'last_name' => 'Erbse',
            'street' => 'Gartenweg 7',
            'zip' => '12345',
            'city' => 'Grünstadt',
        ]);

        $this->actingAs($administrator)
            ->get(route('letters.create'))
            ->assertOk()
            ->assertSee('data-recipient-name="Erika Erbse"', false)
            ->assertSee('data-recipient-street="Gartenweg 7"', false)
            ->assertSee('data-recipient-zip="12345"', false)
            ->assertSee('data-recipient-city="Grünstadt"', false)
            ->assertSee('Empfängeranschrift')
            ->assertDontSee('Nur ausfüllen, wenn kein Mitglied ausgewählt wurde.');
    }

    public function test_payment_reminder_requires_overdue_open_invoice(): void
    {
        $administrator = User::factory()->administrator()->create();
        $overdue = $this->approvedInvoice(now()->subDay()->toDateString());
        $future = $this->approvedInvoice(now()->addDay()->toDateString());

        $this->actingAs($administrator)
            ->get(route('invoices.payment-reminder', $overdue))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($administrator)
            ->get(route('invoices.payment-reminder', $future))
            ->assertStatus(422);
    }

    private function approvedInvoice(string $dueAt, ?Member $member = null): Invoice
    {
        $member ??= Member::factory()->create();
        $invoice = Invoice::factory()->create([
            'member_id' => $member->id,
            'status' => InvoiceStatus::Approved,
            'payment_status' => InvoicePaymentStatus::Open,
            'due_at' => $dueAt,
            'total_amount' => '125.50',
        ]);
        InvoiceRecipient::factory()->create([
            'invoice_id' => $invoice->id,
            'member_id' => $member->id,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'street' => $member->street,
            'zip' => $member->zip,
            'city' => $member->city,
        ]);

        return $invoice;
    }
}
