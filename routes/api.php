<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateToken;


header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');

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
Route::post('/forget-password',[App\Http\Controllers\ForgetPasswordController::class, 'EmailSend']);

Route::get('/state/{countryId}',[App\Http\Controllers\AuthController::class, 'stateList']);

Route::get('/speciality', [App\Http\Controllers\AuthController::class, 'speciality']);
Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handle']);
Route::post('/refund', [App\Http\Controllers\PaymentController::class, 'processRefund'])->middleware('throttle:payment');
Route::post('/generate-agora-details', [App\Http\Controllers\AgoraController::class, 'generateAgoraDetails']);

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
     Route::post('/doctor/prescription',[App\Http\Controllers\AppointmentController::class, 'handlePrescription']);
     Route::post('/update-payment-setup', [App\Http\Controllers\PaymentController::class, 'updatePaymentSetupStatus']);
     Route::post('/logout', [App\Http\Controllers\AuthController::class, 'logout']);

});

Route::post('/patient-login',[App\Http\Controllers\PatientController::class, 'login']);
Route::post('/patients-register',[App\Http\Controllers\PatientController::class, 'register']);
Route::middleware([AuthenticateToken::class])->group(function () {
Route::post('/patient/home',[App\Http\Controllers\PatientController::class, 'home']);
Route::post('/patient/viewall',[App\Http\Controllers\PatientController::class, 'viewAll']);
Route::post('/patient/favourite',[App\Http\Controllers\PatientController::class, 'Favourite']);
Route::post('/patient/search',[App\Http\Controllers\PatientController::class, 'Search']);
Route::post('/patient/timeslot',[App\Http\Controllers\PatientController::class, 'getTimeSlots']);
Route::post('/patient/send-request',[App\Http\Controllers\PatientController::class, 'sendRequest'])->middleware('throttle:booking');
Route::get('/patient-details',[App\Http\Controllers\PatientController::class, 'patientDetails']);
Route::post('/patient-update-profile',[App\Http\Controllers\PatientController::class, 'updateData']);
Route::post('/patient/doctor-profile',[App\Http\Controllers\PatientController::class, 'getDoctorData']);
Route::post('/patient/doctor-profile',[App\Http\Controllers\PatientController::class, 'getDoctorData']);
Route::post('/patient/feedback',[App\Http\Controllers\PatientController::class, 'handleFeedback']);
Route::post('/patient/cancel-appointment', [App\Http\Controllers\AppointmentController::class, 'cancelAppointment']);
Route::post('/patient/process-payment', [App\Http\Controllers\PaymentController::class, 'ProcessPayment'])->middleware('throttle:payment');
Route::get('/patient/my-appointment', [App\Http\Controllers\AppointmentController::class, 'getAppointmentData']);
Route::post('patient/logout', [App\Http\Controllers\PatientController::class, 'logout']);
});

Route::middleware('auth:patient-api')->get('/patient/profile', function (Request $request) {
    return $request->user();
});
