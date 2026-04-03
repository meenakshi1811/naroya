<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patients;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use App\Http\Controllers\NotificationController;
use DB;

class AppointmentController extends Controller
{

    public function cancelAppointment(Request $request)
    {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);

        if (!empty($headerArray[1])) {

            try {
                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {
                    $patient = Patients::find($tokenData['id']);
                    $request->validate([
                        'appointment_id' => 'required',
                        'reason' => 'required'
                    ]);
                    if (!empty($request->appointment_id)) {
                        $appointment = Appointment::find($request->appointment_id);

                        if (isset($appointment) && !empty($appointment)) {
                            $appointment->chrIsCanceled = 'Y';
                            $appointment->varCancelReason = $request->reason;
                            $appointment->save();
                            
                            $doctor = User::find($appointment->dr_id); // Assuming `doctor_id` exists in Appointment table

                            if ($doctor && !empty($doctor->fcm_token)) {
                                // Send push notification to the doctor
                                $notificationController = new NotificationController();
                                $notificationController->sendPushNotification(
                                    $doctor->fcm_token, // Assuming `notification_token` is the FCM token
                                    "Appointment Canceled",
                                    "The appointment with " . $patient->name . " has been canceled. Reason: " . $request->reason,
                                    'doctor'
                                );
                            }
                            
                            return response()->json([
                                'message' => 'Appontment cancel succesfully!',
                                'data' => [
                                    'user' => [
                                        'id' => $patient->id,
                                        'name' => $patient->name,
                                        'lastname' => $patient->lastname,
                                        'country' => $patient->country,
                                        'state' => $patient->state,
                                        'email' => $patient->email,
                                        'varProfile' => !empty($patient->varProfile) ? config('app.url') . 'api/patientprofile/' . $patient->varProfile : 'null'
                                    ]
                                ]
                            ], 200);
                        }
                    }
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Please Provide Valid details!',
                    'data' => [
                        'error' => 'Please Provide Valid details!'
                    ]
                ], 400);
            }
        }
        return response()->json([
            'message' => 'Unauthorized request',
            'data' => [
                'error' => 'Unauthorized request'
            ]
        ], 401);
    }

    public function handlePrescription(Request $request){
        try {
        $request->validate([
            'appointment_id' => 'required',
            'symptons' => 'required',
            'prescription' => 'required'
        ]); 
        $userData = $request->user();
        $userId = $userData->id;
            $user = User::select('id','name as first_name','surname','category','country','state','email','gmc_registration_no','indemnity_insurance_provider','policy_no','india_registration_no','dha_reg','reg_no','chrSmartcard','varProfile as profile_picture','varSpeciality as speciality','varExperience as total_experience','varPostGraduation as post_graduation','varPostGraduationYear as pg_year','varGraduation as graduation','varGraduationYear as graduation_year','varFees as fees','varTimeDuration as consultation_time')->where('id',$userId)->first();
            $current_work_org = DB::table('org_experiance')->select('title as org_name','startYear as start_year','endYear as end_year','varDescription as description','isCurrentworkOrg')->where('user_id',$userId)->get();
            $current_work_org = json_encode($current_work_org);
            $user->current_work_org = $current_work_org;
            if(!empty($user->profile_picture)){
                $user->profile_picture = config('app.url').'api/docterprofile/'.$user->profile_picture;
            }
            $appointmedId = $request->appointment_id;
            $appointment = Appointment::find($appointmedId);
            if(isset($appointment) && !empty($appointment)){
                $appointment->typeSympton = $request->symptons;
                $appointment->varPrescription = $request->prescription;
                $appointment->save();

                return response()->json([
                    'message' => 'Prescription added Succesfully!',
                    'data'=>[
                            'doctor' => $user
                    ]],200);
            }else{
                return response()->json([
                    'message' => 'Please Provide Valid details!',
                    'data' => [
                        'error' => 'Please Provide Valid details!'
                    ]
                ], 400);
            }       
    } catch(\Exception $e){
        return response()->json([
            'message' => 'Please Provide Valid details!',
            'data' => [
                'error' => 'Please Provide Valid details!'
            ]
        ], 400);
    }
    }
    public function getAppointmentData(Request $request)
    {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        $today = Carbon::today();
        $currentDateTime = now();
        $pageSize = max(1, min((int) $request->input('pageSize', 10), 100));
        $upcomingPage = max(1, (int) $request->input('upcomingPageNumber', 1));
        $pastPage = max(1, (int) $request->input('pastPageNumber', 1));
        $shouldPaginateUpcoming = $request->filled('upcomingPageNumber') || $request->filled('pageSize');
        $shouldPaginatePast = $request->filled('pastPageNumber') || $request->filled('pageSize');
        if (!empty($headerArray[1])) {
            try {
                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {
                    $patient = Patients::find($tokenData['id']);
                    $nextAppointmentQuery = Appointment::select('appointment.*','users.name','users.surname','users.varProfile','dr_category.title as category','users.varFees as amount')
                    ->join('users','appointment.dr_id','users.id')
                    ->join('dr_category','users.category','dr_category.id')
                    ->where('appointment.patient_id', $patient->id)
                    ->where('appointment.charIsPaid','Y')
                    ->where('appointment.chrIsAccepted','Y')
                    ->where('appointment.chrIsCanceled','N')
                    ->whereDate('appointment.varAppointment','>=',$today)
                    ->where(function($query) use ($currentDateTime) {
                        $query->whereTime('appointment.endTime', '>=', $currentDateTime->format('H:i'))
                              ->orWhereDate('appointment.varAppointment', '>', $currentDateTime->format('Y-m-d')); // Also include future dates regardless of time
                    })
                    ->orderBy('appointment.varAppointment', 'asc')     // Order by appointment date, ascending
                     ->orderBy('appointment.startTime', 'asc');

                $nextAppointmentData = $shouldPaginateUpcoming
                    ? $nextAppointmentQuery->paginate($pageSize, ['*'], 'upcomingPage', $upcomingPage)
                    : $nextAppointmentQuery->get();

                $nextAppointmentResponse = [];
                $bnextAppointmentResponseData = [];
                if (isset($nextAppointmentData) && !empty($nextAppointmentData)) {
                    $nextAppointments = $shouldPaginateUpcoming ? $nextAppointmentData->items() : $nextAppointmentData;
                    foreach($nextAppointments as $nextAppointment){   
                        $nextAppointmentResponse['id'] = $nextAppointment->id;
                        $nextAppointmentResponse['startTime'] = $nextAppointment->startTime;
                        $nextAppointmentResponse['endTime'] = $nextAppointment->endTime;
                        $nextAppointmentResponse['appointmentDate'] = $nextAppointment->varAppointment;
                        $nextAppointmentResponse['sympton'] = $nextAppointment->varSympton;
                        $nextAppointmentResponse['sympton_description'] = $nextAppointment->varSymptondesc;
                        $nextAppointmentResponse['IsPaid'] = $nextAppointment->charIsPaid;
                        $nextAppointmentResponse['DoctorName'] = $nextAppointment->name .' '. $nextAppointment->surname;
                        $nextAppointmentResponse['category'] =  $nextAppointment->category;
                        $nextAppointmentResponse['amount'] =  $nextAppointment->amount;
                        $nextAppointmentResponse['profile_picture'] = config('app.url').'api/docterprofile/'.$nextAppointment->varProfile;
                        $bnextAppointmentResponseData[] = $nextAppointmentResponse;
                    }
                  
                }

                $bookingHistoryQuery = Appointment::select('appointment.*','users.name','users.surname','users.varProfile','dr_category.title as category','users.varFees as amount')
                                    ->where('appointment.patient_id', $patient->id)
                                    ->join('users','appointment.dr_id','users.id')
                                    ->join('dr_category','users.category','dr_category.id')
                                    ->where('appointment.patient_id', $patient->id)
                                    ->where('appointment.charIsPaid','Y')
                                    ->where('appointment.chrIsAccepted','Y')
                                    ->where('appointment.chrIsCanceled','N')
                                    ->whereDate('appointment.varAppointment', '<=', $today)
                                    ->where(function ($query) use ($currentDateTime) {
                                        $query->whereTime('appointment.endTime', '<', $currentDateTime->format('H:i'))
                                            ->orWhereDate('appointment.varAppointment', '<', $currentDateTime->format('Y-m-d')); // Also include future dates regardless of time
                                    })
                                    ->orderBy('appointment.varAppointment', 'desc')     // Order by appointment date, ascending
                                     ->orderBy('appointment.startTime', 'desc');
                $bookingHistory = $shouldPaginatePast
                    ? $bookingHistoryQuery->paginate($pageSize, ['*'], 'pastPage', $pastPage)
                    : $bookingHistoryQuery->get();
                $bookingHistoryResponse = [];
                $bookingHistoryResponseData = [];
                if(isset($bookingHistory) && count($bookingHistory) > 0){
                    $pastAppointments = $shouldPaginatePast ? $bookingHistory->items() : $bookingHistory;
                    foreach($pastAppointments as $booking){
                        $bookingHistoryResponse['id'] = $booking->id;
                        $bookingHistoryResponse['startTime'] = $booking->startTime;
                        $bookingHistoryResponse['endTime'] = $booking->endTime;
                        $bookingHistoryResponse['appointmentDate'] = $booking->varAppointment;
                        $bookingHistoryResponse['sympton'] = $booking->varSympton;
                        $bookingHistoryResponse['sympton_description'] = $booking->varSymptondesc;
                        $bookingHistoryResponse['IsPaid'] = $booking->charIsPaid;
                        $bookingHistoryResponse['DoctorName'] = $booking->name .' '. $booking->surname;
                        $bookingHistoryResponse['category'] =  $booking->category;
                        $bookingHistoryResponse['amount'] =  $booking->amount;
                         $bookingHistoryResponse['Presc_Sympton'] = $booking->typeSympton;
                    $bookingHistoryResponse['Prescription'] = $booking->varPrescription;
                         $bookingHistoryResponse['profile_picture'] = config('app.url').'api/docterprofile/'.$booking->varProfile;
                        $bookingHistoryResponseData[] = $bookingHistoryResponse;
                    }
                }

                return response()->json([
                    'message' => 'Success!',
                    'data' => [
                        'user' => [
                            'id' => $patient->id,
                            'name' => $patient->name,
                            'lastname' => $patient->lastname,
                            'country' => $patient->country,
                            'state' => $patient->state,
                            'email' => $patient->email,
                            'varProfile' => !empty($patient->varProfile) ? config('app.url') . 'api/patientprofile/' . $patient->varProfile : 'null'
                        ],
                        'upcomingAppointment' => $bnextAppointmentResponseData,
                        'pastAppointment' =>$bookingHistoryResponseData,
                        'upcomingPagination' => $shouldPaginateUpcoming ? [
                            'current_page' => $nextAppointmentData->currentPage(),
                            'per_page' => $nextAppointmentData->perPage(),
                            'total' => $nextAppointmentData->total(),
                            'last_page' => $nextAppointmentData->lastPage(),
                        ] : new \stdClass(),
                        'pastPagination' => $shouldPaginatePast ? [
                            'current_page' => $bookingHistory->currentPage(),
                            'per_page' => $bookingHistory->perPage(),
                            'total' => $bookingHistory->total(),
                            'last_page' => $bookingHistory->lastPage(),
                        ] : new \stdClass(),
                    ]
                ], 200);

                }
            } catch (\Exception $e) {
                // dd($e);
                return response()->json([
                    'message' => 'Please Provide Valid details!',
                    'data' => [
                        'error' => 'Please Provide Valid details!'
                    ]
                ], 400);
            }
        }
        return response()->json([
            'message' => 'Unauthorized request',
            'data' => [
                'error' => 'Unauthorized request'
            ]
        ], 401);
    }
}
