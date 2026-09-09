<?php

namespace Tests\Feature;

use App\Enums\PrivacyErasureStatus;
use App\Models\Member;
use App\Models\PrivacyErasureRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DemoProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_blocks_direct_access_to_credentials_keys_and_backups(): void
    {
        config(['demo.enabled' => true]);
        $this->withoutMiddleware(ThrottleRequests::class);
        $admin = User::factory()->administrator()->create();
        $tenant = User::factory()->create();
        $erasureRequest = PrivacyErasureRequest::create([
            'member_id' => Member::factory()->create(['user_id' => $tenant->id])->id,
            'requested_by' => $tenant->id,
            'status' => PrivacyErasureStatus::Ready,
            'requested_at' => now(),
        ]);
        $this->actingAs($admin);

        $this->put(route('account.password.update'))->assertForbidden();
        $this->put(route('user-permissions.update', $tenant))->assertForbidden();
        $this->post(route('data-transfer.app-key'))->assertForbidden();
        $this->post(route('backups.create'))->assertForbidden();
        $this->post(route('backups.restore'))->assertForbidden();
        $this->post(route('privacy-erasure-requests.anonymize', $erasureRequest))->assertForbidden();
        $this->get(route('backups.download', 'test.zip'))->assertForbidden();
        $this->delete(route('backups.destroy', 'test.zip'))->assertForbidden();
        $this->post(route('password.email'))->assertForbidden();
        $this->post(route('password.update'))->assertForbidden();
        $this->post(route('tenant-registration.store'))->assertForbidden();
        $this->get(route('home'))->assertOk();
    }

    public function test_demo_blocks_even_explicit_mailers_before_transport_delivery(): void
    {
        config(['demo.enabled' => true]);
        $mailer = Mail::mailer('array');
        $mailer->raw('Testnachricht', fn ($message) => $message->to('recipient@example.test')->subject('Test'));
        $this->assertCount(0, $mailer->getSymfonyTransport()->messages());

        config(['demo.enabled' => false]);
        $mailer->raw('Testnachricht', fn ($message) => $message->to('recipient@example.test')->subject('Test'));
        $this->assertCount(1, $mailer->getSymfonyTransport()->messages());
    }
}
