<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateToken;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login',[App\Http\Controllers\AuthController::class, 'login']);
Route::post('/register',[App\Http\Controllers\AuthController::class, 'register']);
Route::get('/stripe/refresh/{userId}', [App\Http\Controllers\AuthController::class, 'refreshOnboarding'])->name('stripe.refresh');
Route::get('/countries',[App\Http\Controllers\AuthController::class, 'countries']);
Route::get('/languages',[App\Http\Controllers\AuthController::class, 'languages']);
Route::post('/set-localization-language',[App\Http\Controllers\AuthController::class, 'setLocalizationLanguage']);
Route::post('/forget-password',[App\Http\Controllers\ForgetPasswordController::class, 'EmailSend']);

Route::get('/state-list',[App\Http\Controllers\AuthController::class, 'stateList']);
Route::get('/city-list',[App\Http\Controllers\AuthController::class, 'cityList']);

Route::get('/speciality', [App\Http\Controllers\AuthController::class, 'speciality']);
Route::get('/public/doctor/{doctorId}', [App\Http\Controllers\PatientController::class, 'getPublicDoctorDetail']);
// Accepts a patient or a doctor login token; the controller checks which one.
Route::match(['get', 'post'], '/clinic-running-token', [App\Http\Controllers\ClinicTokenController::class, 'running']);
Route::match(['get', 'post'], '/clinic-tokens', [App\Http\Controllers\ClinicTokenController::class, 'tokens']);
Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handle']);
Route::post('/refund', [App\Http\Controllers\PaymentController::class, 'processRefund'])->middleware('throttle:payment');
Route::post('/generate-agora-details', [App\Http\Controllers\AgoraController::class, 'generateAgoraDetails']);

// Allow browser preflight requests for all doctor APIs before auth middleware runs.
Route::options('/doctor/{any?}', function () {
    return response()->noContent();
})->where('any', '.*');

Route::middleware('auth:api')->group(function () {
    Route::get('/user', [App\Http\Controllers\AuthController::class, 'getUserData']);
    Route::get('/doctor/home', [App\Http\Controllers\AuthController::class, 'getHome']);
    Route::post('/doctor/all-request', [App\Http\Controllers\AuthController::class, 'getRequest']);
    Route::post('/doctor/all-todayappointment', [App\Http\Controllers\AuthController::class, 'getTodayAppointment']);
    Route::post('/doctor/all-nextappointment', [App\Http\Controllers\AuthController::class, 'getNextAppointment']);
    Route::post('/update-profile',[App\Http\Controllers\AuthController::class, 'update']);   
    Route::post('/doctor/availability',[App\Http\Controllers\AuthController::class, 'Availability']);
    Route::post('/doctor/patient-request',[App\Http\Controllers\AuthController::class, 'patientRequest']);
    Route::post('/doctor/patient-profile',[App\Http\Controllers\AuthController::class, 'getPatientData']);
    Route::post('/doctor/patient-history',[App\Http\Controllers\AuthController::class, 'getPatientHistory']);  
    Route::post('/doctor/patient-block',[App\Http\Controllers\AuthController::class, 'BlockUnblock']);
     Route::post('/doctor/schedule',[App\Http\Controllers\AuthController::class, 'getSchedule']);
    // Clinic token queue (doctors and clinic employees)
    Route::get('/doctor/clinic-token', [App\Http\Controllers\ClinicTokenController::class, 'current']);
    Route::post('/doctor/clinic-token/increase', [App\Http\Controllers\ClinicTokenController::class, 'increase']);
    Route::post('/doctor/clinic-token/next', [App\Http\Controllers\ClinicTokenController::class, 'increase']); // older name for increase
    Route::post('/doctor/clinic-token/decrease', [App\Http\Controllers\ClinicTokenController::class, 'decrease']);
    Route::post('/doctor/clinic-token/update', [App\Http\Controllers\ClinicTokenController::class, 'change']);
    Route::get('/doctor/clinic-patients', [App\Http\Controllers\ClinicTokenController::class, 'patients']);
    Route::post('/doctor/clinic-patients/attendance', [App\Http\Controllers\ClinicTokenController::class, 'attendance']);
    Route::post('/doctor/clinic-token/set', [App\Http\Controllers\ClinicTokenController::class, 'set']);
    Route::post('/doctor/clinic-token/clear', [App\Http\Controllers\ClinicTokenController::class, 'clear']);
     Route::post('/doctor/prescription',[App\Http\Controllers\AppointmentController::class, 'handlePrescription']);
     Route::post('/update-payment-setup', [App\Http\Controllers\PaymentController::class, 'updatePaymentSetupStatus']);
    Route::post('/doctor/bank-details', [App\Http\Controllers\DoctorBankDetailController::class, 'upsert']);
    Route::get('/doctor/bank-details/{doctorId}', [App\Http\Controllers\DoctorBankDetailController::class, 'showByDoctorId']);
    Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout']);

});

Route::post('/patient-login',[App\Http\Controllers\PatientController::class, 'login']);
Route::post('/patient-phone-login',[App\Http\Controllers\PatientController::class, 'phoneLogin']);
Route::post('/patients-register',[App\Http\Controllers\PatientController::class, 'register']);
Route::middleware([AuthenticateToken::class])->group(function () {
Route::post('/patient/home',[App\Http\Controllers\PatientController::class, 'home']);
Route::post('/patient/viewall',[App\Http\Controllers\PatientController::class, 'viewAll']);
Route::match(['get', 'post'], '/patient/clinics', [App\Http\Controllers\PatientController::class, 'clinics']);
Route::match(['get', 'post'], '/patient/clinic-info', [App\Http\Controllers\PatientController::class, 'clinicDoctors']);
Route::match(['get', 'post'], '/patient/clinic-doctors', [App\Http\Controllers\PatientController::class, 'clinicDoctors']); // older name for clinic-info
Route::post('/patient/favourite',[App\Http\Controllers\PatientController::class, 'Favourite']);
Route::post('/patient/search',[App\Http\Controllers\PatientController::class, 'Search']);
Route::post('/patient/timeslot',[App\Http\Controllers\PatientController::class, 'getTimeSlots']);
Route::post('/patient/send-request',[App\Http\Controllers\PatientController::class, 'sendRequest'])->middleware('throttle:booking');
Route::post('/patient/send-request-clinic', [App\Http\Controllers\ClinicAppointmentController::class, 'sendRequest'])->middleware('throttle:booking');
Route::get('/patient-details',[App\Http\Controllers\PatientController::class, 'patientDetails']);
Route::get('/patient/free-slots-offer',[App\Http\Controllers\PatientController::class, 'getFreeSlotsOffer']);
Route::post('/patient-update-profile',[App\Http\Controllers\PatientController::class, 'updateData']);
Route::post('/patient/doctor-profile',[App\Http\Controllers\PatientController::class, 'getDoctorData']);
Route::post('/patient/doctor-profile',[App\Http\Controllers\PatientController::class, 'getDoctorData']);
Route::post('/patient/feedback',[App\Http\Controllers\PatientController::class, 'handleFeedback']);
Route::post('/patient/cancel-appointment', [App\Http\Controllers\AppointmentController::class, 'cancelAppointment']);
Route::post('/patient/cancel-appointment-clinic', [App\Http\Controllers\ClinicAppointmentController::class, 'cancel']);
Route::post('/patient/clear-conflict-appointment', [App\Http\Controllers\AppointmentController::class, 'clearConflictAppointment']);
Route::post('/patient/process-payment', [App\Http\Controllers\PaymentController::class, 'ProcessPayment'])->middleware('throttle:payment');
Route::get('/patient/my-appointment', [App\Http\Controllers\AppointmentController::class, 'getAppointmentData']);
Route::post('patient/logout', [App\Http\Controllers\PatientController::class, 'logout']);
});

Route::middleware('auth:patient-api')->get('/patient/profile', function (Request $request) {
    return $request->user();
});
