<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BillingPeriod;
use App\Models\Member;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Parcel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['demo.password' => 'Demo1234!']);
    }

    public function test_demo_seed_creates_three_year_dataset_and_can_be_repeated(): void
    {
        $this->artisan('okgv:demo-seed', ['--force' => true])->assertSuccessful();
        $this->artisan('okgv:demo-seed', ['--force' => true])->assertSuccessful();

        $this->assertSame(5, User::query()->where('email', 'like', '%.demo@okgv.test')->count());
        $this->assertSame(4, User::query()
            ->where('email', 'like', '%.demo@okgv.test')
            ->where('role', UserRole::Tenant->value)
            ->count());
        $this->assertSame(1, User::query()
            ->where('email', 'like', '%.demo@okgv.test')
            ->where('role', UserRole::Board->value)
            ->count());
        $this->assertSame(5, Member::query()->where('member_number', 'like', 'DEMO-M-%')->count());
        $this->assertSame(5, Parcel::query()->where('parcel_number', 'like', 'DEMO-%')->count());
        $this->assertSame(
            5,
            Parcel::query()
                ->where('parcel_number', 'like', 'DEMO-%')
                ->whereNotNull('map_polygon')
                ->count(),
        );
        $this->assertSame(11, Meter::query()->where('meter_number', 'like', 'DEMO-%')->count());
        $this->assertSame(42, MeterReading::query()
            ->whereHas('meter', fn ($query) => $query->where('meter_number', 'like', 'DEMO-%'))
            ->count());
        $this->assertSame(3, BillingPeriod::query()->where('name', 'like', 'DEMO Abrechnung %')->count());
        $this->assertDatabaseCount('work_hours', 15);
        $this->assertDatabaseCount('work_events', 3);
        $this->assertDatabaseCount('work_event_participants', 15);
        $this->assertDatabaseCount('work_hour_submissions', 7);
        $this->assertDatabaseCount('meter_reading_submissions', 1);
        $this->assertDatabaseCount('billing_rates', 12);
    }

    public function test_all_demo_accounts_can_log_in_through_the_web_form(): void
    {
        $this->artisan('okgv:demo-seed', ['--force' => true])->assertSuccessful();

        foreach ([
            'vorstand.demo@okgv.test',
            'paechter1.demo@okgv.test',
            'paechter2.demo@okgv.test',
            'paechter3.demo@okgv.test',
            'paechter4.demo@okgv.test',
        ] as $email) {
            $this->post(route('login'), [
                'email' => $email,
                'password' => 'Demo1234!',
            ])->assertRedirect('/dashboard');

            $this->assertAuthenticatedAs(User::query()->where('email', $email)->firstOrFail());
            $this->post(route('logout'))->assertRedirect('/');
            $this->assertGuest();
        }
    }

    public function test_demo_seed_uses_configured_login_emails_for_visible_demo_accounts(): void
    {
        config([
            'demo.board_email' => 'vorstand.demo@demo.okgv.de',
            'demo.tenant_email' => 'paechter1.demo@demo.okgv.de',
        ]);

        $this->artisan('okgv:demo-seed', ['--force' => true])->assertSuccessful();

        foreach ([
            'vorstand.demo@demo.okgv.de',
            'paechter1.demo@demo.okgv.de',
            'paechter2.demo@okgv.test',
            'paechter3.demo@okgv.test',
            'paechter4.demo@okgv.test',
        ] as $email) {
            $this->post(route('login'), [
                'email' => $email,
                'password' => 'Demo1234!',
            ])->assertRedirect('/dashboard');

            $this->assertAuthenticatedAs(User::query()->where('email', $email)->firstOrFail());
            $this->post(route('logout'))->assertRedirect('/');
            $this->assertGuest();
        }

        $this->artisan('okgv:demo-purge', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'vorstand.demo@demo.okgv.de']);
        $this->assertDatabaseMissing('users', ['email' => 'paechter1.demo@demo.okgv.de']);
    }

    public function test_demo_seed_rejects_overlapping_real_billing_period(): void
    {
        BillingPeriod::factory()->create([
            'name' => 'Abrechnung 2026',
            'starts_at' => '2026-01-01',
            'ends_at' => '2026-12-31',
        ]);

        $this->artisan('okgv:demo-seed', ['--force' => true])
            ->expectsOutputToContain('Für 2026 existiert bereits eine überschneidende Abrechnungsperiode.')
            ->assertFailed();

        $this->assertDatabaseCount('billing_periods', 1);
        $this->assertDatabaseMissing('users', ['email' => 'vorstand.demo@okgv.test']);
    }

    public function test_demo_purge_removes_only_marked_demo_data(): void
    {
        Storage::fake('local');
        $realUser = User::factory()->create(['email' => 'bestand@example.test']);
        $realMember = Member::factory()->create(['user_id' => $realUser->id]);
        $realParcel = Parcel::factory()->create(['parcel_number' => 'BESTAND-01']);

        $this->artisan('okgv:demo-seed', ['--force' => true])->assertSuccessful();
        $this->createDerivedDemoData();
        $this->artisan('okgv:demo-purge', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $realUser->id]);
        $this->assertDatabaseHas('members', ['id' => $realMember->id]);
        $this->assertDatabaseHas('parcels', ['id' => $realParcel->id]);
        $this->assertDatabaseMissing('users', ['email' => 'vorstand.demo@okgv.test']);
        $this->assertSame(0, Parcel::query()->where('parcel_number', 'like', 'DEMO-%')->count());
        $this->assertSame(0, BillingPeriod::query()->where('name', 'like', 'DEMO Abrechnung %')->count());
        $this->assertDatabaseMissing('invoices', ['invoice_number' => 'DEMO-RE-2026-001']);
        $this->assertDatabaseMissing('dunning_notices', ['notice_number' => 'DEMO-MA-2026-001']);
        Storage::disk('local')->assertMissing('documents/demo-beleg.txt');
    }

    private function createDerivedDemoData(): void
    {
        $now = now();
        $userId = User::query()->where('email', 'vorstand.demo@okgv.test')->valueOrFail('id');
        $member = Member::query()->where('member_number', 'DEMO-M-001')->firstOrFail();
        $parcelId = Parcel::query()->where('parcel_number', 'DEMO-01')->valueOrFail('id');
        $periodId = BillingPeriod::query()->where('name', 'DEMO Abrechnung 2026')->valueOrFail('id');
        $invoiceId = DB::table('invoices')->insertGetId([
            'billing_period_id' => $periodId,
            'member_id' => $member->id,
            'invoice_number' => 'DEMO-RE-2026-001',
            'status' => 'approved',
            'payment_status' => 'open',
            'issued_at' => '2026-12-31',
            'due_at' => '2027-02-15',
            'total_amount' => '25.00',
            'approved_at' => $now,
            'approved_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('invoice_items')->insert([
            'invoice_id' => $invoiceId,
            'parcel_id' => $parcelId,
            'code' => 'DEMO_ITEM',
            'description' => 'Demo-Rechnungsposition',
            'calculation_type' => 'fixed',
            'quantity' => '1.0000',
            'unit_price' => '25.0000',
            'total_amount' => '25.00',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('invoice_recipients')->insert([
            'invoice_id' => $invoiceId,
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'street' => $member->street,
            'zip' => $member->zip,
            'city' => $member->city,
            'is_primary' => true,
            'position' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('dunning_notices')->insert([
            'invoice_id' => $invoiceId,
            'notice_number' => 'DEMO-MA-2026-001',
            'level' => 1,
            'status' => 'issued',
            'invoice_number' => 'DEMO-RE-2026-001',
            'issued_at' => '2027-02-20',
            'due_at' => '2027-03-06',
            'invoice_amount' => '25.00',
            'fee_amount' => '2.50',
            'previous_fees_amount' => '0.00',
            'total_due' => '27.50',
            'recipients' => json_encode([['name' => $member->full_name]], JSON_THROW_ON_ERROR),
            'created_by' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Storage::disk('local')->put('documents/demo-beleg.txt', 'Demo');
        $documentId = DB::table('documents')->insertGetId([
            'member_id' => $member->id,
            'parcel_id' => $parcelId,
            'uploaded_by' => $userId,
            'title' => 'Demo-Beleg',
            'description' => 'Wird mit dem Demo-Bestand entfernt.',
            'type' => 'other',
            'visibility' => 'internal',
            'file_path' => 'documents/demo-beleg.txt',
            'original_name' => 'demo-beleg.txt',
            'mime_type' => 'text/plain',
            'file_size' => 4,
            'current_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('document_versions')->insert([
            'document_id' => $documentId,
            'uploaded_by' => $userId,
            'version_number' => 1,
            'file_path' => 'documents/demo-beleg.txt',
            'original_name' => 'demo-beleg.txt',
            'mime_type' => 'text/plain',
            'file_size' => 4,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
