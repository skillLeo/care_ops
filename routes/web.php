<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceController2026;
use App\Http\Controllers\CprsController;
use App\Http\Controllers\AuthLineOfServiceController;
use App\Http\Controllers\AuthorizationController;
use App\Http\Controllers\AuthToReleaseInfoController;
use App\Http\Controllers\BookingSlotController;
use App\Http\Controllers\BookingWindowController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuditLogController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\CheckController;
use App\Http\Controllers\CheckBatchController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ClaimLineOfServiceController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateTypeController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\GroupNoteController;
use App\Http\Controllers\LevelOfCareController;
use App\Http\Controllers\MedicalContactController;
use App\Http\Controllers\HospitalizationFacilityController;
use App\Http\Controllers\HospitalizationController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PreBioInterviewController;
use App\Http\Controllers\ServiceCodeController;
use App\Http\Controllers\VerificationLetterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DropboxController;
use App\Http\Controllers\DropboxSubmissionController;
use App\Http\Controllers\ClientGroupController;
use App\Http\Controllers\PeerGroupController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\SubTaskTemplateController;
use App\Http\Controllers\SubTaskController;
use App\Http\Controllers\TaskCategoryController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskTemplateController;
use App\Http\Controllers\ChartAuditRandomizerController;
use App\Http\Controllers\ClinicalNotesController;
use App\Http\Controllers\SubmissionDuplicateController;
use App\Http\Controllers\UaRandomizerController;

Route::get('intake', [DropboxSubmissionController::class, 'showIntake'])->name('dropbox.intake');

Route::get('dropbox/intake/{type?}', function (?string $type = null) {
    $params = [];

    if (in_array($type, ['individual', 'agency'], true)) {
        $params['type'] = $type;
    }

    return redirect()->route('dropbox.intake', $params);
});

Route::get('dropbox/individual', function () {
    return redirect()->route('dropbox.intake', ['type' => 'individual']);
})->name('dropbox.individual');
Route::post('dropbox/individual', [DropboxSubmissionController::class, 'submitIndividual'])->name('dropbox.individual.submit');
Route::get('dropbox/agency', function () {
    return redirect()->route('dropbox.intake', ['type' => 'agency']);
})->name('dropbox.agency');
Route::post('dropbox/agency', [DropboxSubmissionController::class, 'submitAgency'])->name('dropbox.agency.submit');
Route::post('dropbox/roi-preview', [DropboxSubmissionController::class, 'previewRoi'])->name('dropbox.roi.preview');
Route::get('dropbox/thank-you', [DropboxSubmissionController::class, 'thankYou'])->name('dropbox.thank-you');

Auth::routes([
    'register' => false,   // Disable registration routes
    'reset' => false,      // Disable password reset routes
    'verify' => false,     // Optional: Disable email verification if not needed
]);


Route::middleware(['auth'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    Route::get('roles', [RoleController::class, 'index'])
        ->middleware('can:role.view')
        ->name('roles.index');
    Route::get('roles/create', [RoleController::class, 'create'])
        ->middleware('can:role.create')
        ->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])
        ->middleware('can:role.create')
        ->name('roles.store');
    Route::get('roles/{role}', [RoleController::class, 'show'])
        ->middleware('can:role.view')
        ->name('roles.show');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('can:role.edit')
        ->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])
        ->middleware('can:role.edit')
        ->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])
        ->middleware('can:role.delete')
        ->name('roles.destroy');
    Route::post('/roles/{role}/add-users', [RoleController::class, 'addUsers'])
        ->middleware('can:role.assign_users')
        ->name('roles.addUsers');

    Route::get('users', [UserController::class, 'index'])
        ->middleware('can:user.list')
        ->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])
        ->middleware('can:user.create')
        ->name('users.create');
    Route::post('users', [UserController::class, 'store'])
        ->middleware('can:user.create')
        ->name('users.store');
    Route::get('users/{id}', [UserController::class, 'show'])
        ->middleware('can:user.view')
        ->name('users.show');
    Route::get('users/{id}/edit', [UserController::class, 'edit'])
        ->middleware('can:user.edit')
        ->name('users.edit');
    Route::put('users/{id}', [UserController::class, 'update'])
        ->middleware('can:user.edit')
        ->name('users.update');
    Route::delete('users/{id}', [UserController::class, 'destroy'])
        ->middleware('can:user.delete')
        ->name('users.destroy');
    Route::get('users/{id}/reset-password', [UserController::class, 'resetPasswordForm'])
        ->middleware('can:user.reset_password')
        ->name('users.reset_password_form');
    Route::put('users/{id}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('can:user.reset_password')
        ->name('users.reset_password');
    Route::get('pin', [PinController::class, 'edit'])
        ->middleware('can:user.reset_pin')
        ->name('pin.edit');
    Route::put('pin', [PinController::class, 'update'])
        ->middleware('can:user.reset_pin')
        ->name('pin.update');

    Route::get('email-templates', [EmailTemplateController::class, 'index'])
        ->middleware('can:email_template.manage')
        ->name('email-templates.index');
    Route::get('email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])
        ->middleware('can:email_template.manage')
        ->name('email-templates.edit');
    Route::put('email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])
        ->middleware('can:email_template.manage')
        ->name('email-templates.update');
    Route::post('email-templates/clinical-notes-tracker/test', [EmailTemplateController::class, 'sendClinicalNoteTrackerTest'])
        ->middleware('can:email_template.manage')
        ->name('email-templates.clinical-notes-tracker.test');
    Route::post('email-templates/clinical-notes-tracker/send-now', [EmailTemplateController::class, 'sendClinicalNoteTrackerNow'])
        ->middleware('can:email_template.manage')
        ->name('email-templates.clinical-notes-tracker.send-now');
    Route::get('users/{user}/reset-pin', [PinController::class, 'resetForm'])
        ->middleware('can:user.reset_pin')
        ->name('users.reset_pin_form');
    Route::put('users/{user}/reset-pin', [PinController::class, 'reset'])
        ->middleware('can:user.reset_pin')
        ->name('users.reset_pin');


    Route::resource('level-of-cares', LevelOfCareController::class)->middleware([
        'index' => 'can:level_of_care.view',
        'show' => 'can:level_of_care.view',
        'create' => 'can:level_of_care.create',
        'store' => 'can:level_of_care.create',
        'edit' => 'can:level_of_care.edit',
        'update' => 'can:level_of_care.edit',
        'destroy' => 'can:level_of_care.delete',
    ]);

    Route::resource('service-codes', ServiceCodeController::class)->middleware([
        'index' => 'can:service_code.view',
        'show' => 'can:service_code.view',
        'create' => 'can:service_code.create',
        'store' => 'can:service_code.create',
        'edit' => 'can:service_code.edit',
        'update' => 'can:service_code.edit',
        'destroy' => 'can:service_code.delete',
    ]);

    Route::resource('positions', PositionController::class)->only(['index', 'store', 'edit', 'update', 'destroy'])->middleware([
        'index' => 'can:position.view',
        'store' => 'can:position.create',
        'edit' => 'can:position.edit',
        'update' => 'can:position.edit',
        'destroy' => 'can:position.delete',
    ]);

    Route::resource('task-categories', TaskCategoryController::class)->only(['index', 'store', 'edit', 'update', 'destroy'])->middleware([
        'index' => 'can:task_category.view',
        'store' => 'can:task_category.create',
        'edit' => 'can:task_category.edit',
        'update' => 'can:task_category.edit',
        'destroy' => 'can:task_category.delete',
    ]);

    Route::resource('task-templates', TaskTemplateController::class)->except(['show'])->middleware([
        'index' => 'can:task_template.view',
        'create' => 'can:task_template.create',
        'store' => 'can:task_template.create',
        'edit' => 'can:task_template.edit',
        'update' => 'can:task_template.edit',
        'destroy' => 'can:task_template.delete',
    ]);

    Route::resource('sub-task-templates', SubTaskTemplateController::class)->except(['show'])->middleware([
        'index' => 'can:sub_task_template.view',
        'create' => 'can:sub_task_template.create',
        'store' => 'can:sub_task_template.create',
        'edit' => 'can:sub_task_template.edit',
        'update' => 'can:sub_task_template.edit',
        'destroy' => 'can:sub_task_template.delete',
    ]);

    Route::get('ua-randomizers', [UaRandomizerController::class, 'index'])
        ->middleware('can:ua_randomizer.view')
        ->name('ua-randomizers.index');
    Route::get('ua-randomizers/create', [UaRandomizerController::class, 'create'])
        ->middleware('can:ua_randomizer.create')
        ->name('ua-randomizers.create');
    Route::post('ua-randomizers', [UaRandomizerController::class, 'store'])
        ->middleware('can:ua_randomizer.create')
        ->name('ua-randomizers.store');
    Route::get('ua-randomizers/{uaRandomizer}/edit', [UaRandomizerController::class, 'edit'])
        ->middleware('can:ua_randomizer.edit')
        ->name('ua-randomizers.edit');
    Route::put('ua-randomizers/{uaRandomizer}', [UaRandomizerController::class, 'update'])
        ->middleware('can:ua_randomizer.edit')
        ->name('ua-randomizers.update');
    Route::delete('ua-randomizers/{uaRandomizer}', [UaRandomizerController::class, 'destroy'])
        ->middleware('can:ua_randomizer.delete')
        ->name('ua-randomizers.destroy');
    Route::get('ua-randomizers/{uaRandomizer}', [UaRandomizerController::class, 'show'])
        ->middleware('can:ua_randomizer.view')
        ->whereNumber('uaRandomizer')
        ->name('ua-randomizers.show');
    Route::get('ua-randomizers/{uaRandomizer}/pdf', [UaRandomizerController::class, 'viewPdf'])
        ->middleware('can:ua_randomizer.view')
        ->whereNumber('uaRandomizer')
        ->name('ua-randomizers.pdf');
    Route::get('ua-randomizers/{uaRandomizer}/download', [UaRandomizerController::class, 'download'])
        ->middleware('can:ua_randomizer.view')
        ->whereNumber('uaRandomizer')
        ->name('ua-randomizers.download');
    Route::post('ua-randomizers/filter', [UaRandomizerController::class, 'filter'])
        ->middleware('can:ua_randomizer.view')
        ->name('ua-randomizers.filter');

    Route::get('chart-audit-randomizers', [ChartAuditRandomizerController::class, 'index'])
        ->middleware('can:chart_audit_randomizer.view')
        ->name('chart-audit-randomizers.index');
    Route::get('chart-audit-randomizers/create', [ChartAuditRandomizerController::class, 'create'])
        ->middleware('can:chart_audit_randomizer.create')
        ->name('chart-audit-randomizers.create');
    Route::post('chart-audit-randomizers', [ChartAuditRandomizerController::class, 'store'])
        ->middleware('can:chart_audit_randomizer.create')
        ->name('chart-audit-randomizers.store');
    Route::get('chart-audit-randomizers/{chartAuditRandomizer}/edit', [ChartAuditRandomizerController::class, 'edit'])
        ->middleware('can:chart_audit_randomizer.edit')
        ->name('chart-audit-randomizers.edit');
    Route::put('chart-audit-randomizers/{chartAuditRandomizer}', [ChartAuditRandomizerController::class, 'update'])
        ->middleware('can:chart_audit_randomizer.edit')
        ->name('chart-audit-randomizers.update');
    Route::delete('chart-audit-randomizers/{chartAuditRandomizer}', [ChartAuditRandomizerController::class, 'destroy'])
        ->middleware('can:chart_audit_randomizer.delete')
        ->name('chart-audit-randomizers.destroy');
    Route::get('chart-audit-randomizers/{chartAuditRandomizer}', [ChartAuditRandomizerController::class, 'show'])
        ->middleware('can:chart_audit_randomizer.view')
        ->whereNumber('chartAuditRandomizer')
        ->name('chart-audit-randomizers.show');
    Route::get('chart-audit-randomizers/{chartAuditRandomizer}/pdf', [ChartAuditRandomizerController::class, 'viewPdf'])
        ->middleware('can:chart_audit_randomizer.view')
        ->whereNumber('chartAuditRandomizer')
        ->name('chart-audit-randomizers.pdf');
    Route::get('chart-audit-randomizers/{chartAuditRandomizer}/download', [ChartAuditRandomizerController::class, 'download'])
        ->middleware('can:chart_audit_randomizer.view')
        ->whereNumber('chartAuditRandomizer')
        ->name('chart-audit-randomizers.download');
    Route::post('chart-audit-randomizers/filter', [ChartAuditRandomizerController::class, 'filter'])
        ->middleware('can:chart_audit_randomizer.view')
        ->name('chart-audit-randomizers.filter');

    Route::get('tasks', [TaskController::class, 'index'])
        ->middleware('can:task.view')
        ->name('tasks.index');
    Route::get('tasks/assigned', [TaskController::class, 'assigned'])
        ->middleware('can:task.view')
        ->name('tasks.assigned');
    Route::get('tasks/list', [TaskController::class, 'list'])
        ->middleware('can:task.view')
        ->name('tasks.list');
    Route::get('tasks/{task}', [TaskController::class, 'show'])
        ->middleware('can:task.view')
        ->name('tasks.show');
    Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
    Route::post('tasks/{task}/cancel', [TaskController::class, 'cancel'])->name('tasks.cancel');
    Route::post('tasks/{task}/reassign', [TaskController::class, 'reassign'])
        ->middleware('can:task.view')
        ->name('tasks.reassign');
    Route::post('sub-tasks/{subTask}/complete', [SubTaskController::class, 'complete'])->name('sub-tasks.complete');
    Route::post('sub-tasks/{subTask}/cancel', [SubTaskController::class, 'cancel'])->name('sub-tasks.cancel');

    Route::get('documents', [DocumentController::class, 'index'])
        ->name('documents.index');
    Route::get('documents/create', [DocumentController::class, 'create'])
        ->middleware('can:document.upload')
        ->name('documents.create');
    Route::get('documents/{document}', [DocumentController::class, 'show'])
        ->name('documents.show');
    Route::get('documents/{document}/edit', [DocumentController::class, 'edit'])
        ->middleware('can:document.upload')
        ->name('documents.edit');
    Route::post('documents', [DocumentController::class, 'store'])
        ->middleware('can:document.upload')
        ->name('documents.store');
    Route::put('documents/{document}', [DocumentController::class, 'update'])
        ->middleware('can:document.upload')
        ->name('documents.update');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])
        ->name('documents.download');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])
        ->middleware('can:document.delete')
        ->name('documents.destroy');

    Route::resource('certificates', CertificateController::class)->except(['show', 'destroy'])->middleware([
        'index' => 'can:certificate.view',
        'create' => 'can:certificate.create',
        'store' => 'can:certificate.create',
        'edit' => 'can:certificate.edit',
        'update' => 'can:certificate.edit',
    ]);
    Route::get('certificates/{certificate}/download', [CertificateController::class, 'download'])
        ->middleware('can:certificate.download')
        ->name('certificates.download');
    Route::post('certificates/bulk-download', [CertificateController::class, 'bulkDownload'])
        ->middleware('can:certificate.download')
        ->name('certificates.bulk-download');
    Route::post('certificates/{certificate}/regenerate', [CertificateController::class, 'regenerate'])
        ->middleware('can:certificate.regenerate')
        ->name('certificates.regenerate');
    Route::delete('certificates/{certificate}', [CertificateController::class, 'destroy'])
        ->middleware('can:certificate.delete')
        ->name('certificates.destroy');

    Route::resource('certificate-types', CertificateTypeController::class)->except(['show'])->middleware([
        'index' => 'can:certificate-type.view',
        'create' => 'can:certificate-type.create',
        'store' => 'can:certificate-type.create',
        'edit' => 'can:certificate-type.edit',
        'update' => 'can:certificate-type.edit',
        'destroy' => 'can:certificate-type.delete',
    ]);
    Route::get('certificate-types/{certificateType}/preview', [CertificateTypeController::class, 'preview'])
        ->middleware('can:certificate-type.view')
        ->name('certificate-types.preview');
    Route::get('certificate-types/{certificateType}/preview-pdf', [CertificateTypeController::class, 'previewPdf'])
        ->middleware('can:certificate-type.view')
        ->name('certificate-types.preview-pdf');

    Route::get('booking-windows', [BookingWindowController::class, 'index'])
        ->middleware('can:booking_window.view')
        ->name('booking-windows.index');
    Route::get('booking-windows/create', [BookingWindowController::class, 'create'])
        ->middleware('can:booking_window.create')
        ->name('booking-windows.create');
    Route::post('booking-windows', [BookingWindowController::class, 'store'])
        ->middleware('can:booking_window.create')
        ->name('booking-windows.store');
    Route::get('booking-windows/{bookingWindow}', [BookingWindowController::class, 'show'])
        ->middleware('can:booking_window.view')
        ->name('booking-windows.show');
    Route::get('booking-windows/{bookingWindow}/download', [BookingWindowController::class, 'download'])
        ->middleware('can:booking_window.view')
        ->name('booking-windows.download');
    Route::get('booking-windows/{bookingWindow}/edit', [BookingWindowController::class, 'edit'])
        ->middleware('can:booking_window.edit')
        ->name('booking-windows.edit');
    Route::put('booking-windows/{bookingWindow}', [BookingWindowController::class, 'update'])
        ->middleware('can:booking_window.edit')
        ->name('booking-windows.update');
    Route::delete('booking-windows/{bookingWindow}', [BookingWindowController::class, 'destroy'])
        ->middleware('can:booking_window.delete')
        ->name('booking-windows.destroy');

    Route::get('bookings', [BookingSlotController::class, 'index'])
        ->middleware('can:booking_slot.view')
        ->name('bookings.index');
    Route::post('booking-slots/{bookingSlot}/book', [BookingSlotController::class, 'book'])
        ->middleware('can:booking_slot.book')
        ->name('booking-slots.book');
    Route::post('booking-slots/{bookingSlot}/cancel', [BookingSlotController::class, 'cancel'])
        ->name('booking-slots.cancel');

    Route::get('/clients', [ClientController::class, 'index'])
        ->middleware('can:client.view')
        ->name('clients.index');
    Route::get('/clients/create', [ClientController::class, 'create'])
        ->middleware('can:client.create')
        ->name('clients.create');
    Route::post('/clients', [ClientController::class, 'store'])
        ->middleware('can:client.create')
        ->name('clients.store');
    Route::get('/clients/{client}', [ClientController::class, 'show'])
        ->middleware('can:client.view')
        ->name('clients.show');
    Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])
        ->middleware('can:client.edit')
        ->name('clients.edit');
    Route::put('/clients/{client}', [ClientController::class, 'update'])
        ->middleware('can:client.edit')
        ->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])
        ->middleware('can:client.delete')
        ->name('clients.destroy');
    Route::get('/clients/{client}/pdf', [ClientController::class, 'downloadPdf'])
        ->middleware('can:client.download_pdf')
        ->name('clients.pdf');
    Route::post('/clients/{id}/assign-program', [ClientController::class, 'assignProgram'])
        ->middleware('can:client.assign_program')
        ->name('clients.assignProgram');
    Route::delete('/clients/{clientId}/remove-program/{programId}', [ClientController::class, 'removeProgram'])
        ->middleware('can:client.remove_program')
        ->name('clients.removeProgram');
    Route::get('/clients/photo/{clientId}', [ClientController::class, 'photo'])
        ->middleware('can:client.view')
        ->name('client.photo');
    Route::get('/clients/{client}/calendar', [ClientController::class, 'showCalendar'])
        ->middleware('can:client.view_calendar')
        ->name('clients.calendar');
    Route::get('/clients/{client}/calendar-2026', [ClientController::class, 'showCalendar2026'])
        ->middleware('can:client.view_calendar')
        ->name('clients.calendar2026');

    Route::get('client-groups', [ClientGroupController::class, 'index'])
        ->middleware('can:client_group.view')
        ->name('client-groups.index');
    Route::get('client-groups/create', [ClientGroupController::class, 'create'])
        ->middleware('can:client_group.create')
        ->name('client-groups.create');
    Route::post('client-groups', [ClientGroupController::class, 'store'])
        ->middleware('can:client_group.create')
        ->name('client-groups.store');
    Route::get('client-groups/{clientGroup}/edit', [ClientGroupController::class, 'edit'])
        ->middleware('can:client_group.edit')
        ->name('client-groups.edit');
    Route::put('client-groups/{clientGroup}', [ClientGroupController::class, 'update'])
        ->middleware('can:client_group.edit')
        ->name('client-groups.update');
    Route::delete('client-groups/{clientGroup}', [ClientGroupController::class, 'destroy'])
        ->middleware('can:client_group.delete')
        ->name('client-groups.destroy');

    Route::get('peer-groups', [PeerGroupController::class, 'index'])
        ->middleware('can:peer_group.view')
        ->name('peer-groups.index');
    Route::get('peer-groups/create', [PeerGroupController::class, 'create'])
        ->middleware('can:peer_group.create')
        ->name('peer-groups.create');
    Route::post('peer-groups', [PeerGroupController::class, 'store'])
        ->middleware('can:peer_group.create')
        ->name('peer-groups.store');
    Route::get('peer-groups/{peerGroup}/edit', [PeerGroupController::class, 'edit'])
        ->middleware('can:peer_group.edit')
        ->name('peer-groups.edit');
    Route::put('peer-groups/{peerGroup}', [PeerGroupController::class, 'update'])
        ->middleware('can:peer_group.edit')
        ->name('peer-groups.update');
    Route::delete('peer-groups/{peerGroup}', [PeerGroupController::class, 'destroy'])
        ->middleware('can:peer_group.delete')
        ->name('peer-groups.destroy');

    Route::get('programs', [ProgramController::class, 'index'])
        ->middleware('can:program.view')
        ->name('programs.index');
    Route::get('programs/create', [ProgramController::class, 'create'])
        ->middleware('can:program.create')
        ->name('programs.create');
    Route::post('programs', [ProgramController::class, 'store'])
        ->middleware('can:program.create')
        ->name('programs.store');
    Route::get('programs/{program}', [ProgramController::class, 'show'])
        ->middleware('can:program.view')
        ->name('programs.show');
    Route::get('programs/{program}/edit', [ProgramController::class, 'edit'])
        ->middleware('can:program.edit')
        ->name('programs.edit');
    Route::put('programs/{program}', [ProgramController::class, 'update'])
        ->middleware('can:program.edit')
        ->name('programs.update');
    Route::delete('programs/{program}', [ProgramController::class, 'destroy'])
        ->middleware('can:program.delete')
        ->name('programs.destroy');

    Route::get('houses', [HouseController::class, 'index'])
        ->middleware('can:house.view')
        ->name('houses.index');
    Route::get('houses/create', [HouseController::class, 'create'])
        ->middleware('can:house.create')
        ->name('houses.create');
    Route::post('houses', [HouseController::class, 'store'])
        ->middleware('can:house.create')
        ->name('houses.store');
    Route::get('houses/{house}', [HouseController::class, 'show'])
        ->middleware('can:house.view')
        ->name('houses.show');
    Route::get('houses/{house}/edit', [HouseController::class, 'edit'])
        ->middleware('can:house.edit')
        ->name('houses.edit');
    Route::put('houses/{house}', [HouseController::class, 'update'])
        ->middleware('can:house.edit')
        ->name('houses.update');
    Route::delete('houses/{house}', [HouseController::class, 'destroy'])
        ->middleware('can:house.delete')
        ->name('houses.destroy');

    Route::get('houses/{house}/apartments', [ApartmentController::class, 'byHouse'])
        ->middleware('can:apartment.view');

    Route::get('apartments', [ApartmentController::class, 'index'])
        ->middleware('can:apartment.view')
        ->name('apartments.index');
    Route::get('apartments/create', [ApartmentController::class, 'create'])
        ->middleware('can:apartment.create')
        ->name('apartments.create');
    Route::post('apartments', [ApartmentController::class, 'store'])
        ->middleware('can:apartment.create')
        ->name('apartments.store');
    Route::get('apartments/{apartment}', [ApartmentController::class, 'show'])
        ->middleware('can:apartment.view')
        ->name('apartments.show');
    Route::get('apartments/{apartment}/edit', [ApartmentController::class, 'edit'])
        ->middleware('can:apartment.edit')
        ->name('apartments.edit');
    Route::put('apartments/{apartment}', [ApartmentController::class, 'update'])
        ->middleware('can:apartment.edit')
        ->name('apartments.update');
    Route::delete('apartments/{apartment}', [ApartmentController::class, 'destroy'])
        ->middleware('can:apartment.delete')
        ->name('apartments.destroy');


    Route::get('consents', [ConsentController::class, 'index'])
        ->middleware('can:consent.view')
        ->name('consents.index');
    Route::get('consents/create', [ConsentController::class, 'create'])
        ->middleware('can:consent.create')
        ->name('consents.create');
    Route::post('consents', [ConsentController::class, 'store'])
        ->middleware('can:consent.create')
        ->name('consents.store');
    Route::get('consents/{consent}', [ConsentController::class, 'show'])
        ->middleware('can:consent.view')
        ->name('consents.show');
    Route::get('consents/{consent}/edit', [ConsentController::class, 'edit'])
        ->middleware('can:consent.edit')
        ->name('consents.edit');
    Route::get('consents/{consent}/regenerate', [ConsentController::class, 'regenerate'])
        ->middleware('can:consent.regenerate')
        ->name('consents.regenerate');
    Route::put('consents/{consent}', [ConsentController::class, 'update'])
        ->middleware('can:consent.edit')
        ->name('consents.update');
    route::delete('consents/{consent}', [ConsentController::class, 'destroy'])
        ->middleware('can:consent.delete')
        ->name('consents.destroy');

    Route::get('/consents/{clientId}/sign', [ConsentController::class, 'showSignForm'])
        ->middleware('can:consent.sign')
        ->name('consents.signForm');
    Route::post('/consents/{clientId}/sign', [ConsentController::class, 'signConsent'])
        ->middleware('can:consent.sign')
        ->name('consents.sign');
    // Route::post('/consents/{consent}/sign', [ConsentController::class, 'signForm'])->name('consents.signForm');
    Route::get('/consents/{clientId}/download', [ConsentController::class, 'downloadConsent'])
        ->middleware('can:consent.download')
        ->name('consents.download');
    Route::put('/consents/{id}/regenerate', [ConsentController::class, 'updateAndRegenerate'])
        ->middleware('can:consent.regenerate')
        ->name('consents.updateAndRegenerate');

    Route::get('/authorizations/tracker', [AuthorizationController::class, 'tracker'])
        ->middleware('can:authorization.view_tracker')
        ->name('authorizations.tracker');

    Route::get('/authorizations/eligibility-tracker', [ClientController::class, 'eligibilityTracker'])
        ->middleware('can:eligibility_tracker.view')
        ->name('authorizations.eligibility-tracker');
    Route::get('/authorizations/denied-auths-tracker', [AuthorizationController::class, 'deniedAuthsTracker'])
        ->middleware('can:authorization_line.view')
        ->name('authorizations.denied-auths-tracker');
    Route::get('/authorizations/pending-auths-tracker', [AuthorizationController::class, 'pendingAuthsTracker'])
        ->middleware('can:authorization_line.view')
        ->name('authorizations.pending-auths-tracker');
    Route::get('/hospitalizations/tracker', [HospitalizationController::class, 'tracker'])
        ->middleware('can:hospitalization_tracker.view')
        ->name('hospitalizations.tracker');

    Route::get('authorizations', [AuthorizationController::class, 'index'])
        ->middleware('can:authorization.view')
        ->name('authorizations.index');
    Route::get('authorizations/create', [AuthorizationController::class, 'create'])
        ->middleware('can:authorization.create')
        ->name('authorizations.create');
    Route::post('authorizations', [AuthorizationController::class, 'store'])
        ->middleware('can:authorization.create')
        ->name('authorizations.store');
    Route::get('authorizations/{authorization}', [AuthorizationController::class, 'show'])
        ->middleware('can:authorization.view')
        ->name('authorizations.show');
    Route::get('authorizations/{authorization}/edit', [AuthorizationController::class, 'edit'])
        ->middleware('can:authorization.edit')
        ->name('authorizations.edit');
    Route::put('authorizations/{authorization}', [AuthorizationController::class, 'update'])
        ->middleware('can:authorization.edit')
        ->name('authorizations.update');
    Route::delete('authorizations/{authorization}', [AuthorizationController::class, 'destroy'])
        ->middleware('can:authorization.delete')
        ->name('authorizations.destroy');

    Route::get('/auth-to-release-info', [AuthToReleaseInfoController::class, 'index'])
        ->middleware('can:auth_release.view')
        ->name('auth-to-release-info.index');
    Route::get('/auth-to-release-info/{client}', [AuthToReleaseInfoController::class, 'show'])
        ->middleware('can:auth_release.view')
        ->name('auth-to-release-info.show');
    Route::get('/auth_to_release_info/{client}/{medicalContact}/sign', [AuthToReleaseInfoController::class, 'signForm'])
        ->middleware('can:auth_release.sign')
        ->name('auth-to-release-info.signForm');
    Route::post('/auth_to_release_info/{client}/{medicalContact}/generate', [AuthToReleaseInfoController::class, 'generateFromConsent'])
        ->middleware('can:auth_release.generate')
        ->name('auth-to-release-info.generate');
    Route::post('/auth_to_release_info/{client}/{medicalContact}/sign', [AuthToReleaseInfoController::class, 'sign'])
        ->middleware('can:auth_release.sign')
        ->name('auth-to-release-info.sign');
    Route::post('/auth-to-release-info/{authId}/regenerate', [AuthToReleaseInfoController::class, 'regenerate'])
        ->middleware('can:auth_release.regenerate')
        ->name('auth-to-release-info.regenerate');
    Route::get('/auth-to-release-info/{authId}/download', [AuthToReleaseInfoController::class, 'download'])
        ->middleware('can:auth_release.download')
        ->name('auth-to-release-info.download');
    Route::get('/auth-to-release-info/{authId}/email', [AuthToReleaseInfoController::class, 'email'])
        ->middleware('can:auth_release.email')
        ->name('auth-to-release-info.email');

    Route::get('medical-contacts', [MedicalContactController::class, 'index'])
        ->middleware('can:medical_contact.view')
        ->name('medical-contacts.index');
    Route::get('medical-contacts/create', [MedicalContactController::class, 'create'])
        ->middleware('can:medical_contact.create')
        ->name('medical-contacts.create');
    Route::post('medical-contacts', [MedicalContactController::class, 'store'])
        ->middleware('can:medical_contact.create')
        ->name('medical-contacts.store');
    Route::get('medical-contacts/{medicalContact}', [MedicalContactController::class, 'show'])
        ->middleware('can:medical_contact.view')
        ->name('medical-contacts.show');
    Route::get('medical-contacts/{medicalContact}/edit', [MedicalContactController::class, 'edit'])
        ->middleware('can:medical_contact.edit')
        ->name('medical-contacts.edit');
    Route::put('medical-contacts/{medicalContact}', [MedicalContactController::class, 'update'])
        ->middleware('can:medical_contact.edit')
        ->name('medical-contacts.update');
    Route::delete('medical-contacts/{medicalContact}', [MedicalContactController::class, 'destroy'])
        ->middleware('can:medical_contact.delete')
        ->name('medical-contacts.destroy');

    Route::get('hospitalization-facilities', [HospitalizationFacilityController::class, 'index'])
        ->middleware('can:hospitalization_facility.view')
        ->name('hospitalization-facilities.index');
    Route::get('hospitalization-facilities/create', [HospitalizationFacilityController::class, 'create'])
        ->middleware('can:hospitalization_facility.create')
        ->name('hospitalization-facilities.create');
    Route::post('hospitalization-facilities', [HospitalizationFacilityController::class, 'store'])
        ->middleware('can:hospitalization_facility.create')
        ->name('hospitalization-facilities.store');
    Route::get('hospitalization-facilities/{hospitalizationFacility}/edit', [HospitalizationFacilityController::class, 'edit'])
        ->middleware('can:hospitalization_facility.edit')
        ->name('hospitalization-facilities.edit');
    Route::put('hospitalization-facilities/{hospitalizationFacility}', [HospitalizationFacilityController::class, 'update'])
        ->middleware('can:hospitalization_facility.edit')
        ->name('hospitalization-facilities.update');
    Route::delete('hospitalization-facilities/{hospitalizationFacility}', [HospitalizationFacilityController::class, 'destroy'])
        ->middleware('can:hospitalization_facility.delete')
        ->name('hospitalization-facilities.destroy');

    Route::get('/group-notes', [GroupNoteController::class, 'index'])
        ->middleware('can:group_note.view')
        ->name('group_notes.index');
    Route::get('/group-notes/edit/{counselor}', [GroupNoteController::class, 'edit'])
        ->middleware('can:group_note.edit')
        ->name('group_notes.edit');
    Route::post('/group-notes/update/{counselor}', [GroupNoteController::class, 'update'])
        ->middleware('can:group_note.update')
        ->name('group_notes.update');
    Route::get('/group-notes/view/{counselor}', [GroupNoteController::class, 'view'])
        ->middleware('can:group_note.view')
        ->name('group_notes.view');
    Route::get('/group-notes/data/{month}/{counselor}', [GroupNoteController::class, 'fetchGroupNotes'])
        ->middleware('can:group_note.view');

    Route::get('auth_line_of_services', [AuthLineOfServiceController::class, 'index'])
        ->middleware('can:authorization_line.view')
        ->name('auth_line_of_services.index');
    Route::get('auth_line_of_services/create', [AuthLineOfServiceController::class, 'create'])
        ->middleware('can:authorization_line.create')
        ->name('auth_line_of_services.create');
    Route::post('auth_line_of_services', [AuthLineOfServiceController::class, 'store'])
        ->middleware('can:authorization_line.create')
        ->name('auth_line_of_services.store');
    Route::get('auth_line_of_services/{authLineOfService}', [AuthLineOfServiceController::class, 'show'])
        ->middleware('can:authorization_line.view')
        ->name('auth_line_of_services.show');
    Route::get('auth_line_of_services/{authLineOfService}/edit', [AuthLineOfServiceController::class, 'edit'])
        ->middleware('can:authorization_line.edit')
        ->name('auth_line_of_services.edit');
    Route::put('auth_line_of_services/{authLineOfService}', [AuthLineOfServiceController::class, 'update'])
        ->middleware('can:authorization_line.edit')
        ->name('auth_line_of_services.update');
    Route::delete('auth_line_of_services/{authLineOfService}', [AuthLineOfServiceController::class, 'destroy'])
        ->middleware('can:authorization_line.delete')
        ->name('auth_line_of_services.destroy');

    Route::get('authorizations/{auth}/lines', [AuthLineOfServiceController::class, 'index'])
        ->middleware('can:authorization_line.view')
        ->name('authorizations.lines');
    Route::get('auth_line_of_services/create/{auth_id}', [AuthLineOfServiceController::class, 'create'])
        ->middleware('can:authorization_line.create')
        ->name('auth_line_of_services.create');

    Route::post('pre-bio-interviews/generate', [PreBioInterviewController::class, 'generate'])
        ->middleware('can:pre_bio.generate')
        ->name('pre-bio-interviews.generate');
    Route::resource('pre-bio-interviews', PreBioInterviewController::class)->middleware([
        'index' => 'can:pre_bio.view',
        'show' => 'can:pre_bio.view',
        'create' => 'can:pre_bio.create',
        'store' => 'can:pre_bio.create',
        'edit' => 'can:pre_bio.edit',
        'update' => 'can:pre_bio.edit',
        'destroy' => 'can:pre_bio.delete',
    ]);

    Route::get('verification-letters', [VerificationLetterController::class, 'index'])
        ->middleware('can:verification_letter.view')
        ->name('verification-letters.index');
    Route::get('verification-letters/create', [VerificationLetterController::class, 'create'])
        ->middleware('can:verification_letter.create')
        ->name('verification-letters.create');
    Route::post('verification-letters', [VerificationLetterController::class, 'store'])
        ->middleware('can:verification_letter.create')
        ->name('verification-letters.store');
    Route::get('verification-letters/{verificationLetter}', [VerificationLetterController::class, 'show'])
        ->middleware('can:verification_letter.view')
        ->name('verification-letters.show');
    Route::get('verification-letters/{verificationLetter}/edit', [VerificationLetterController::class, 'edit'])
        ->middleware('can:verification_letter.edit')
        ->name('verification-letters.edit');
    Route::put('verification-letters/{verificationLetter}', [VerificationLetterController::class, 'update'])
        ->middleware('can:verification_letter.edit')
        ->name('verification-letters.update');
    Route::delete('verification-letters/{verificationLetter}', [VerificationLetterController::class, 'destroy'])
        ->middleware('can:verification_letter.delete')
        ->name('verification-letters.destroy');
    Route::get('/verification-letters/{clientId}/download', [VerificationLetterController::class, 'download'])
        ->middleware('can:verification_letter.download')
        ->name('verification-letters.download');

    // Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');

    Route::get('clients/{client}/attendance', [AttendanceController::class, 'show'])
        ->middleware('can:attendance.view')
        ->name('attendances.show');
    Route::post('clients/{client}/attendance', [AttendanceController::class, 'store'])
        ->middleware('can:attendance.create')
        ->name('attendances.store');
    Route::get('attendances/{attendance}/attachments/{filename}', [AttendanceController::class, 'downloadAttachment'])
        ->middleware('can:attendance.view')
        ->name('attendances.attachments.download');
    Route::get('clients/{client}/notes', [NoteController::class, 'show'])
        ->middleware('can:note.view')
        ->name('notes.show');
    Route::post('clients/{client}/notes', [NoteController::class, 'store'])
        ->middleware('can:note.create')
        ->name('notes.store');
    Route::get('/clients/{client}/discharge', [ClientController::class, 'showDischargeForm'])
        ->middleware('can:client.discharge')
        ->name('clients.discharge.form');
    Route::post('/clients/{client}/discharge', [ClientController::class, 'submitDischarge'])
        ->middleware('can:client.discharge')
        ->name('clients.discharge.submit');
    Route::get('/clients/{client}/hospitalize', [ClientController::class, 'hospitalizeForm'])
        ->middleware('can:hospitalization.create')
        ->name('clients.hospitalize.form');
    Route::post('/clients/{client}/hospitalize', [ClientController::class, 'submitHospitalize'])
        ->middleware('can:hospitalization.create')
        ->name('clients.hospitalize.submit');
    Route::get('/clients/{client}/transition', [ClientController::class, 'transitionForm'])
        ->middleware('can:client.transition')
        ->name('clients.transition');
    Route::post('/clients/{client}/transition', [ClientController::class, 'submitTransition'])
        ->middleware('can:client.transition')
        ->name('clients.transition.submit');
    Route::get('/clients/{client}/reactivate', [ClientController::class, 'reactivateForm'])
        ->middleware('can:client.reactivate')
        ->name('clients.reactivate');
    Route::post('/clients/{client}/reactivate', [ClientController::class, 'submitReactivate'])
        ->middleware('can:client.reactivate')
        ->name('clients.reactivate.submit');
    Route::get('/clients/{client}/readmit', [ClientController::class, 'readmitForm'])
        ->middleware('can:hospitalization.readmit')
        ->name('clients.readmit');
    Route::post('/clients/{client}/readmit', [ClientController::class, 'submitReadmit'])
        ->middleware('can:hospitalization.readmit')
        ->name('clients.readmit.submit');

    Route::get('/attendance/batch', [AttendanceController::class, 'batch'])
        ->middleware('can:attendance.batch')
        ->name('attendances.batch');
    Route::post('/attendance/batch', [AttendanceController::class, 'batchStore'])
        ->middleware('can:attendance.batch_store')
        ->name('attendances.batchStore');
    Route::post('/attendance-submissions/duplicate-check', [SubmissionDuplicateController::class, 'check'])
        ->name('attendance-submissions.duplicate-check');
    Route::get('/attendance-2026', [AttendanceController2026::class, 'index'])
        ->middleware('can:attendance2026.view')
        ->name('attendance2026.index');
    Route::get('/attendance-2026/batch-outing', [AttendanceController2026::class, 'batchOuting'])
        ->middleware('can:attendance2026.create')
        ->name('attendance2026.batch_outing');
    Route::post('/attendance-2026/batch-outing', [AttendanceController2026::class, 'batchOutingStore'])
        ->middleware('can:attendance2026.create')
        ->name('attendance2026.batch_outing_store');
    Route::get('/attendance-2026/productivity-sheet', [AttendanceController2026::class, 'productivitySheet'])
        ->middleware('can:attendance2026.create')
        ->name('attendance2026.prod_sheet');
    Route::post('/attendance-2026/productivity-sheet', [AttendanceController2026::class, 'productivitySheetStore'])
        ->middleware('can:attendance2026.create')
        ->name('attendance2026.prod_sheet_store');
    Route::get('/attendance-2026/group-therapy', [AttendanceController2026::class, 'groupTherapy'])
        ->middleware('can:attendance2026.create')
        ->name('attendance2026.group_therapy');
    Route::post('/attendance-2026/group-therapy', [AttendanceController2026::class, 'groupTherapyStore'])
        ->middleware('can:attendance2026.create')
        ->name('attendance2026.group_therapy_store');
    Route::get('/attendance-2026/group-therapy/service-codes', [AttendanceController2026::class, 'groupTherapyServiceCodes'])
        ->middleware('can:attendance2026.create')
        ->name('attendance2026.group_therapy.service_codes');
    Route::get('/attendance-2026/group-therapy/groups', [AttendanceController2026::class, 'groupTherapyGroups'])
        ->middleware('can:attendance2026.create')
        ->name('attendance2026.group_therapy.groups');
    Route::get('/attendance-2026/cprs-group', [AttendanceController2026::class, 'cprsGroup'])
        ->middleware('can:attendance2026.create')
        ->name('attendance2026.cprs_group');
    Route::post('/attendance-2026/cprs-group', [AttendanceController2026::class, 'cprsGroupStore'])
        ->middleware('can:attendance2026.create')
        ->name('attendance2026.cprs_group_store');
    Route::get('/attendance-2026/submissions/{submission}', [AttendanceController2026::class, 'showSubmission'])
        ->middleware('can:attendance2026.view')
        ->name('attendance2026.submissions.show');
    Route::get('/attendance-2026/submissions/{submission}/edit', [AttendanceController2026::class, 'editSubmission'])
        ->middleware('can:attendance2026.edit')
        ->name('attendance2026.submissions.edit');
    Route::put('/attendance-2026/submissions/{submission}', [AttendanceController2026::class, 'updateSubmission'])
        ->middleware('can:attendance2026.edit')
        ->name('attendance2026.submissions.update');
    Route::delete('/attendance-2026/submissions/{submission}', [AttendanceController2026::class, 'destroySubmission'])
        ->middleware('can:attendance2026.delete')
        ->name('attendance2026.submissions.destroy');
    Route::get('/attendance-2026/submissions/{submission}/download', [AttendanceController2026::class, 'downloadSubmission'])
        ->middleware('can:attendance2026.download')
        ->name('attendance2026.submissions.download');
    Route::get('/attendance-2026/submissions/{submission}/attachments/{filename}', [AttendanceController2026::class, 'downloadAttachment'])
        ->middleware('can:attendance2026.view')
        ->name('attendance2026.submissions.attachments.download');
    Route::get('/cprs', [CprsController::class, 'index'])
        ->middleware('can:cprs.view')
        ->name('cprs.index');
    Route::get('/cprs/peer-outing', [CprsController::class, 'batchOuting'])
        ->middleware('can:cprs.peer_outing')
        ->name('cprs.batch_outing');
    Route::post('/cprs/peer-outing', [CprsController::class, 'batchOutingStore'])
        ->middleware('can:cprs.peer_outing')
        ->name('cprs.batch_outing_store');
    Route::get('/cprs/individual', [CprsController::class, 'productivitySheet'])
        ->middleware('can:cprs.individual')
        ->name('cprs.prod_sheet');
    Route::post('/cprs/individual', [CprsController::class, 'productivitySheetStore'])
        ->middleware('can:cprs.individual')
        ->name('cprs.prod_sheet_store');
    Route::get('/cprs/cprs-group', [CprsController::class, 'cprsGroup'])
        ->middleware('can:cprs.group')
        ->name('cprs.cprs_group');
    Route::post('/cprs/cprs-group', [CprsController::class, 'cprsGroupStore'])
        ->middleware('can:cprs.group')
        ->name('cprs.cprs_group_store');
    Route::get('/cprs/submissions/{submission}', [CprsController::class, 'showSubmission'])
        ->middleware('can:cprs.submissions.view')
        ->name('cprs.submissions.show');
    Route::get('/cprs/submissions/{submission}/edit', [CprsController::class, 'editSubmission'])
        ->middleware('can:cprs.submissions.edit')
        ->name('cprs.submissions.edit');
    Route::put('/cprs/submissions/{submission}', [CprsController::class, 'updateSubmission'])
        ->middleware('can:cprs.submissions.edit')
        ->name('cprs.submissions.update');
    Route::delete('/cprs/submissions/{submission}', [CprsController::class, 'destroySubmission'])
        ->middleware('can:cprs.submissions.delete')
        ->name('cprs.submissions.destroy');
    Route::get('/cprs/submissions/{submission}/download', [CprsController::class, 'downloadSubmission'])
        ->middleware('can:cprs.submissions.download')
        ->name('cprs.submissions.download');
    Route::get('/cprs/submissions/{submission}/attachments/{filename}', [CprsController::class, 'downloadAttachment'])
        ->middleware('can:cprs.submissions.view')
        ->name('cprs.submissions.attachments.download');
    Route::get('/clinical-notes', [ClinicalNotesController::class, 'index'])
        ->middleware('can:clinical_notes.view')
        ->name('clinical-notes.index');
    Route::get('/clinical-notes/op-individual', [ClinicalNotesController::class, 'opIndividual'])
        ->middleware('can:clinical_notes.op_individual')
        ->name('clinical-notes.op_individual');
    Route::post('/clinical-notes/op-individual', [ClinicalNotesController::class, 'opIndividualStore'])
        ->middleware('can:clinical_notes.op_individual')
        ->name('clinical-notes.op_individual_store');
    Route::get('/clinical-notes/productivity-sheet', [ClinicalNotesController::class, 'productivitySheet'])
        ->middleware('can:clinical_notes.create')
        ->name('clinical-notes.productivity_sheet');
    Route::post('/clinical-notes/productivity-sheet', [ClinicalNotesController::class, 'productivitySheetStore'])
        ->middleware('can:clinical_notes.create')
        ->name('clinical-notes.productivity_sheet_store');
    Route::get('/clinical-notes/tracker', [ClinicalNotesController::class, 'tracker'])
        ->middleware('can:clinical_notes.view')
        ->name('clinical-notes.tracker');
    Route::get('/clinical-notes/tracker/download', [ClinicalNotesController::class, 'trackerDownload'])
        ->middleware('can:clinical_notes.view')
        ->name('clinical-notes.tracker.download');
    Route::get('/clinical-notes/group-therapy', [ClinicalNotesController::class, 'groupTherapy'])
        ->middleware('can:clinical_notes.create')
        ->name('clinical-notes.group_therapy');
    Route::post('/clinical-notes/group-therapy', [ClinicalNotesController::class, 'groupTherapyStore'])
        ->middleware('can:clinical_notes.create')
        ->name('clinical-notes.group_therapy_store');
    Route::get('/clinical-notes/group-therapy/service-codes', [ClinicalNotesController::class, 'groupTherapyServiceCodes'])
        ->middleware('can:clinical_notes.create')
        ->name('clinical-notes.group_therapy.service_codes');
    Route::get('/clinical-notes/group-therapy/groups', [ClinicalNotesController::class, 'groupTherapyGroups'])
        ->middleware('can:clinical_notes.create')
        ->name('clinical-notes.group_therapy.groups');
    Route::get('/clinical-notes/submissions/{submission}', [ClinicalNotesController::class, 'showSubmission'])
        ->middleware('can:clinical_notes.view')
        ->name('clinical-notes.submissions.show');
    Route::get('/clinical-notes/submissions/{submission}/edit', [ClinicalNotesController::class, 'editSubmission'])
        ->middleware('can:clinical_notes.edit')
        ->name('clinical-notes.submissions.edit');
    Route::put('/clinical-notes/submissions/{submission}', [ClinicalNotesController::class, 'updateSubmission'])
        ->middleware('can:clinical_notes.edit')
        ->name('clinical-notes.submissions.update');
    Route::delete('/clinical-notes/submissions/{submission}', [ClinicalNotesController::class, 'destroySubmission'])
        ->middleware('can:clinical_notes.delete')
        ->name('clinical-notes.submissions.destroy');
    Route::get('/clinical-notes/submissions/{submission}/download', [ClinicalNotesController::class, 'downloadSubmission'])
        ->middleware('can:clinical_notes.download')
        ->name('clinical-notes.submissions.download');
    Route::get('/clinical-notes/submissions/{submission}/attachments/{filename}', [ClinicalNotesController::class, 'downloadAttachment'])
        ->middleware('can:clinical_notes.view')
        ->name('clinical-notes.submissions.attachments.download');
    Route::get('/notes/batch', [NoteController::class, 'batch'])
        ->middleware('can:note.batch')
        ->name('notes.batch');

    Route::get('reports/attendance/export', [ReportController::class, 'attendanceBulkExportForm'])
        ->middleware('can:report.attendance_export')
        ->name('reports.attendance.export');
    Route::post('reports/attendance/export', [ReportController::class, 'attendanceBulkExport'])
        ->middleware('can:report.attendance_export')
        ->name('reports.attendance.export.generate');
    Route::get('reports/attendance-tsv', [ReportController::class, 'attendanceTsv'])
        ->middleware('can:report.attendance_tsv')
        ->name('reports.attendanceTsv');
    Route::post('reports/attendance-tsv/download', [ReportController::class, 'attendanceTsvDownload'])
        ->middleware('can:report.attendance_tsv')
        ->name('reports.attendanceTsv.download');
    Route::post('/notes/batch', [NoteController::class, 'batchStore'])
        ->middleware('can:note.batch_store')
        ->name('notes.batchStore');


    Route::post('claims/{claim}/process-all', [ClaimController::class, 'processAll'])
        ->middleware('can:claim.process_all')
        ->name('claims.processAll');
    Route::post('claims/{claim}/add-check', [ClaimController::class, 'addCheck'])
        ->middleware('can:claim.add_check')
        ->name('claims.addCheck');

    Route::resource('claims', ClaimController::class)->middleware([
        'index' => 'can:claim.view',
        'show' => 'can:claim.view',
        'create' => 'can:claim.create',
        'store' => 'can:claim.create',
        'edit' => 'can:claim.edit',
        'update' => 'can:claim.edit',
        'destroy' => 'can:claim.delete',
    ]);
    Route::get('claims/{claim}/attachments/{filename}', [ClaimController::class, 'downloadAttachment'])
        ->middleware('can:claim.view')
        ->name('claims.attachments.download');
    Route::get('/clients/{id}/calendar-body', [ClaimController::class, 'calendarBody'])
        ->middleware('can:claim.view_calendar')
        ->name('clients.calendar.body');
    Route::get('/clients/{id}/calendar-body-2026', [ClaimController::class, 'calendarBody2026'])
        ->middleware('can:claim.view_calendar')
        ->name('clients.calendar2026.body');

    Route::get('claims/{client}/calendar', [ClaimController::class, 'calendar'])
        ->middleware('can:claim.view_calendar')
        ->name('claims.calendar');
    Route::get('claims/{client}/calendar-2026', [ClaimController::class, 'calendar2026'])
        ->middleware('can:claim.view_calendar')
        ->name('claims.calendar2026');
    Route::get('claim-lines', [ClaimLineOfServiceController::class, 'index'])
        ->middleware('can:claim_line.view')
        ->name('claim-lines.index');
    Route::resource('checks', CheckController::class)->middleware([
        'index' => 'can:check.view',
        'show' => 'can:check.view',
        'create' => 'can:check.create',
        'store' => 'can:check.create',
        'edit' => 'can:check.edit',
        'update' => 'can:check.edit',
        'destroy' => 'can:check.delete',
    ]);
    Route::get('checks/{check}/batch', [CheckBatchController::class, 'show'])
        ->middleware('can:check.edit')
        ->name('checks.batch.show');
    Route::post('checks/{check}/batch/scan', [CheckBatchController::class, 'scan'])
        ->middleware('can:check.edit')
        ->name('checks.batch.scan');
    Route::get('checks/{check}/batch/claims', [CheckBatchController::class, 'claimsForClient'])
        ->middleware('can:check.edit')
        ->name('checks.batch.claims');
    Route::get('checks/{check}/batch/resolve-client', [CheckBatchController::class, 'resolveClientClaims'])
        ->middleware('can:check.edit')
        ->name('checks.batch.resolve-client');
    Route::post('checks/{check}/batch/commit', [CheckBatchController::class, 'commit'])
        ->middleware('can:check.edit')
        ->name('checks.batch.commit');
    Route::get('checks/{check}/attachments/{filename}', [CheckController::class, 'downloadAttachment'])
        ->middleware('can:check.view')
        ->name('checks.attachments.download');
    Route::put('checks/{check}/toggle', [CheckController::class, 'toggle'])
        ->middleware('can:check.toggle')
        ->name('checks.toggle');
    Route::get('/claims/{claim}/check-status', [ClaimController::class, 'checkStatus'])
        ->middleware('can:claim.check_status')
        ->name('claims.checkStatus');

    Route::get('/attachments/download/{filename}', [AuthLineOfServiceController::class, 'downloadAttachment'])
        ->middleware('can:authorization_line.download_attachment')
        ->name('attachments.download');
    Route::get('reports', [ReportController::class, 'index'])
        ->middleware('can:report.view')
        ->name('reports.index');
    Route::get('reports/attendance', [ReportController::class, 'attendance'])
        ->middleware('can:report.attendance')
        ->name('reports.attendance');
    Route::get('reports/clients-by-house', [ReportController::class, 'clientsByHouse'])
        ->middleware('can:report.clients_by_house')
        ->name('reports.clientsByHouse');
    Route::get('reports/clients-by-counselor', [ReportController::class, 'clientsByCounselor'])
        ->middleware('can:report.clients_by_counselor')
        ->name('reports.clientsByCounselor');
    Route::get('reports/clients-by-peer', [ReportController::class, 'clientsByPeer'])
        ->middleware('can:report.clients_by_peer')
        ->name('reports.clientsByPeer');
    Route::get('reports/clients-by-group', [ReportController::class, 'clientsByGroup'])
        ->middleware('can:report.clients_by_group')
        ->name('reports.clientsByGroup');
    Route::get('reports/clients-by-peer-group', [ReportController::class, 'clientsByPeerGroup'])
        ->middleware('can:report.clients_by_peer_group')
        ->name('reports.clientsByPeerGroup');
    Route::get('reports/house', [ReportController::class, 'houseReport'])
        ->middleware('can:report.house')
        ->name('reports.house');
    Route::get('reports/clients-list', [ReportController::class, 'clientList'])
        ->middleware('can:report.client_list')
        ->name('reports.clientList');
    Route::get('reports/total-client-list', [ReportController::class, 'totalClientList'])
        ->middleware('can:report.total_client_list')
        ->name('reports.totalClientList');
    Route::get('reports/intakes', [ReportController::class, 'intakes'])
        ->middleware('can:report.intakes')
        ->name('reports.intakes');
    Route::get('reports/reactivations', [ReportController::class, 'reactivations'])
        ->middleware('can:report.reactivations')
        ->name('reports.reactivations');
    Route::get('reports/discharges', [ReportController::class, 'discharges'])
        ->middleware('can:report.discharges')
        ->name('reports.discharges');
    Route::get('reports/hospitalizations', [ReportController::class, 'hospitalizations'])
        ->middleware('can:report.hospitalizations')
        ->name('reports.hospitalizations');
    Route::get('reports/transitions', [ReportController::class, 'transitions'])
        ->middleware('can:report.transitions')
        ->name('reports.transitions');
    Route::get('reports/medicaid-by-date', [ReportController::class, 'medicaidByDate'])
        ->middleware('can:report.medicaid_by_date')
        ->name('reports.medicaidByDate');
    Route::get('reports/group-attendance-summary', [ReportController::class, 'groupAttendanceSummary'])
        ->middleware('can:report.group_attendance_summary')
        ->name('reports.groupAttendanceSummary');
    Route::get('reports/peer-group-attendance-summary', [ReportController::class, 'peerGroupAttendanceSummary'])
        ->middleware('can:report.peer_group_attendance_summary')
        ->name('reports.peerGroupAttendanceSummary');

    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->middleware('can:audit_log.view')
        ->name('audit-logs.index');

    Route::get('dropboxes', [DropboxController::class, 'index'])
        ->middleware('can:dropbox.view')
        ->name('dropboxes.index');
    Route::get('dropboxes/{dropbox}/download/{type}/{index?}', [DropboxController::class, 'download'])
        ->middleware('can:dropbox.download')
        ->where('type', 'biopsychosocial|discharge|urine|roi')
        ->name('dropboxes.download');
    Route::get('dropboxes/{dropbox}', [DropboxController::class, 'show'])
        ->middleware('can:dropbox.view')
        ->name('dropboxes.show');
    Route::delete('dropboxes/{dropbox}', [DropboxController::class, 'destroy'])
        ->middleware('can:dropbox.delete')
        ->name('dropboxes.destroy');
    Route::put('dropboxes/{dropbox}/status', [DropboxController::class, 'updateStatus'])
        ->middleware('can:dropbox.update_status')
        ->name('dropboxes.status');

});
