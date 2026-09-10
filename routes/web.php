<?php

use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ApplicationSettingController;
use App\Http\Controllers\AssociationLogoController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BillingPeriodController;
use App\Http\Controllers\BillingRateAssignmentController;
use App\Http\Controllers\BillingRateController;
use App\Http\Controllers\BillingRateTemplateController;
use App\Http\Controllers\BoardMeetingController;
use App\Http\Controllers\CommunicationSettingController;
use App\Http\Controllers\DataTransferController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DunningNoticeController;
use App\Http\Controllers\GardenInspectionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\InventoryLoanController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\MailCampaignController;
use App\Http\Controllers\MemberAssemblyController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MeterController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\MeterReadingCorrectionController;
use App\Http\Controllers\MeterReadingSubmissionController;
use App\Http\Controllers\MeterReplacementController;
use App\Http\Controllers\NumberSequenceController;
use App\Http\Controllers\ParcelController;
use App\Http\Controllers\ParcelMapController;
use App\Http\Controllers\ParcelTenantController;
use App\Http\Controllers\PaymentBatchController;
use App\Http\Controllers\PaymentReminderController;
use App\Http\Controllers\PaymentReturnController;
use App\Http\Controllers\PermissionProfileController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PortalDocumentController;
use App\Http\Controllers\PortalSepaMandateController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\PrivacyErasureRequestController;
use App\Http\Controllers\PublicDocumentController;
use App\Http\Controllers\RegistrationRequestController;
use App\Http\Controllers\SepaMandateController;
use App\Http\Controllers\SepaSettingController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TenantPortalController;
use App\Http\Controllers\TenantRegistrationController;
use App\Http\Controllers\TenantTransitionController;
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
    Route::get('tenant-registration', [TenantRegistrationController::class, 'create'])
        ->middleware('module:tenant_portal')
        ->name('tenant-registration.create');
    Route::post('tenant-registration', [TenantRegistrationController::class, 'store'])
        ->middleware(['module:tenant_portal', 'throttle:5,10'])
        ->name('tenant-registration.store');
});

Route::get('shared-documents/{token}', [PublicDocumentController::class, 'download'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware(['module:documents', 'throttle:30,1'])
    ->name('documents.public');

Route::view('privacy-information', 'privacy.information')
    ->name('privacy.information');

Route::middleware('module:announcements')->group(function (): void {
    Route::get('public-announcements', [AnnouncementController::class, 'index'])->name('announcements.public.index');
    Route::get('public-announcements/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.public.show');
    Route::get('public-announcements/{announcement}/documents/{document}', [AnnouncementController::class, 'document'])
        ->middleware(['module:documents', 'throttle:30,1'])->name('announcements.public.document');
});
Route::get('vereinslogo', [AssociationLogoController::class, 'show'])
    ->name('association-logo.show');

Route::get('/dashboard', [HomeController::class, 'index'])
    ->middleware(['auth', 'verified', 'registration.approved'])
    ->name('home');

Route::view('account/pending-approval', 'auth.pending-approval')
    ->middleware(['auth', 'verified'])
    ->name('registration.pending');

Route::middleware(['auth', 'verified', 'registration.approved'])->group(function (): void {
    Route::middleware('module:garden_inspections')->group(function (): void {
        Route::resource('garden-inspections', GardenInspectionController::class)->only(['index', 'create', 'store', 'show'])->parameters(['garden-inspections' => 'inspection'])->names('garden-inspections');
        Route::post('garden-inspections/{inspection}/findings', [GardenInspectionController::class, 'finding'])->name('garden-inspections.findings.store');
        Route::post('garden-inspection-findings/{finding}/resolve', [GardenInspectionController::class, 'resolve'])->name('garden-inspection-findings.resolve');
        Route::get('garden-inspection-findings/{finding}/photo', [GardenInspectionController::class, 'photo'])->name('garden-inspection-findings.photo');
        Route::post('garden-inspections/{inspection}/finalize', [GardenInspectionController::class, 'finalize'])->name('garden-inspections.finalize');
        Route::get('garden-inspections/{inspection}/pdf', [GardenInspectionController::class, 'pdf'])->name('garden-inspections.pdf');
    });
    Route::middleware('module:member_assemblies')->group(function (): void {
        Route::resource('member-assemblies', MemberAssemblyController::class)->only(['index', 'create', 'store', 'show'])->parameters(['member-assemblies' => 'assembly'])->names('member-assemblies');
        Route::post('member-assemblies/{assembly}/attend', [MemberAssemblyController::class, 'attend'])->name('member-assemblies.attend');
        Route::post('member-assemblies/{assembly}/vote', [MemberAssemblyController::class, 'vote'])->name('member-assemblies.vote');
        Route::post('member-assemblies/{assembly}/publish', [MemberAssemblyController::class, 'publish'])->name('member-assemblies.publish');
        Route::post('member-assemblies/{assembly}/finalize', [MemberAssemblyController::class, 'finalize'])->name('member-assemblies.finalize');
    });
    Route::middleware('module:polls')->group(function (): void {
        Route::resource('polls', PollController::class)->except('destroy')->parameters(['polls' => 'poll'])->names('polls');
        foreach (['publish', 'close', 'archive', 'restore'] as $action) {
            Route::post('polls/{poll}/'.$action, [PollController::class, 'transition'])->defaults('action', $action)->name('polls.'.$action);
        }
        Route::post('polls/{poll}/answer', [PollController::class, 'answer'])->middleware('throttle:30,1')->name('polls.answer');
        Route::get('polls/{poll}/export', [PollController::class, 'export'])->name('polls.export');
    });
    Route::middleware('module:tasks')->group(function (): void {
        Route::resource('tasks', TaskController::class)->except('destroy')->parameters(['tasks' => 'task'])->names('tasks');
        foreach (['start', 'complete', 'cancel', 'archive', 'restore'] as $action) {
            Route::post('tasks/{task}/'.$action, [TaskController::class, 'action'])->name('tasks.'.$action);
        }
        Route::get('tasks/{task}/document', [TaskController::class, 'document'])->middleware('module:documents')->name('tasks.document');
    });
    Route::middleware('module:board_work')->group(function (): void {
        Route::get('board-resolutions', [BoardMeetingController::class, 'book'])->name('board-meetings.book');
        Route::resource('board-meetings', BoardMeetingController::class)->except('destroy')->parameters(['board-meetings' => 'meeting'])->names('board-meetings');
        Route::post('board-meetings/{meeting}/finalize', [BoardMeetingController::class, 'finalize'])->name('board-meetings.finalize');
        Route::post('board-meetings/{meeting}/archive', [BoardMeetingController::class, 'archive'])->name('board-meetings.archive');
        Route::post('board-meetings/{meeting}/restore', [BoardMeetingController::class, 'unarchive'])->name('board-meetings.unarchive');
        Route::get('board-meetings/{meeting}/pdf', [BoardMeetingController::class, 'pdf'])->name('board-meetings.pdf');
        Route::get('board-meetings/{meeting}/documents/{document}', [BoardMeetingController::class, 'document'])->middleware('module:documents')->name('board-meetings.document');
        Route::get('board-meetings/{meeting}/agenda/create', [BoardMeetingController::class, 'agendaForm'])->name('board-agenda.create');
        Route::get('board-meetings/{meeting}/agenda/{item}/edit', [BoardMeetingController::class, 'agendaForm'])->name('board-agenda.edit');
        Route::post('board-meetings/{meeting}/agenda', [BoardMeetingController::class, 'saveAgenda'])->name('board-agenda.store');
        Route::put('board-meetings/{meeting}/agenda/{item}', [BoardMeetingController::class, 'saveAgenda'])->name('board-agenda.update');
        Route::get('board-meetings/{meeting}/resolutions/create', [BoardMeetingController::class, 'resolutionForm'])->name('board-resolutions.create');
        Route::get('board-meetings/{meeting}/resolutions/{resolution}/edit', [BoardMeetingController::class, 'resolutionForm'])->name('board-resolutions.edit');
        Route::post('board-meetings/{meeting}/resolutions', [BoardMeetingController::class, 'saveResolution'])->name('board-resolutions.store');
        Route::put('board-meetings/{meeting}/resolutions/{resolution}', [BoardMeetingController::class, 'saveResolution'])->name('board-resolutions.update');
        Route::get('board-resolutions/{resolution}/task', [BoardMeetingController::class, 'followUpForm'])->name('board-follow-ups.create');
        Route::post('board-resolutions/{resolution}/task', [BoardMeetingController::class, 'addFollowUp'])->name('board-follow-ups.store');
        Route::post('board-follow-ups/{task}/complete', [BoardMeetingController::class, 'complete'])->name('board-follow-ups.complete');
    });
    Route::middleware('module:announcements')->group(function (): void {
        Route::resource('announcements', AnnouncementController::class)->except('destroy')->parameters(['announcements' => 'announcement'])->names('announcements');
        Route::post('announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
        Route::post('announcements/{announcement}/archive', [AnnouncementController::class, 'archive'])->name('announcements.archive');
        Route::post('announcements/{announcement}/acknowledge', [AnnouncementController::class, 'acknowledge'])
            ->middleware('throttle:60,1')->name('announcements.acknowledge');
        Route::get('announcements/{announcement}/documents/{document}', [AnnouncementController::class, 'document'])
            ->middleware(['module:documents', 'throttle:30,1'])->name('announcements.document');
    });
    Route::get('account/password', [AccountPasswordController::class, 'edit'])
        ->name('account.password.edit');
    Route::put('account/password', [AccountPasswordController::class, 'update'])
        ->name('account.password.update');
    Route::get('privacy', [PrivacyController::class, 'index'])
        ->name('privacy.index');
    Route::put('privacy/sharing', [PrivacyController::class, 'update'])
        ->name('privacy.settings.update');
    Route::get('privacy/export/{member}', [PrivacyController::class, 'export'])
        ->middleware('throttle:10,1')
        ->name('privacy.export');
    Route::post('privacy/erasure-requests', [PrivacyErasureRequestController::class, 'store'])
        ->middleware('throttle:5,10')
        ->name('privacy-erasure-requests.store');
    Route::post('privacy/erasure-requests/{privacy_erasure_request}/review', [PrivacyErasureRequestController::class, 'review'])
        ->name('privacy-erasure-requests.review');
    Route::post('privacy/erasure-requests/{privacy_erasure_request}/anonymize', [PrivacyErasureRequestController::class, 'anonymize'])
        ->middleware('throttle:3,10')
        ->name('privacy-erasure-requests.anonymize');
    Route::get('tenant-portal', [TenantPortalController::class, 'index'])
        ->middleware('module:tenant_portal')
        ->name('tenant-portal.index');
    Route::get('tenant-portal/documents', [PortalDocumentController::class, 'index'])
        ->middleware(['module:tenant_portal', 'module:documents'])
        ->name('tenant-portal.documents');
    Route::get('tenant-portal/documents/{document}', [PortalDocumentController::class, 'download'])
        ->middleware(['module:tenant_portal', 'module:documents'])
        ->name('tenant-portal.documents.download');
    Route::get('tenant-portal/sepa-mandates', [PortalSepaMandateController::class, 'index'])
        ->middleware(['module:tenant_portal', 'module:sepa'])
        ->name('tenant-portal.sepa-mandates.index');
    Route::get('tenant-portal/sepa-mandates/create', [PortalSepaMandateController::class, 'create'])
        ->middleware(['module:tenant_portal', 'module:sepa'])
        ->name('tenant-portal.sepa-mandates.create');
    Route::post('tenant-portal/sepa-mandates', [PortalSepaMandateController::class, 'store'])
        ->middleware(['module:tenant_portal', 'module:sepa', 'throttle:5,10'])
        ->name('tenant-portal.sepa-mandates.store');
    Route::post('tenant-portal/sepa-mandates/{sepaMandate}/revoke', [PortalSepaMandateController::class, 'revoke'])
        ->middleware(['module:tenant_portal', 'module:sepa', 'throttle:5,10'])
        ->name('tenant-portal.sepa-mandates.revoke');
    Route::get('registration-requests', [RegistrationRequestController::class, 'index'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.index');
    Route::get('registration-requests/{registration_request}', [RegistrationRequestController::class, 'show'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.show');
    Route::post('registration-requests/{registration_request}/approve', [RegistrationRequestController::class, 'approve'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.approve');
    Route::post('registration-requests/{registration_request}/link-account', [RegistrationRequestController::class, 'linkAccount'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.link-account');
    Route::post('registration-requests/{registration_request}/create-member', [RegistrationRequestController::class, 'createMember'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.create-member');
    Route::post('registration-requests/{registration_request}/link-member', [RegistrationRequestController::class, 'linkMember'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.link-member');
    Route::post('registration-requests/{registration_request}/reject', [RegistrationRequestController::class, 'reject'])
        ->middleware('module:tenant_portal')
        ->name('registration-requests.reject');
    Route::get('meter-reading-submissions', [MeterReadingSubmissionController::class, 'index'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.index');
    Route::get('meters/{meter}/report-reading', [MeterReadingSubmissionController::class, 'create'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.create');
    Route::post('meters/{meter}/report-reading', [MeterReadingSubmissionController::class, 'store'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.store');
    Route::get('meter-reading-submissions/{meter_reading_submission}/photo', [MeterReadingSubmissionController::class, 'photo'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.photo');
    Route::post('meter-reading-submissions/{meter_reading_submission}/approve', [MeterReadingSubmissionController::class, 'approve'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.approve');
    Route::post('meter-reading-submissions/{meter_reading_submission}/reject', [MeterReadingSubmissionController::class, 'reject'])
        ->middleware('module:meters')
        ->name('meter-reading-submissions.reject');
    Route::get('user-permissions', [UserPermissionController::class, 'index'])
        ->name('user-permissions.index');
    Route::put('user-permissions/{user}', [UserPermissionController::class, 'update'])
        ->name('user-permissions.update');
    Route::get('application-settings', [ApplicationSettingController::class, 'edit'])
        ->name('application-settings.edit');
    Route::put('application-settings', [ApplicationSettingController::class, 'update'])
        ->name('application-settings.update');
    Route::get('number-sequences', [NumberSequenceController::class, 'edit'])
        ->name('number-sequences.edit');
    Route::put('number-sequences', [NumberSequenceController::class, 'update'])
        ->name('number-sequences.update');
    Route::resource('permission-profiles', PermissionProfileController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);
    Route::put('application-settings/smtp', [CommunicationSettingController::class, 'update'])
        ->name('communication-settings.update');
    Route::post('application-settings/smtp/test', [CommunicationSettingController::class, 'test'])
        ->middleware('throttle:smtp-tests')
        ->name('communication-settings.test');
    Route::get('data-transfer', [DataTransferController::class, 'index'])
        ->middleware('module:data_transfer')
        ->name('data-transfer.index');
    Route::post('data-transfer/app-key', [DataTransferController::class, 'revealAppKey'])
        ->middleware(['module:data_transfer', 'throttle:3,10'])
        ->name('data-transfer.app-key');
    Route::post('data-transfer/import', [DataTransferController::class, 'import'])
        ->middleware(['module:data_transfer', 'throttle:10,1'])
        ->name('data-transfer.import');
    Route::get('data-transfer/export/{type}', [DataTransferController::class, 'export'])
        ->middleware(['module:data_transfer', 'throttle:30,1'])
        ->name('data-transfer.export');
    Route::get('data-transfer/template/{type}', [DataTransferController::class, 'template'])
        ->middleware(['module:data_transfer', 'throttle:30,1'])
        ->name('data-transfer.template');
    Route::post('data-transfer/backups', [BackupController::class, 'create'])
        ->middleware(['module:data_transfer', 'throttle:3,10'])
        ->name('backups.create');
    Route::get('data-transfer/backups/{backup}', [BackupController::class, 'download'])
        ->middleware(['module:data_transfer', 'throttle:10,1'])
        ->name('backups.download');
    Route::delete('data-transfer/backups/{backup}', [BackupController::class, 'destroy'])
        ->middleware('module:data_transfer')
        ->name('backups.destroy');
    Route::post('data-transfer/restore', [BackupController::class, 'restore'])
        ->middleware(['module:data_transfer', 'throttle:2,60'])
        ->name('backups.restore');
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
    Route::get('work-hour-submissions', [WorkHourSubmissionController::class, 'index'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.index');
    Route::get('work-hour-submissions/create', [WorkHourSubmissionController::class, 'create'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.create');
    Route::post('work-hour-submissions', [WorkHourSubmissionController::class, 'store'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.store');
    Route::get('work-hour-submissions/{work_hour_submission}/photo', [WorkHourSubmissionController::class, 'photo'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.photo');
    Route::post('work-hour-submissions/{work_hour_submission}/approve', [WorkHourSubmissionController::class, 'approve'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.approve');
    Route::post('work-hour-submissions/{work_hour_submission}/reject', [WorkHourSubmissionController::class, 'reject'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.reject');
    Route::post('work-hour-submissions/{work_hour_submission}/acknowledge', [WorkHourSubmissionController::class, 'acknowledge'])
        ->middleware('module:work_hours')
        ->name('work-hour-submissions.acknowledge');
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
    Route::get('parcel-map', [ParcelMapController::class, 'index'])
        ->name('parcel-map.index');
    Route::get('parcel-map/background', [ParcelMapController::class, 'background'])
        ->name('parcel-map.background');
    Route::get('parcel-map/edit', [ParcelMapController::class, 'edit'])
        ->name('parcel-map.edit');
    Route::put('parcel-map/background', [ParcelMapController::class, 'updateBackground'])
        ->name('parcel-map.background.update');
    Route::put('parcel-map/parcels/{parcel}', [ParcelMapController::class, 'updatePolygon'])
        ->name('parcel-map.polygon.update');
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
    Route::get('tenant-transitions/{tenant_transition}/documents/{document}', [TenantTransitionController::class, 'document'])
        ->name('tenant-transitions.documents.download');
    Route::resource('tenant-transitions', TenantTransitionController::class)
        ->only(['index', 'create', 'store', 'show'])
        ->parameters(['tenant-transitions' => 'tenant_transition'])
        ->names('tenant-transitions');
    Route::resource('waiting-list', WaitingListEntryController::class)
        ->except(['destroy'])
        ->parameters(['waiting-list' => 'waiting_list_entry'])
        ->names('waiting-list-entries')
        ->middleware('module:waiting_list');
    Route::get('inventory/{inventory_item}/loans/create', [InventoryLoanController::class, 'create'])
        ->middleware('module:inventory')
        ->name('inventory-items.loans.create');
    Route::post('inventory/{inventory_item}/loans', [InventoryLoanController::class, 'store'])
        ->middleware('module:inventory')
        ->name('inventory-items.loans.store');
    Route::get('inventory/{inventory_item}/loans/{inventory_loan}/return', [InventoryLoanController::class, 'editReturn'])
        ->middleware('module:inventory')
        ->name('inventory-items.loans.return.edit');
    Route::put('inventory/{inventory_item}/loans/{inventory_loan}/return', [InventoryLoanController::class, 'updateReturn'])
        ->middleware('module:inventory')
        ->name('inventory-items.loans.return.update');
    Route::resource('inventory', InventoryItemController::class)
        ->except(['destroy'])
        ->parameters(['inventory' => 'inventory_item'])
        ->names('inventory-items')
        ->middleware('module:inventory');
});
