<?php

namespace App\Providers;

use App\Models\ApplicationSetting;
use App\Models\BillingPeriod;
use App\Models\BillingRate;
use App\Models\BillingRateAssignment;
use App\Models\BillingRateTemplate;
use App\Models\CommunicationSetting;
use App\Models\Document;
use App\Models\DunningNotice;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Letter;
use App\Models\MailCampaign;
use App\Models\Member;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\MeterReadingSubmission;
use App\Models\NumberSequence;
use App\Models\Parcel;
use App\Models\ParcelTenant;
use App\Models\PaymentBatch;
use App\Models\PaymentBatchItem;
use App\Models\PermissionProfile;
use App\Models\PrivacyErasureRequest;
use App\Models\RegistrationRequest;
use App\Models\SepaMandate;
use App\Models\SepaSetting;
use App\Models\TenantTransition;
use App\Models\User;
use App\Models\WaitingListEntry;
use App\Models\WorkEvent;
use App\Models\WorkEventParticipant;
use App\Models\WorkHour;
use App\Models\WorkHourSubmission;
use App\Policies\ApplicationSettingPolicy;
use App\Policies\BillingPeriodPolicy;
use App\Policies\BillingRateAssignmentPolicy;
use App\Policies\BillingRatePolicy;
use App\Policies\BillingRateTemplatePolicy;
use App\Policies\CommunicationSettingPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\DunningNoticePolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LetterPolicy;
use App\Policies\MailCampaignPolicy;
use App\Policies\MemberPolicy;
use App\Policies\MeterPolicy;
use App\Policies\MeterReadingPolicy;
use App\Policies\MeterReadingSubmissionPolicy;
use App\Policies\NumberSequencePolicy;
use App\Policies\ParcelPolicy;
use App\Policies\ParcelTenantPolicy;
use App\Policies\PaymentBatchItemPolicy;
use App\Policies\PaymentBatchPolicy;
use App\Policies\PermissionProfilePolicy;
use App\Policies\PrivacyErasureRequestPolicy;
use App\Policies\RegistrationRequestPolicy;
use App\Policies\SepaMandatePolicy;
use App\Policies\SepaSettingPolicy;
use App\Policies\TenantTransitionPolicy;
use App\Policies\UserPolicy;
use App\Policies\WaitingListEntryPolicy;
use App\Policies\WorkEventParticipantPolicy;
use App\Policies\WorkEventPolicy;
use App\Policies\WorkHourPolicy;
use App\Policies\WorkHourSubmissionPolicy;
use App\Services\ActionIndicatorService;
use App\Services\AuditLogger;
use App\Services\ModuleManager;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(ModuleManager::class)->ensureValidConfiguration();

        if (config('demo.enabled')) {
            config(['mail.default' => 'log']);
        }

        Paginator::useBootstrapFive();

        RateLimiter::for('smtp-tests', function ($request): Limit {
            return Limit::perMinute(10)
                ->by((string) $request->user()->id)
                ->response(fn ($request, array $headers) => redirect()
                    ->route('application-settings.edit', ['section' => 'smtp'])
                    ->withErrors([
                        'test_email' => 'Zu viele Testmails in kurzer Zeit. Bitte warte etwa eine Minute und versuche es erneut.',
                    ])
                    ->withHeaders($headers));
        });

        if (Schema::hasTable('application_settings')) {
            $systemName = ApplicationSetting::query()->value('system_name');

            if ($systemName) {
                config([
                    'app.name' => $systemName,
                    'mail.from.name' => $systemName,
                ]);
            }
        }

        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();
            $view->with(
                'actionIndicators',
                $user
                    ? app(ActionIndicatorService::class)->forUser($user)
                    : [
                        'registrations' => 0,
                        'meter_readings' => 0,
                        'invoices' => 0,
                        'work_hours' => 0,
                        'work_events' => 0,
                        'work_hour_submissions' => 0,
                        'waiting_list' => 0,
                        'inventory' => 0,
                        'members_group' => 0,
                        'meters_group' => 0,
                        'finance_group' => 0,
                        'communication_group' => 0,
                        'dunning_notices' => 0,
                        'total' => 0,
                    ],
            );
        });

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ApplicationSetting::class, ApplicationSettingPolicy::class);
        Gate::policy(BillingPeriod::class, BillingPeriodPolicy::class);
        Gate::policy(BillingRate::class, BillingRatePolicy::class);
        Gate::policy(BillingRateTemplate::class, BillingRateTemplatePolicy::class);
        Gate::policy(BillingRateAssignment::class, BillingRateAssignmentPolicy::class);
        Gate::policy(CommunicationSetting::class, CommunicationSettingPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(InventoryItem::class, InventoryItemPolicy::class);
        Gate::policy(Letter::class, LetterPolicy::class);
        Gate::policy(MailCampaign::class, MailCampaignPolicy::class);
        Gate::policy(Member::class, MemberPolicy::class);
        Gate::policy(Meter::class, MeterPolicy::class);
        Gate::policy(MeterReading::class, MeterReadingPolicy::class);
        Gate::policy(MeterReadingSubmission::class, MeterReadingSubmissionPolicy::class);
        Gate::policy(NumberSequence::class, NumberSequencePolicy::class);
        Gate::policy(Parcel::class, ParcelPolicy::class);
        Gate::policy(ParcelTenant::class, ParcelTenantPolicy::class);
        Gate::policy(PaymentBatch::class, PaymentBatchPolicy::class);
        Gate::policy(PaymentBatchItem::class, PaymentBatchItemPolicy::class);
        Gate::policy(PermissionProfile::class, PermissionProfilePolicy::class);
        Gate::policy(PrivacyErasureRequest::class, PrivacyErasureRequestPolicy::class);
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(DunningNotice::class, DunningNoticePolicy::class);
        Gate::policy(RegistrationRequest::class, RegistrationRequestPolicy::class);
        Gate::policy(SepaMandate::class, SepaMandatePolicy::class);
        Gate::policy(SepaSetting::class, SepaSettingPolicy::class);
        Gate::policy(TenantTransition::class, TenantTransitionPolicy::class);
        Gate::policy(WorkHour::class, WorkHourPolicy::class);
        Gate::policy(WorkEvent::class, WorkEventPolicy::class);
        Gate::policy(WorkEventParticipant::class, WorkEventParticipantPolicy::class);
        Gate::policy(WorkHourSubmission::class, WorkHourSubmissionPolicy::class);
        Gate::policy(WaitingListEntry::class, WaitingListEntryPolicy::class);
        Event::listen(Login::class, fn (Login $event) => AuditLogger::log(
            action: 'auth.login',
            actor: $event->user,
        ));

        Event::listen(Logout::class, fn (Logout $event) => AuditLogger::log(
            action: 'auth.logout',
            actor: $event->user,
        ));

        Event::listen(Failed::class, fn (Failed $event) => AuditLogger::log(
            action: 'auth.failed',
            metadata: ['email' => $event->credentials['email'] ?? null],
        ));
    }
}
