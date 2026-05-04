<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


use App\Http\Controllers\PaymentController;

Route::get('/sentry-test', function () {
    throw new Exception("Sentry test error!");
});

Route::get('payment', [PaymentController::class, 'showPaymentForm']);
Route::post('payment', [PaymentController::class, 'storePayment'])->name('payment.store');
Route::get('payment/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('payment/failure', [PaymentController::class, 'failure'])->name('payment.failure');

Route::get('/', [App\Http\Controllers\AdminAuthController::class, 'showLoginForm']);

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});
Route::get('api/terms-and-conditions', function () {
    return view('terms-condition');
});
Route::get('/terms-and-conditions-patient', function () {
    return view('terms-condition-patients');
});
Route::get('/terms-and-conditions-doctor', function () {
    return view('terms-condition-doctors');
});
Route::get('/about-us', function () {
    return view('about-us');
});
Route::get('/contact-us', function () {
    return view('contact-us');
});
Route::get('/refund-policy', function () {
    return view('refund-policy');
});
Route::get('/disclaimer', function () {
    return view('disclaimer');
});
Route::get('admin/login', [App\Http\Controllers\AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('admin/login', [App\Http\Controllers\AdminAuthController::class, 'login']);
Route::get('admin/logout', [App\Http\Controllers\AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware(['auth:web'])->group(function () {
    Route::get('/admin', function () {
        return view('admin.dashboard'); // Create this view
    });
    Route::get('/admin/doctor',[App\Http\Controllers\Admin\DoctorController::class, 'index'])->name('admin.doctor');
    Route::get('/admin/doctor/{id}/activities',[App\Http\Controllers\Admin\DoctorController::class, 'activities'])->name('admin.doctor.activities');
    Route::get('/admin/pending-doctor',[App\Http\Controllers\Admin\DoctorController::class, 'PendingList'])->name('admin.doctor');
    Route::post('/admin/doctor',[App\Http\Controllers\Admin\DoctorController::class, 'updateData'])->name('admin.approval');
    Route::delete('/admin/doctor/delete/{id}', [App\Http\Controllers\Admin\DoctorController::class, 'destroy'])->name('admin.doctor.delete');
    Route::get('/admin/patient',[App\Http\Controllers\PatientController::class, 'listData'])->name('admin.patient');
    Route::post('/admin/update-payment', [App\Http\Controllers\Admin\DoctorController::class, 'updatePayment'])->name('admin.updatePayment');
        Route::delete('/admin/delete-doctor/{id}', [App\Http\Controllers\Admin\DoctorController::class, 'deleteDoctor']);
Route::delete('/admin/delete-patient/{id}', [App\Http\Controllers\PatientController::class, 'deletePatient'])
    ->name('admin.patient.delete');

    
    //Country
    Route::get('/admin/country',[App\Http\Controllers\Admin\CountryController::class, 'listData'])->name('admin.country');
    Route::get('/admin/country/add', [App\Http\Controllers\Admin\CountryController::class, 'create'])->name('admin.country.add');
    Route::post('/admin/country', [App\Http\Controllers\Admin\CountryController::class, 'store'])->name('admin.country.store');
    Route::get('/admin/country/{id}/edit', [App\Http\Controllers\Admin\CountryController::class,  'edit'])->name('admin.country.edit');
    Route::put('/admin/country/{id}', [App\Http\Controllers\Admin\CountryController::class,  'update'])->name('admin.country.update');
    Route::delete('/admin/country/{id}', [App\Http\Controllers\Admin\CountryController::class,  'Delete'])->name('admin.country.delete');


    //Languages
    Route::get('/admin/language',[App\Http\Controllers\Admin\LanguageController::class, 'listData'])->name('admin.language');
    Route::get('/admin/language/add', [App\Http\Controllers\Admin\LanguageController::class, 'create'])->name('admin.language.add');
    Route::post('/admin/language', [App\Http\Controllers\Admin\LanguageController::class, 'store'])->name('admin.language.store');
    Route::get('/admin/language/{id}/edit', [App\Http\Controllers\Admin\LanguageController::class,  'edit'])->name('admin.language.edit');
    Route::put('/admin/language/{id}', [App\Http\Controllers\Admin\LanguageController::class,  'update'])->name('admin.language.update');
    Route::delete('/admin/language/{id}', [App\Http\Controllers\Admin\LanguageController::class,  'Delete'])->name('admin.language.delete');

    //Speciality Admin
    Route::get('/admin/speciality',[App\Http\Controllers\Admin\SpecialityController::class, 'listData'])->name('admin.speciality');
    Route::get('/admin/speciality/add', [App\Http\Controllers\Admin\SpecialityController::class, 'create'])->name('admin.speciality.add');
    Route::post('/admin/speciality', [App\Http\Controllers\Admin\SpecialityController::class, 'store'])->name('admin.speciality.store');
    Route::get('/admin/speciality/{id}/edit', [App\Http\Controllers\Admin\SpecialityController::class,  'edit'])->name('speciality.edit');
    Route::put('/admin/speciality/{id}', [App\Http\Controllers\Admin\SpecialityController::class,  'update'])->name('speciality.update');
    Route::delete('/admin/speciality/{id}', [App\Http\Controllers\Admin\SpecialityController::class,  'Delete'])->name('admin.speciality.delete');

    //appointment Admin
    Route::get('/admin/appointment',[App\Http\Controllers\Admin\AppointmentController::class, 'index'])->name('admin.appointment');
    Route::get('/admin/appointments/filter', [App\Http\Controllers\Admin\AppointmentController::class, 'filter'])->name('appointments.filter');
    Route::get('/admin/specialities/list', [App\Http\Controllers\Admin\AppointmentController::class, 'getSpecialities'])->name('specialities.list');
    //Setting
    Route::get('/admin/settings',[App\Http\Controllers\Admin\SettingController::class, 'index'])->name('admin.settings');
    Route::post('/admin/settings/update',[App\Http\Controllers\Admin\SettingController::class, 'update'])->name('admin.settings.update');
    
    
    //PaymentLogs
    Route::get('/admin/payment-log',[App\Http\Controllers\Admin\PaymentLogController::class, 'showPaymentLogs'])->name('admin.payment-log');
    Route::get('/admin/payment-ledger',[App\Http\Controllers\Admin\PaymentLogController::class, 'showPaymentLedger'])->name('admin.payment-ledger');
    Route::get('/admin/payment-ledger/{id}',[App\Http\Controllers\Admin\PaymentLogController::class, 'showDoctorPaymentLedger'])->name('admin.payment-ledger.doctor');
    Route::post('/admin/refund', [PaymentController::class, 'processRefund']);

    

});

Route::get('password/reset/{token}', [\App\Http\Controllers\ResePasswordController::class, 'create'])
    ->name('password.reset');

Route::post('password/reset', [\App\Http\Controllers\ResePasswordController::class, 'update'])
    ->name('password.update');
// Route::get('/admin', function () {
//     return view('admin.dashboard'); // Create a dashboard view
// });
