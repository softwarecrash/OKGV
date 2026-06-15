<?php

use App\Http\Controllers\ApplicationSettingController;
use App\Http\Controllers\BillingPeriodController;
use App\Http\Controllers\BillingRateAssignmentController;
use App\Http\Controllers\BillingRateController;
use App\Http\Controllers\BillingRateTemplateController;
use App\Http\Controllers\CommunicationSettingController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DunningNoticeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryLoanController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\MailCampaignController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MeterController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\MeterReadingCorrectionController;
use App\Http\Controllers\MeterReadingSubmissionController;
use App\Http\Controllers\MeterReplacementController;
use App\Http\Controllers\ParcelController;
use App\Http\Controllers\ParcelTenantController;
use App\Http\Controllers\PaymentBatchController;
use App\Http\Controllers\PaymentReminderController;
use App\Http\Controllers\PaymentReturnController;
use App\Http\Controllers\PermissionProfileController;
use App\Http\Controllers\PortalDocumentController;
use App\Http\Controllers\PublicDocumentController;
use App\Http\Controllers\RegistrationRequestController;
use App\Http\Controllers\SepaMandateController;
use App\Http\Controllers\SepaSettingController;
use App\Http\Controllers\TenantPortalController;
use App\Http\Controllers\TenantRegistrationController;
use App\Http\Controllers\UserPermissionController;
use App\Http\Controllers\WaitingListEntryController;
use App\Http\Controllers\WorkEventController;
use App\Http\Controllers\WorkEventParticipantController;
use App\Http\Controllers\WorkHourController;
use App\Http\Controllers\WorkHourSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('home')
        : redirect()->route('login');
});

Auth::routes([
    'register' => false,
    'verify' => true,
]);

Route::middleware('guest')->group(function (): void {
    Route::get('paechter-registrierung', [TenantRegistrationController::class, 'create'])
        ->middleware('module:tenant_portal')
        ->name('tenant-registration.create');
    Route::post('paechter-registrierung', [TenantRegistrationController::class, 'store'])
        ->middleware(['module:tenant_portal', 'throttle:5,10'])
        ->name('tenant-registration.store');
});

Route::get('freigabe/dokument/{token}', [PublicDocumentController::class, 'download'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware(['module:documents', 'throttle:30,1'])
    ->name('documents.public');

Route::get('/dashboard', [HomeController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('paechterportal', [TenantPortalController::class, 'index'])
        ->middleware('module:tenant_portal')
        ->name('tenant-portal.index');
    Route::get('paechterportal/dokumente', [PortalDocumentController::class, 'index'])
        ->middleware(['module:tenant_portal', 'module:documents'])
        ->name('tenant-portal.documents');
    Route::get('paechterportal/dokumente/{document}', [PortalDocumentController::class, 'download'])
        ->middleware(['module:tenant_portal', 'module:documents'])
        ->name('tenant-portal.documents.download');
    Route::get('registrierungsanfragen', [RegistrationRequestController::class, 'index'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.index');
    Route::get('registrierungsanfragen/{registration_request}', [RegistrationRequestController::class, 'show'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.show');
    Route::post('registrierungsanfragen/{registration_request}/freigeben', [RegistrationRequestController::class, 'approve'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.approve');
    Route::post('registrierungsanfragen/{registration_request}/ablehnen', [RegistrationRequestController::class, 'reject'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.reject');
    Route::get('zaehlerstandsmeldungen', [MeterReadingSubmissionController::class, 'index'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.index');
    Route::get('zaehler/{meter}/stand-melden', [MeterReadingSubmissionController::class, 'create'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.create');
    Route::post('zaehler/{meter}/stand-melden', [MeterReadingSubmissionController::class, 'store'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.store');
    Route::get('zaehlerstandsmeldungen/{meter_reading_submission}/foto', [MeterReadingSubmissionController::class, 'photo'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.photo');
    Route::post('zaehlerstandsmeldungen/{meter_reading_submission}/freigeben', [MeterReadingSubmissionController::class, 'approve'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.approve');
    Route::post('zaehlerstandsmeldungen/{meter_reading_submission}/ablehnen', [MeterReadingSubmissionController::class, 'reject'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.reject');
    Route::get('user-permissions', [UserPermissionController::class, 'index'])
        ->name('user-permissions.index');
    Route::put('user-permissions/{user}', [UserPermissionController::class, 'update'])
        ->name('user-permissions.update');
    Route::get('globale-konfiguration', [ApplicationSettingController::class, 'edit'])
        ->name('application-settings.edit');
    Route::put('globale-konfiguration', [ApplicationSettingController::class, 'update'])
        ->name('application-settings.update');
    Route::resource('permission-profiles', PermissionProfileController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);
    Route::put('globale-konfiguration/smtp', [CommunicationSettingController::class, 'update'])
        ->name('communication-settings.update');
    Route::post('globale-konfiguration/smtp/test', [CommunicationSettingController::class, 'test'])
        ->middleware('throttle:smtp-tests')
        ->name('communication-settings.test');
    Route::post('mail-campaigns/{mail_campaign}/send', [MailCampaignController::class, 'send'])
        ->middleware('module:communication')
        ->name('mail-campaigns.send');
    Route::resource('mail-campaigns', MailCampaignController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middleware('module:communication');
    Route::get('letters/{letter}/pdf', [LetterController::class, 'pdf'])
        ->middleware('module:communication')
        ->name('letters.pdf');
    Route::resource('letters', LetterController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middleware('module:communication');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])
        ->middleware('module:documents')
        ->name('documents.download');
    Route::get('documents/{document}/versions/{version}', [DocumentController::class, 'downloadVersion'])
        ->middleware('module:documents')
        ->name('documents.versions.download');
    Route::patch('documents/{document}/archive', [DocumentController::class, 'archive'])
        ->middleware('module:documents')
        ->name('documents.archive');
    Route::resource('documents', DocumentController::class)
        ->except(['destroy'])
        ->middleware('module:documents');
    Route::post('billing-periods/{billing_period}/calculate', [BillingPeriodController::class, 'calculate'])
        ->middleware('module:billing')
        ->name('billing-periods.calculate');
    Route::post('billing-periods/{billing_period}/approve', [BillingPeriodController::class, 'approve'])
        ->middleware('module:billing')
        ->name('billing-periods.approve');
    Route::post('billing-periods/{billing_period}/archive', [BillingPeriodController::class, 'archive'])
        ->middleware('module:billing')
        ->name('billing-periods.archive');
    Route::resource('billing-periods', BillingPeriodController::class)
        ->except('destroy')
        ->middleware('module:billing');
    Route::get('work-hours', [WorkHourController::class, 'index'])
        ->middleware('module:work_hours')
        ->name('work-hours.index');
    Route::get('billing-periods/{billing_period}/work-hours/create', [WorkHourController::class, 'create'])
        ->middleware('module:work_hours')
        ->name('billing-periods.work-hours.create');
    Route::post('billing-periods/{billing_period}/work-hours', [WorkHourController::class, 'store'])
        ->middleware('module:work_hours')
        ->name('billing-periods.work-hours.store');
    Route::get('work-hours/{work_hour}/edit', [WorkHourController::class, 'edit'])
        ->middleware('module:work_hours')
        ->name('work-hours.edit');
    Route::put('work-hours/{work_hour}', [WorkHourController::class, 'update'])
        ->middleware('module:work_hours')
        ->name('work-hours.update');
    Route::get('work-events', [WorkEventController::class, 'index'])
        ->middleware('module:work_events')
        ->name('work-events.index');
    Route::get('billing-periods/{billing_period}/work-events/create', [WorkEventController::class, 'create'])
        ->middleware('module:work_events')
        ->name('billing-periods.work-events.create');
    Route::post('billing-periods/{billing_period}/work-events', [WorkEventController::class, 'store'])
        ->middleware('module:work_events')
        ->name('billing-periods.work-events.store');
    Route::get('work-events/{work_event}', [WorkEventController::class, 'show'])
        ->middleware('module:work_events')
        ->name('work-events.show');
    Route::get('work-events/{work_event}/edit', [WorkEventController::class, 'edit'])
        ->middleware('module:work_events')
        ->name('work-events.edit');
    Route::put('work-events/{work_event}', [WorkEventController::class, 'update'])
        ->middleware('module:work_events')
        ->name('work-events.update');
    Route::post('work-events/{work_event}/participants', [WorkEventParticipantController::class, 'store'])
        ->middleware('module:work_events')
        ->name('work-events.participants.store');
    Route::put('work-event-participants/{work_event_participant}', [WorkEventParticipantController::class, 'update'])
        ->middleware('module:work_events')
        ->name('work-event-participants.update');
    Route::get('arbeitsstundenmeldungen', [WorkHourSubmissionController::class, 'index'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.index');
    Route::get('arbeitsstunden-melden', [WorkHourSubmissionController::class, 'create'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.create');
    Route::post('arbeitsstundenmeldungen', [WorkHourSubmissionController::class, 'store'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.store');
    Route::get('arbeitsstundenmeldungen/{work_hour_submission}/foto', [WorkHourSubmissionController::class, 'photo'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.photo');
    Route::post('arbeitsstundenmeldungen/{work_hour_submission}/freigeben', [WorkHourSubmissionController::class, 'approve'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.approve');
    Route::post('arbeitsstundenmeldungen/{work_hour_submission}/ablehnen', [WorkHourSubmissionController::class, 'reject'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.reject');
    Route::resource('billing-periods.billing-rates', BillingRateController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['billing-rates' => 'billing_rate'])
        ->middleware('module:billing');
    Route::resource('billing-rate-templates', BillingRateTemplateController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])
        ->middleware('module:billing');
    Route::post('billing-rates/{billing_rate}/assignments', [BillingRateAssignmentController::class, 'store'])
        ->middleware('module:billing')
        ->name('billing-rate-assignments.store');
    Route::delete('billing-rate-assignments/{billing_rate_assignment}', [BillingRateAssignmentController::class, 'destroy'])
        ->middleware('module:billing')
        ->name('billing-rate-assignments.destroy');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->middleware('module:billing')
        ->name('invoices.pdf');
    Route::get('invoices/{invoice}/payment-reminder', [PaymentReminderController::class, 'pdf'])
        ->middleware('module:billing')
        ->name('invoices.payment-reminder');
    Route::get('invoices/{invoice}/dunning-notices/create', [DunningNoticeController::class, 'create'])
        ->middleware('module:dunning')
        ->name('invoices.dunning-notices.create');
    Route::post('invoices/{invoice}/dunning-notices', [DunningNoticeController::class, 'store'])
        ->middleware('module:dunning')
        ->name('invoices.dunning-notices.store');
    Route::get('dunning-notices/{dunning_notice}/pdf', [DunningNoticeController::class, 'pdf'])
        ->middleware('module:dunning')
        ->name('dunning-notices.pdf');
    Route::patch('dunning-notices/{dunning_notice}/cancel', [DunningNoticeController::class, 'cancel'])
        ->middleware('module:dunning')
        ->name('dunning-notices.cancel');
    Route::resource('dunning-notices', DunningNoticeController::class)
        ->only(['index', 'show'])
        ->middleware('module:dunning');
    Route::resource('invoices', InvoiceController::class)
        ->only(['index', 'show'])
        ->middleware('module:billing');
    Route::get('sepa-settings', [SepaSettingController::class, 'edit'])
        ->middleware('module:sepa')
        ->name('sepa-settings.edit');
    Route::put('sepa-settings', [SepaSettingController::class, 'update'])
        ->middleware('module:sepa')
        ->name('sepa-settings.update');
    Route::resource('sepa-mandates', SepaMandateController::class)
        ->except(['show', 'destroy'])
        ->middleware('module:sepa');
    Route::post('payment-batches/{payment_batch}/submit', [PaymentBatchController::class, 'submit'])
        ->middleware('module:sepa')
        ->name('payment-batches.submit');
    Route::post('payment-batches/{payment_batch}/settle', [PaymentBatchController::class, 'settle'])
        ->middleware('module:sepa')
        ->name('payment-batches.settle');
    Route::post('payment-batches/{payment_batch}/export', [PaymentBatchController::class, 'export'])
        ->middleware('module:sepa')
        ->name('payment-batches.export');
    Route::resource('payment-batches', PaymentBatchController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->middleware('module:sepa');
    Route::get('payment-batch-items/{payment_batch_item}/return', [PaymentReturnController::class, 'create'])
        ->middleware('module:sepa')
        ->name('payment-returns.create');
    Route::post('payment-batch-items/{payment_batch_item}/return', [PaymentReturnController::class, 'store'])
        ->middleware('module:sepa')
        ->name('payment-returns.store');
    Route::patch('members/{member}/archive', [MemberController::class, 'archive'])
        ->name('members.archive');
    Route::resource('members', MemberController::class)->except('destroy');
    Route::resource('parcels', ParcelController::class)->except('destroy');
    Route::get('meters/{meter}/replace', [MeterReplacementController::class, 'create'])
        ->middleware('module:meters')
        ->name('meters.replace');
    Route::post('meters/{meter}/replace', [MeterReplacementController::class, 'store'])
        ->middleware('module:meters')
        ->name('meters.replace.store');
    Route::resource('meters', MeterController::class)
        ->except('destroy')
        ->middleware('module:meters');
    Route::get('meter-readings/{meter_reading}/corrections/create', [MeterReadingCorrectionController::class, 'create'])
        ->middleware('module:meters')
        ->name('meter-reading-corrections.create');
    Route::post('meter-readings/{meter_reading}/corrections', [MeterReadingCorrectionController::class, 'store'])
        ->middleware('module:meters')
        ->name('meter-reading-corrections.store');
    Route::resource('meter-readings', MeterReadingController::class)
        ->only(['create', 'store'])
        ->middleware('module:meters');
    Route::resource('parcel-tenants', ParcelTenantController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->parameters(['parcel-tenants' => 'parcel_tenant']);
    Route::resource('warteliste', WaitingListEntryController::class)
        ->except(['destroy'])
        ->parameters(['warteliste' => 'waiting_list_entry'])
        ->names('waiting-list-entries')
        ->middleware('module:waiting_list');
    Route::get('inventar/{inventory_item}/ausgeben', [InventoryLoanController::class, 'create'])
        ->middleware('module:inventory')
        ->name('inventory-items.loans.create');
    Route::post('inventar/{inventory_item}/ausgaben', [InventoryLoanController::class, 'store'])
        ->middleware('module:inventory')
        ->name('inventory-items.loans.store');
    Route::get('inventar/{inventory_item}/ausgaben/{inventory_loan}/rueckgabe', [InventoryLoanController::class, 'editReturn'])
        ->middleware('module:inventory')
        ->name('inventory-items.loans.return.edit');
    Route::put('inventar/{inventory_item}/ausgaben/{inventory_loan}/rueckgabe', [InventoryLoanController::class, 'updateReturn'])
        ->middleware('module:inventory')
        ->name('inventory-items.loans.return.update');
    Route::resource('inventar', InventoryItemController::class)
        ->except(['destroy'])
        ->parameters(['inventar' => 'inventory_item'])
        ->names('inventory-items')
        ->middleware('module:inventory');
});
