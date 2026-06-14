<?php

use App\Http\Controllers\ApplicationSettingController;
use App\Http\Controllers\BillingPeriodController;
use App\Http\Controllers\BillingRateAssignmentController;
use App\Http\Controllers\BillingRateController;
use App\Http\Controllers\BillingRateTemplateController;
use App\Http\Controllers\CommunicationSettingController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\HomeController;
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
        ->name('tenant-registration.create');
    Route::post('paechter-registrierung', [TenantRegistrationController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('tenant-registration.store');
});

Route::get('freigabe/dokument/{token}', [PublicDocumentController::class, 'download'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:30,1')
    ->name('documents.public');

Route::get('/dashboard', [HomeController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('paechterportal', [TenantPortalController::class, 'index'])
        ->name('tenant-portal.index');
    Route::get('paechterportal/dokumente', [PortalDocumentController::class, 'index'])
        ->name('tenant-portal.documents');
    Route::get('paechterportal/dokumente/{document}', [PortalDocumentController::class, 'download'])
        ->name('tenant-portal.documents.download');
    Route::get('registrierungsanfragen', [RegistrationRequestController::class, 'index'])
        ->name('registration-requests.index');
    Route::get('registrierungsanfragen/{registration_request}', [RegistrationRequestController::class, 'show'])
        ->name('registration-requests.show');
    Route::post('registrierungsanfragen/{registration_request}/freigeben', [RegistrationRequestController::class, 'approve'])
        ->name('registration-requests.approve');
    Route::post('registrierungsanfragen/{registration_request}/ablehnen', [RegistrationRequestController::class, 'reject'])
        ->name('registration-requests.reject');
    Route::get('zaehlerstandsmeldungen', [MeterReadingSubmissionController::class, 'index'])
        ->name('meter-reading-submissions.index');
    Route::get('zaehler/{meter}/stand-melden', [MeterReadingSubmissionController::class, 'create'])
        ->name('meter-reading-submissions.create');
    Route::post('zaehler/{meter}/stand-melden', [MeterReadingSubmissionController::class, 'store'])
        ->name('meter-reading-submissions.store');
    Route::get('zaehlerstandsmeldungen/{meter_reading_submission}/foto', [MeterReadingSubmissionController::class, 'photo'])
        ->name('meter-reading-submissions.photo');
    Route::post('zaehlerstandsmeldungen/{meter_reading_submission}/freigeben', [MeterReadingSubmissionController::class, 'approve'])
        ->name('meter-reading-submissions.approve');
    Route::post('zaehlerstandsmeldungen/{meter_reading_submission}/ablehnen', [MeterReadingSubmissionController::class, 'reject'])
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
    Route::get('communication-settings', [CommunicationSettingController::class, 'edit'])
        ->name('communication-settings.edit');
    Route::put('communication-settings', [CommunicationSettingController::class, 'update'])
        ->name('communication-settings.update');
    Route::post('communication-settings/test', [CommunicationSettingController::class, 'test'])
        ->middleware('throttle:3,10')
        ->name('communication-settings.test');
    Route::post('mail-campaigns/{mail_campaign}/send', [MailCampaignController::class, 'send'])
        ->name('mail-campaigns.send');
    Route::resource('mail-campaigns', MailCampaignController::class)
        ->only(['index', 'create', 'store', 'show']);
    Route::get('letters/{letter}/pdf', [LetterController::class, 'pdf'])
        ->name('letters.pdf');
    Route::resource('letters', LetterController::class)
        ->only(['index', 'create', 'store', 'show']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
    Route::get('documents/{document}/versions/{version}', [DocumentController::class, 'downloadVersion'])
        ->name('documents.versions.download');
    Route::patch('documents/{document}/archive', [DocumentController::class, 'archive'])
        ->name('documents.archive');
    Route::resource('documents', DocumentController::class)
        ->except(['destroy']);
    Route::post('billing-periods/{billing_period}/calculate', [BillingPeriodController::class, 'calculate'])
        ->name('billing-periods.calculate');
    Route::post('billing-periods/{billing_period}/approve', [BillingPeriodController::class, 'approve'])
        ->name('billing-periods.approve');
    Route::post('billing-periods/{billing_period}/archive', [BillingPeriodController::class, 'archive'])
        ->name('billing-periods.archive');
    Route::resource('billing-periods', BillingPeriodController::class)->except('destroy');
    Route::resource('billing-periods.billing-rates', BillingRateController::class)
        ->only(['create', 'store', 'edit', 'update', 'destroy'])
        ->parameters(['billing-rates' => 'billing_rate']);
    Route::resource('billing-rate-templates', BillingRateTemplateController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);
    Route::post('billing-rates/{billing_rate}/assignments', [BillingRateAssignmentController::class, 'store'])
        ->name('billing-rate-assignments.store');
    Route::delete('billing-rate-assignments/{billing_rate_assignment}', [BillingRateAssignmentController::class, 'destroy'])
        ->name('billing-rate-assignments.destroy');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->name('invoices.pdf');
    Route::get('invoices/{invoice}/payment-reminder', [PaymentReminderController::class, 'pdf'])
        ->name('invoices.payment-reminder');
    Route::resource('invoices', InvoiceController::class)->only(['index', 'show']);
    Route::get('sepa-settings', [SepaSettingController::class, 'edit'])
        ->name('sepa-settings.edit');
    Route::put('sepa-settings', [SepaSettingController::class, 'update'])
        ->name('sepa-settings.update');
    Route::resource('sepa-mandates', SepaMandateController::class)
        ->except(['show', 'destroy']);
    Route::post('payment-batches/{payment_batch}/submit', [PaymentBatchController::class, 'submit'])
        ->name('payment-batches.submit');
    Route::post('payment-batches/{payment_batch}/settle', [PaymentBatchController::class, 'settle'])
        ->name('payment-batches.settle');
    Route::post('payment-batches/{payment_batch}/export', [PaymentBatchController::class, 'export'])
        ->name('payment-batches.export');
    Route::resource('payment-batches', PaymentBatchController::class)
        ->only(['index', 'create', 'store', 'show']);
    Route::get('payment-batch-items/{payment_batch_item}/return', [PaymentReturnController::class, 'create'])
        ->name('payment-returns.create');
    Route::post('payment-batch-items/{payment_batch_item}/return', [PaymentReturnController::class, 'store'])
        ->name('payment-returns.store');
    Route::patch('members/{member}/archive', [MemberController::class, 'archive'])
        ->name('members.archive');
    Route::resource('members', MemberController::class)->except('destroy');
    Route::resource('parcels', ParcelController::class)->except('destroy');
    Route::get('meters/{meter}/replace', [MeterReplacementController::class, 'create'])
        ->name('meters.replace');
    Route::post('meters/{meter}/replace', [MeterReplacementController::class, 'store'])
        ->name('meters.replace.store');
    Route::resource('meters', MeterController::class)->except('destroy');
    Route::get('meter-readings/{meter_reading}/corrections/create', [MeterReadingCorrectionController::class, 'create'])
        ->name('meter-reading-corrections.create');
    Route::post('meter-readings/{meter_reading}/corrections', [MeterReadingCorrectionController::class, 'store'])
        ->name('meter-reading-corrections.store');
    Route::resource('meter-readings', MeterReadingController::class)->only(['create', 'store']);
    Route::resource('parcel-tenants', ParcelTenantController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->parameters(['parcel-tenants' => 'parcel_tenant']);
});
