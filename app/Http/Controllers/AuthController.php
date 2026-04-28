<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Block;
use Illuminate\Support\Facades\Hash;
use DB;
use Storage;
use Carbon\Carbon;
use App\Models\GeneralSetting;
use App\Models\Patients;
use App\Models\Language;
use App\Http\Controllers\NotificationController;
use App\Models\OrgExperience;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Illuminate\Support\Facades\Mail;
use App\Mail\DoctorRegistrationReceivedMail;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string'
            ]);
    
            $credentials = request(['email', 'password']);
            $user = User::where('email', $request->email)->first();
    
           if (!$user) {
                return response()->json([
                    'message' => 'User Dose not exist!',
                    'data' => [
                        'error' => 'User Dose not exist!'
                    ]
                ], 400);
            }
    
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials!',
                    'data' => [
                        'error' => 'Invalid credentials!'
                    ]
                ], 400);
            }
    
            
    
            if (!$user->chrApproval) {
                return response()->json([
                    'message' => 'Your account is currently pending approval. Please contact your administration for further assistance.'
                ], 200);
            }
            $user->tokens()->delete();
            $http = new \GuzzleHttp\Client;
            $response = $http->post(config('app.url') . 'oauth/token', [
                'form_params' => [
                    'grant_type' => 'password',
                    'client_id' => env('PASSWORD_CLIENT_ID'),
                    'client_secret' => env('PASSWORD_CLIENT_SECRET'),
                    'username' => $request->email,
                    'password' => $request->password,
                    'scope' => '',
                ],
            ]);
    
            $tokenData = json_decode((string) $response->getBody(), true);
    
            // Send Push Notification
            // if ($user->fcm_token) {
            //     $this->sendPushNotification($user->fcm_token, 'Login Successful', 'You have successfully logged in.');
            // }

        if (isset($request->fcm_token) && !empty($request->fcm_token)) {
                $notificationController = new NotificationController();
            
                if ($request->fcm_token === $user->fcm_token) {
                    $notificationController->sendPushNotification(
                        $user->fcm_token,
                        'Login Successful!',
                        'You have successfully logged in.',
                        'doctor'
                    );
                } else {
                    $message = $user->fcm_token
                        ? 'Your account was logged in from a new device. If this was not you, please contact support.'
                        : 'You have successfully logged in.';
            
                    $notificationController->sendPushNotification(
                        $user->fcm_token ?: $request->fcm_token,
                        $user->fcm_token ? 'New Device Login' : 'Login Successful!',
                        $message,
                        'doctor'
                    );
            
                    $user->fcm_token = $request->fcm_token;
                    $user->save();
            }
        } elseif (!empty($user->fcm_token)) {
            $notificationController = new NotificationController();
            $notificationController->sendPushNotification(
                $user->fcm_token,
                'Login Successful!',
                'You have successfully logged in.',
                'doctor'
            );
        }

            $user->varProfile = config('app.url') . 'api/docterprofile/' . $user->varProfile;
            return response()->json([
                'message' => 'Login successful!',
                'data' => [
                    'user' => $user,
                    'token_type' => $tokenData['token_type'],
                    'expires_in' => $tokenData['expires_in'],
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token']
                ]
            ], 200);
    
        } catch (\Exception $e) {
            // dd($e);
            if (method_exists($e, 'errors')) {
                return response()->json([
                    'message' => 'Please Provide Valid details!',
                    'data' => [
                        'error' => 'Please Provide Valid details!',
                    ]
                ], 400);
            }
    
            return response()->json([
                'message' => 'Please Provide Valid details!',
                'data' => [
                    'error' => 'Please Provide Valid details!',
                ]
            ], 400);
        }
    }
    
    
      public function logout(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated!',
                    'data' => ['error' => 'User not authenticated']
                ], 401);
            }

            // Revoke user's current access token
            $accessToken = $request->user()->token();
            if ($accessToken) {
                $accessToken->revoke();
            }

            // Revoke all user tokens from the database (force logout from all devices)
            Token::where('user_id', $user->id)->update(['revoked' => true]);

            // Delete refresh tokens linked to revoked access tokens
            RefreshToken::whereIn('access_token_id', function ($query) use ($user) {
                $query->select('id')->from('oauth_access_tokens')->where('user_id', $user->id);
            })->delete();

            // Optionally, clear FCM token (for push notifications)
            // $user->fcm_token = null;
            // $user->save();

            return response()->json([
                'message' => 'Logout successful!',
                'data' => ['success' => true]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong!',
                'data' => ['error' => $e->getMessage()]
            ], 400);
        }
    }
     


    public function languages()
    {
        $languages = Language::select('id', 'language_name')
            ->where('chrPublish', 'Y')
            ->orderBy('language_name', 'asc')
            ->get();

        return response()->json([
            'message' => 'successful!',
            'data' => [
                'languages' => $languages,
            ],
        ], 200);
    }

    public function getUserData(Request $request){
        $userData = $request->user();
        if($userData->chrApproval == 'Y'){
        $userId = $userData->id;
        $user = User::select('id','name as first_name','surname','category','country','state','email','gmc_registration_no','indemnity_insurance_provider','policy_no','india_registration_no','dha_reg','reg_no','chrSmartcard','varProfile as profile_picture','varSpeciality as speciality','varExperience as total_experience','varPostGraduation as post_graduation','varPostGraduationYear as pg_year','varGraduation as graduation','varGraduationYear as graduation_year','varFees as fees','varTimeDuration as consultation_time')->where('id',$userId)->first();
        $current_work_org = $this->getCurrentWorkOrg($userId, true);
        $current_work_org = json_encode($current_work_org);
        $user->current_work_org = $current_work_org;
        if(!empty($user->profile_picture)){
            $user->profile_picture = config('app.url').'api/docterprofile/'.$user->profile_picture;
        }
        return response()->json([
            'message'=> 'successful!',
            'data'=>[
                'user' => $user
                ]
            ],200);
        }
         return response()->json([
                'message' => 'Your account is currently pending approval. Please contact your administration for further assistance.',
                'status' => 200
            ], 200);
    }


    
    public function getHome(Request $request){
        $userData = $request->user();
        $nexAppointment = [];
        $TodaysAppointment = [];
        if($userData->chrApproval == 'Y'){
        $userId = $userData->id;
        $today = Carbon::today(); // keep it as Carbon
        $current = $today->format('Y-m-d');
        // echo'<pre>';print_r($current);exit();

        $currentDateTime = now();
        $user = User::select('id','name as first_name','surname','category','country','state','email','gmc_registration_no','indemnity_insurance_provider','policy_no','india_registration_no','dha_reg','reg_no','chrSmartcard','varProfile as profile_picture','varSpeciality as speciality','varExperience as total_experience','varPostGraduation as post_graduation','varPostGraduationYear as pg_year','varGraduation as graduation','varGraduationYear as graduation_year','varFees as Fees','varTimeDuration as Consaltation Time')->where('id',$userId)->first();
        $current_work_org = $this->getCurrentWorkOrg($userId);
        $current_work_org = json_encode($current_work_org);
        $user->current_work_org = $current_work_org;
        if(!empty($user->profile_picture)){
            $user->profile_picture = config('app.url').'api/docterprofile/'.$user->profile_picture;
        }
        $RequestData = Appointment::select('appointment.id', 'appointment.patient_id as patient', 'appointment.dr_id as doctor', 'appointment.varAppointment as date', 'appointment.startTime as start time', 'appointment.endTime as end time', 'appointment.varSympton as sympton', 'appointment.varSymptondesc as description', 'appointment.chrIsAccepted as isAccepted', 'appointment.charIsPaid as isPaid','patients.name as first name','patients.lastname as last name','patients.varProfile')->join('patients','appointment.patient_id','patients.id')->where('dr_id',$userId)->where('chrIsAccepted','N')->where('chrIsRejected','N')->whereDate('appointment.varAppointment','>=',$today)->orderBy('appointment.varAppointment', 'asc')->orderBy('appointment.startTime', 'asc')->limit(5)->get();
    //    echo'<pre>';print_r($RequestData);exit();
        $requestResponse = [];
        if (isset($RequestData) && count($RequestData) > 0) {
            foreach ($RequestData as $rd) {
                $isToday = $rd->date === $today->format('Y-m-d');
                 if ($isToday && $rd['end time'] >= now()->format('H:i')) {
                     $rd->varProfile = !empty($rd->varProfile) ? config('app.url') . 'api/patientprofile/' . $rd->varProfile : 'null';
                    $requestResponse[] = $rd;
                }else if(!$isToday){
                     $rd->varProfile = !empty($rd->varProfile) ? config('app.url') . 'api/patientprofile/' . $rd->varProfile : 'null';
                    $requestResponse[] = $rd;
                }
            }
        }
        $TodaysAppointment = Appointment::select(
            'appointment.id',
            'appointment.patient_id AS patient',
            'appointment.dr_id AS doctor',
            'appointment.varAppointment AS date',
            'appointment.startTime AS start_time',
            'appointment.endTime AS end_time',
            'appointment.varSympton AS sympton',
            'appointment.varSymptondesc AS description',
            'appointment.chrIsAccepted AS isAccepted',
            'appointment.charIsPaid AS isPaid',
            'patients.name AS first_name',
            'patients.lastname AS last_name',
            'patients.varProfile'
        )
        ->join('patients', 'appointment.patient_id', '=', 'patients.id')
        ->where('appointment.dr_id', $userId)
        ->where('appointment.chrIsAccepted', 'Y')
        ->where('appointment.charIsPaid', 'Y')
        ->where('appointment.chrIsCanceled', 'N')
        ->whereDate('appointment.varAppointment',$today)
        ->whereTime('appointment.endTime', '>=', $currentDateTime->format('H:i'))
        ->orderBy('appointment.varAppointment', 'asc')
        ->orderBy('appointment.startTime', 'asc')
        ->limit(5)
        ->get();

        if (isset($TodaysAppointment) && count($TodaysAppointment) > 0) {
            foreach ($TodaysAppointment as $tdata) {
                $tdata->varProfile = !empty($tdata->varProfile) ? config('app.url') . 'api/patientprofile/' . $tdata->varProfile : 'null';
            }
        }



        $nexAppointment = Appointment::select(
            'appointment.id',
            'appointment.patient_id AS patient',
            'appointment.dr_id AS doctor',
            'appointment.varAppointment AS date',
            'appointment.startTime AS start_time',
            'appointment.endTime AS end_time',
            'appointment.varSympton AS sympton',
            'appointment.varSymptondesc AS description',
            'appointment.chrIsAccepted AS isAccepted',
            'appointment.charIsPaid AS isPaid',
            'patients.name AS first_name',
            'patients.lastname AS last_name',
            'patients.varProfile'
        )
        ->join('patients', 'appointment.patient_id', '=', 'patients.id')
        ->where('appointment.dr_id', $userId)
        ->where('appointment.chrIsAccepted', 'Y')
        ->where('appointment.charIsPaid', 'Y')
        ->where('appointment.chrIsCanceled', 'N')
        ->whereDate('appointment.varAppointment','>',$today)
        ->orderBy('appointment.varAppointment', 'asc')  // First, sort by date
        ->orderBy('appointment.startTime', 'asc')
        ->limit(5)
        ->get();

        if (isset($nexAppointment) && count($nexAppointment) > 0) {
            foreach ($nexAppointment as $ndata) {
                $ndata->varProfile = !empty($ndata->varProfile) ? config('app.url') . 'api/patientprofile/' . $ndata->varProfile : 'null';
            }
        }

        return response()->json([
            'message'=> 'successful!',
            'data'=>[
                'doctor' => $user,
                'requestList'=> $requestResponse,
                'today-appointment' => $TodaysAppointment ? $TodaysAppointment : new \stdClass(),
                'next-appointment' => $nexAppointment ? $nexAppointment : new \stdClass(),
                ]
            ],200);
        }
         return response()->json([
                'message' => 'Your account is currently pending approval. Please contact your administration for further assistance.',
                'status' => 200
            ], 200);
    }



    public function getRequest(Request $request){
        $userData = $request->user();      
        if($userData->chrApproval == 'Y'){
        $userId = $userData->id;
        $today = Carbon::today();
        $currentDateTime = now();
        $user = User::select('id','name as first_name','surname','category','country','state','email','gmc_registration_no','indemnity_insurance_provider','policy_no','india_registration_no','dha_reg','reg_no','chrSmartcard','varProfile as profile_picture','varSpeciality as speciality','varExperience as total_experience','varPostGraduation as post_graduation','varPostGraduationYear as pg_year','varGraduation as graduation','varGraduationYear as graduation_year')->where('id',$userId)->first();
        $current_work_org = $this->getCurrentWorkOrg($userId);
        $current_work_org = json_encode($current_work_org);
        $user->current_work_org = $current_work_org;
        if(!empty($user->profile_picture)){
            $user->profile_picture = config('app.url').'api/docterprofile/'.$user->profile_picture;
        }
        $limit = !empty($request->pageSize) ? $request->pageSize : 5;
        $page = !empty($request->pageNumber) ? $request->pageNumber : 1;
        $RequestData = Appointment::select('appointment.id', 'appointment.patient_id as patient', 'appointment.dr_id as doctor', 'appointment.varAppointment as date', 'appointment.startTime as start time', 'appointment.endTime as end time', 'appointment.varSympton as sympton', 'appointment.varSymptondesc as description', 'appointment.chrIsAccepted as isAccepted', 'appointment.charIsPaid as isPaid','patients.name as first name','patients.lastname as last name','patients.varProfile')->join('patients','appointment.patient_id','patients.id')->where('dr_id',$userId)->where('chrIsAccepted','N')->where('chrIsRejected','N')->whereDate('appointment.varAppointment','>=',$today)->orderBy('appointment.varAppointment', 'asc')->orderBy('appointment.startTime', 'asc')->paginate($limit, ['*'], 'page', $page);
        $requestResponse = [];
            if (isset($RequestData) && count($RequestData) > 0) {
                foreach ($RequestData as $rd) {
                    $isToday = $rd->date === $today->format('Y-m-d');
                    if ($isToday && $rd['end time'] >= now()->format('H:i')) {
                     $rd->varProfile = !empty($rd->varProfile) ? config('app.url') . 'api/patientprofile/' . $rd->varProfile : 'null';
                    $requestResponse[] = $rd;
                }else if(!$isToday){
                     $rd->varProfile = !empty($rd->varProfile) ? config('app.url') . 'api/patientprofile/' . $rd->varProfile : 'null';
                    $requestResponse[] = $rd;
                }
                }
                $RequestDataResponse = [
                    'current_page' => $RequestData->currentPage(),
                    'request' => $requestResponse,                                
                    'total' => count($requestResponse),
                ];
            }
            return response()->json([
                'message'=> 'successful!',
                'data'=>[
                    'doctor' => $user,
                    'requestList'=> isset($RequestDataResponse) && !empty($RequestDataResponse) ? $RequestDataResponse : new \stdClass(),              
                    ]
                ],200);
        }
         return response()->json([
                'message' => 'Your account is currently pending approval. Please contact your administration for further assistance.',
                'status' => 200
            ], 200);
    }


    public function getTodayAppointment(Request $request){
        $userData = $request->user();
        $nexAppointment = [];
        $TodaysAppointment = [];
        if($userData->chrApproval == 'Y'){
        $userId = $userData->id;
        $user = User::select('id','name as first_name','surname','category','country','state','email','gmc_registration_no','indemnity_insurance_provider','policy_no','india_registration_no','dha_reg','reg_no','chrSmartcard','varProfile as profile_picture','varSpeciality as speciality','varExperience as total_experience','varPostGraduation as post_graduation','varPostGraduationYear as pg_year','varGraduation as graduation','varGraduationYear as graduation_year')->where('id',$userId)->first();
        $current_work_org = $this->getCurrentWorkOrg($userId);
        $current_work_org = json_encode($current_work_org);
        $user->current_work_org = $current_work_org;
        if(!empty($user->profile_picture)){
            $user->profile_picture = config('app.url').'api/docterprofile/'.$user->profile_picture;
        }
        $limit = !empty($request->pageSize) ? $request->pageSize : 5;
        $page = !empty($request->pageNumber) ? $request->pageNumber : 1;
        $today = Carbon::today();
        $currentDateTime = now();
        $TodaysAppointment = Appointment::select(
            'appointment.id',
            'appointment.patient_id AS patient',
            'appointment.dr_id AS doctor',
            'appointment.varAppointment AS date',
            'appointment.startTime AS start_time',
            'appointment.endTime AS end_time',
            'appointment.varSympton AS sympton',
            'appointment.varSymptondesc AS description',
            'appointment.chrIsAccepted AS isAccepted',
            'appointment.charIsPaid AS isPaid',
            'patients.name AS first_name',
            'patients.lastname AS last_name',
            'patients.varProfile'
        )
        ->join('patients', 'appointment.patient_id', '=', 'patients.id')
        ->where('appointment.dr_id', $userId)
        ->where('appointment.chrIsAccepted', 'Y')
        ->where('appointment.charIsPaid', 'Y')
        ->where('appointment.chrIsCanceled', 'N')
        ->whereDate('appointment.varAppointment',$today)
         ->whereTime('appointment.endTime', '>=', $currentDateTime->format('H:i'))
        ->orderBy('appointment.varAppointment', 'asc')  // First, sort by date
        ->orderBy('appointment.startTime', 'asc')
        ->paginate($limit, ['*'], 'page', $page);

        if (isset($TodaysAppointment) && count($TodaysAppointment) > 0) {
            foreach ($TodaysAppointment as $tdata) {
                $tdata->varProfile = !empty($tdata->varProfile) ? config('app.url') . 'api/patientprofile/' . $tdata->varProfile : 'null';
            }
            $appointmentDataResponse = [
                'current_page' => $TodaysAppointment->currentPage(),
                'appointment' => $TodaysAppointment->items(),                                
                'total' => $TodaysAppointment->total(),
            ];
        }



      

        return response()->json([
            'message'=> 'successful!',
            'data'=>[
                'doctor' => $user,              
                'today-appointment' => isset($appointmentDataResponse) && !empty($appointmentDataResponse) ? $appointmentDataResponse : new \stdClass(),               
                ]
            ],200);
        }
         return response()->json([
                'message' => 'Your account is currently pending approval. Please contact your administration for further assistance.',
                'status' => 200
            ], 200);
    }


    public function getNextAppointment(Request $request){
        $userData = $request->user();    
        if($userData->chrApproval == 'Y'){
        $userId = $userData->id;
        $user = User::select('id','name as first_name','surname','category','country','state','email','gmc_registration_no','indemnity_insurance_provider','policy_no','india_registration_no','dha_reg','reg_no','chrSmartcard','varProfile as profile_picture','varSpeciality as speciality','varExperience as total_experience','varPostGraduation as post_graduation','varPostGraduationYear as pg_year','varGraduation as graduation','varGraduationYear as graduation_year')->where('id',$userId)->first();
        $current_work_org = $this->getCurrentWorkOrg($userId);
        $current_work_org = json_encode($current_work_org);
        $user->current_work_org = $current_work_org;
        if(!empty($user->profile_picture)){
            $user->profile_picture = config('app.url').'api/docterprofile/'.$user->profile_picture;
        }
        $limit = !empty($request->pageSize) ? $request->pageSize : 5;
        $page = !empty($request->pageNumber) ? $request->pageNumber : 1;
        $today = Carbon::today();
        $nexAppointment = Appointment::select(
            'appointment.id',
            'appointment.patient_id AS patient',
            'appointment.dr_id AS doctor',
            'appointment.varAppointment AS date',
            'appointment.startTime AS start_time',
            'appointment.endTime AS end_time',
            'appointment.varSympton AS sympton',
            'appointment.varSymptondesc AS description',
            'appointment.chrIsAccepted AS isAccepted',
            'appointment.charIsPaid AS isPaid',
            'patients.name AS first_name',
            'patients.lastname AS last_name',
            'patients.varProfile'
        )
        ->join('patients', 'appointment.patient_id', '=', 'patients.id')
        ->where('appointment.dr_id', $userId)
        ->where('appointment.chrIsAccepted', 'Y')
        ->where('appointment.charIsPaid', 'Y')
        ->where('appointment.chrIsCanceled', 'N')
        ->whereDate('appointment.varAppointment','>',$today)
        ->orderBy('appointment.varAppointment', 'asc')  // First, sort by date
        ->orderBy('appointment.startTime', 'asc')
        ->paginate($limit, ['*'], 'page', $page);

        if (isset($nexAppointment) && count($nexAppointment) > 0) {
            foreach ($nexAppointment as $tdata) {
                $tdata->varProfile = !empty($tdata->varProfile) ? config('app.url') . 'api/patientprofile/' . $tdata->varProfile : 'null';
            }
            $nextappointmentDataResponse = [
                'current_page' => $nexAppointment->currentPage(),
                'appointment' => $nexAppointment->items(),                                
                'total' => $nexAppointment->total(),
            ];
        }


      

        return response()->json([
            'message'=> 'successful!',
            'data'=>[
                'doctor' => $user,              
                'next-appointment' => isset($nextappointmentDataResponse) && !empty($nextappointmentDataResponse) ? $nextappointmentDataResponse : new \stdClass(),               
                ]
            ],200);
        }
         return response()->json([
                'message' => 'Your account is currently pending approval. Please contact your administration for further assistance.',
                'status' => 200
            ], 200);
    }



     public function register(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string',
                'email' => 'required|string|email|unique:users|valid_email_domain',
                'password' => 'required|string',
                'language_ids' => 'nullable',
                // 'fcm_token' => 'required|string',
            ]);

            $languageIds = [];
            if ($request->has('language_ids') && !is_null($request->language_ids) && $request->language_ids !== '') {
                if (is_array($request->language_ids)) {
                    $languageIds = $request->language_ids;
                } else {
                    $decodedLanguageIds = json_decode($request->language_ids, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedLanguageIds)) {
                        $languageIds = $decodedLanguageIds;
                    } else {
                        $languageIds = array_map('trim', explode(',', (string) $request->language_ids));
                    }
                }

                $languageIds = array_values(array_unique(array_filter($languageIds, function ($id) {
                    return is_numeric($id) && (int) $id > 0;
                })));

                if (!empty($languageIds)) {
                    $languageCount = Language::whereIn('id', $languageIds)->count();
                    if ($languageCount !== count($languageIds)) {
                        return response()->json([
                            'message' => 'Please Provide Valid details!',
                            'data' => [
                                'error' => 'One or more language_ids are invalid.',
                            ]
                        ], 400);
                    }
                }
            }
            $time_range = GeneralSetting::select('field_value')->where('field_name', 'time_duration')->first();
            $user = new User();
            $user->name = $request->first_name;
            $user->surname = $request->surname;
            $user->category = $request->category;
            $user->country = $request->country;
            $user->state = $request->state;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->gmc_registration_no = $request->gmc_registration_no;
            $user->indemnity_insurance_provider = $request->indemnity_insurance_provider;
            $user->policy_no = $request->policy_no;
            $user->india_registration_no = $request->india_registration_no;
            $user->dha_reg = $request->dha_reg;
            $user->reg_no = $request->reg_no;
            $user->chrSmartcard = $request->chrSmartcard;
            $user->varFees = $request->fees;
            $user->varTimeDuration = $time_range->field_value;
            $user->fcm_token = $request->fcm_token; // Save FCM token
            if (!empty($request->file('profile_picture'))) {
                $destinationPath = 'api/docterprofile';
                $myimage = time() . '_' . $request->profile_picture->getClientOriginalName();
                $request->profile_picture->move(public_path($destinationPath), $myimage);
                $user->varProfile =  $myimage;
            }
            $user->varSpeciality = $request->speciality;
            $user->varExperience = $request->total_experience;
            $user->varPostGraduation = $request->post_graduation;
            $user->varPostGraduationYear = $request->pg_year;
            $user->varGraduation = $request->graduation;
            $user->varGraduationYear = $request->graduation_year;
            $user->language_ids = !empty($languageIds) ? json_encode($languageIds) : null;
            $user->save();
            if (isset($request->current_work_org) && !empty($request->current_work_org)) {
                $this->replaceCurrentWorkOrg($user->id, $request->current_work_org);
            }

                


              


            if (isset($request->current_work_org) && !empty($request->current_work_org)) {
                $current_work_org = $this->getCurrentWorkOrg($user->id, true);
                $current_work_org = json_encode($current_work_org);
                $user->current_work_org = $current_work_org;
            } else {
                $user->current_work_org = null;
            }
            if (!empty($user->varProfile)) {
                $user->varProfile = config('app.url') . 'api/docterprofile/' . $user->varProfile;
            }
             if (!empty($user)) {
                $user->isPaymentFlowRegistered = ($user->isPaymentFlowRegistered == 0)? 'false':'true';
            }

            // Send push notification using NotificationController
            if(isset($request->fcm_token) && !empty($request->fcm_token)){
                $notificationController = new NotificationController();
                $notificationController->sendPushNotification(
                    $user->fcm_token,
                    'Welcome to the platform!',
                    'Your registration was successful sent for approval.',
                    'doctor'
                );
            }

            Mail::to($user->email)->send(new DoctorRegistrationReceivedMail($user));

            return response()->json([
                'message' => 'Your request has been sent successfully for approval!',
                'data' => [
                    'user' => $user
                ]
            ], 200);
        } catch (\Exception $e) {

            //   dd($e);
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                // Check for the specific error on the email field
                $errors = $e->errors();
                if (isset($errors['email']) && in_array('The email has already been taken.', $errors['email'])) {
                    return response()->json([
                        'message' => 'This email is already registered!',
                        'data' => [
                            'error' => 'This email is already registered!',
                        ]
                    ], 400);
                }

                // Handle all other validation errors
                return response()->json([
                    'message' => 'Please Provide Valid details!',
                    'data' => [
                        'error' => 'Please Provide Valid details!',
                    ]
                ], 400);
            }


            return response()->json([
                'message' => 'Please Provide Valid details!',
                'data' => [
                    'error' => 'Please Provide Valid details!',
                ]
            ], 400);
        }
    }



    public function update(Request $request){
        $userId = $request->user()->id;
        if(isset($userId) && !empty($userId)){      
            $user = User::find($userId);
            $user->name = $request->first_name;
            $user->surname = $request->surname;
            $user->category = $request->category;
            $user->country = $request->country;
            $user->state = $request->state;
            $user->gmc_registration_no = $request->gmc_registration_no;
            $user->indemnity_insurance_provider = $request->indemnity_insurance_provider;
            $user->policy_no = $request->policy_no;
            $user->india_registration_no = $request->india_registration_no;
            $user->dha_reg = $request->dha_reg;
            $user->reg_no = $request->reg_no;
            $user->chrSmartcard = $request->chrSmartcard;
            $user->varFees = $request->fees;
            $user->varTimeDuration = $request->consultation_time;
            if($request->hasFile('profile_picture') && !empty($request->file('profile_picture'))) {
                $destinationPath = 'api/docterprofile';
                $file_path = public_path() .'api/docterprofile'.$user->varProfile;
                if(Storage::exists($file_path)) {
                   unlink($file_path);
                }
                $myimage = time().'_'.$request->profile_picture->getClientOriginalName();
                $request->profile_picture->move(public_path($destinationPath), $myimage);            
            $user->varProfile =  $myimage;
            }
            $user->varSpeciality = $request->speciality;
            $user->varExperience = $request->total_experience;        
            $user->varPostGraduation = $request->post_graduation;  
            $user->varPostGraduationYear = $request->pg_year;  
            $user->varGraduation = $request->graduation;  
            $user->varGraduationYear = $request->graduation_year; 
            $user->save();
            if(isset($request->current_work_org) && !empty($request->current_work_org)){
                $this->replaceCurrentWorkOrg($userId, $request->current_work_org);
            }

             if(isset($request->current_work_org) && !empty($request->current_work_org)){
            $current_work_org = $this->getCurrentWorkOrg($userId, true);
            $current_work_org = json_encode($current_work_org);
            $user->current_work_org = $current_work_org;
            }else{
                  $user->current_work_org = null;
            }
            if(!empty($user->varProfile)){
                $user->varProfile = config('app.url').'api/docterprofile/'.$user->varProfile;
            }


            return response()->json([
                'message' => 'Successfully Updated user!',
                'data' => [
                    'user' => $user
                ]
            ], 200);
        }
         return response()->json([
                'message' => 'Invalid details!',
                 'data' => [
                'error' => 'Invalid details!',
            ]], 400);
    }

    
    public function Availability(Request $request){
        $userId = $request->user()->id;
        if(isset($userId) && !empty($userId)){      
            $user = User::find($userId);       
            $user->chrIsAvailable = $request->isAvilable;   
            $user->save();           
            if(!empty($user->varProfile)){
                $user->varProfile = config('app.url').'api/docterprofile/'.$user->varProfile;
            }
            return response()->json([
                'message' => 'Success!',
                'data' => [
                    'user' => $user
                ]
            ], 200);
        }
         return response()->json([
                'message' => 'Invalid details!',
                 'data' => [
                'error' => 'Invalid details!',
            ]], 400);
    }


    public function patientRequest(Request $request){
        $userId = $request->user()->id;
        if(isset($userId) && !empty($userId)){      
            $user = User::find($userId);
            if(!empty($user->varProfile)){
                $user->varProfile = config('app.url').'api/docterprofile/'.$user->varProfile;
            }
            if(isset($request->requestId) && !empty($request->requestId)){
                $requestData = Appointment::find($request->requestId);
                if(isset($requestData) && !empty($requestData) && $requestData->patient_id == $request->patient) {
                    $patient = Patients::find($request->patient);
                    if(isset($request->isAccept) && $request->isAccept == 'Y'){
                        $requestData->chrIsAccepted = $request->isAccept;
                        
                          $notificationController = new NotificationController();
                        $notificationController->sendPushNotification(
                        $patient->fcm_token, // Assuming `fcm_token` is stored in the patient table
                            'Appointment Accepted',
                            'Your appointment request has been accepted by Dr. ' . $user->name,
                    'doctor'
                        );
                        $message = "Appointment Accepted Successfully";
                        
                    }else{
                        $requestData->chrIsAccepted = 'N';
                        $requestData->chrIsRejected = 'Y';
                        
                        
                        $notificationController = new NotificationController();
                        $notificationController->sendPushNotification(
                        $patient->fcm_token,
                            'Appointment Rejected',
                            'Your appointment request has been rejected by Dr. ' . $user->name . '. Reason: ' . $request->reason,
                            'patient'
                        );
                        $message = "Appointment Declined Successfully";
                    }
                    $requestData->varReason = $request->reason;
                    $requestData->save();  
                }
            }
            if(isset($requestData) && !empty($requestData->varReason)){
                $requestData->reason = $requestData->varReason;
                unset($requestData->varReason);
            }
            return response()->json([
                'message' =>  $message,
                'data' => [
                    'user' => $user,
                    'request' => $requestData
                ]
            ], 200);
        }
         return response()->json([
                'message' => 'Invalid details!',
                 'data' => [
                'error' => 'Invalid details!',
            ]], 400);
    }


    public function countries(Request $request){
        $countries = DB::table('country_master')
                        ->where('chrPublish','Y')
                        ->get();
        foreach($countries as $key => $country){
            $countries[$key]->countryImage = config('app.url').'flags/'.strtolower($country->code).'.png';
            
        }
        return response()->json([
            'message' => 'success',
            'data'=>[
            'countries' => $countries]
        ], 200);
    }  


     public function speciality(Request $request){
        $category = DB::table('dr_category')
                        ->get();
        return response()->json([
            'message' => 'success',
            'data'=>[
            'speciality' => $category]
        ], 200);
    } 

    public function stateList($countryId){
        $states = DB::table('states')
                        ->where('country_id',$countryId)
                        ->get();
        return response()->json([
            'message' => 'success',
            'data'=>[
            'states' => $states]
        ], 200);
    }  


     public function getPatientData(Request $request)
    {
        $userId = $request->user()->id;
        if (isset($userId) && !empty($userId)) {
            $user = User::find($userId);
            $patient = Patients::where('id', $request->patient)->first();
            // Get the block data for the patient (if any)
             $block = Block::where('patient_id', $request->patient)->where('dr_id',$userId)->first();

            // If no block data exists, set 'blockpatient' to 'n'
            if (isset($patient) && !empty($patient)) {
                $patient->blockpatient = isset($block) && !empty($block) ? $block->chrIsBlock : 'n';
            }
            if (!empty($user->varProfile)) {
                $user->varProfile = config('app.url') . 'api/docterprofile/' . $user->varProfile;
            }
            if (!empty($patient->varProfile)) {
                $patient->varProfile = config('app.url') . 'api/patientprofile/' . $patient->varProfile;
            }
            $bookingHistory = Appointment::select('*')->where('patient_id', $request->patient)
                        ->where('dr_id', $userId)
                        ->where('chrIsAccepted','Y')
                        ->where('charIsPaid','Y')
                       ->orderBy('varAppointment', 'desc')
                        ->orderBy('startTime', 'desc')
                        ->get();
            $bookingHistoryResponse = [];
            $bookingHistoryResponseData = [];
            if (isset($bookingHistory) && count($bookingHistory) > 0) {
                foreach ($bookingHistory as $booking) {
                    $bookingHistoryResponse['startTime'] = $booking->startTime;
                    $bookingHistoryResponse['endTime'] = $booking->endTime;
                    $bookingHistoryResponse['appointmentDate'] = $booking->varAppointment;
                    $bookingHistoryResponse['sympton'] = $booking->varSympton;
                    $bookingHistoryResponse['sympton_description'] = $booking->varSymptondesc;
                    $bookingHistoryResponse['IsPaid'] = $booking->charIsPaid;
                     $bookingHistoryResponse['Presc_Sympton'] = $booking->typeSympton;
                    $bookingHistoryResponse['Prescription'] = $booking->varPrescription;
                    $bookingHistoryResponseData[] = $bookingHistoryResponse;
                }
            }
            // Get next appointment after the current date
            $nextAppointment = Appointment::select('*')
                ->where('patient_id', $request->patient)
                ->where('dr_id', $userId)
                ->whereDate('varAppointment', '>=', now())  // Filter for appointments after the current date
                ->whereTime('endTime', '>', now())
                ->where('chrIsAccepted','Y')
                ->where('charIsPaid','Y')
                ->orderBy('varAppointment', 'asc')     // Order by appointment date, ascending
                ->orderBy('startTime', 'asc')
                ->first();  // Get the next appointment only

            $nextAppointmentResponse = [];
            if ($nextAppointment) {
                $nextAppointmentResponse = [
                    'startTime' => $nextAppointment->startTime,
                    'endTime' => $nextAppointment->endTime,
                    'appointmentDate' => $nextAppointment->varAppointment,
                    'sympton' => $nextAppointment->varSympton,
                    'sympton_description' => $nextAppointment->varSymptondesc,
                    'IsPaid' => $nextAppointment->charIsPaid,
                ];
            }

            return response()->json([
                'message' => 'Success!',
                'data' => [
                    'user' => $user,
                    'patient' => isset($patient) && !empty($patient) ? $patient : new \stdClass(),
                    'bookingHistory' => (isset($bookingHistoryResponseData) && !empty($bookingHistoryResponseData)) ? $bookingHistoryResponseData : [],
                    'nextAppointment' => (isset($nextAppointmentResponse) && !empty($nextAppointmentResponse)) ? $nextAppointmentResponse : new \stdClass(), // Include next appointment data
                ]
            ], 200);
        }
        return response()->json([
            'message' => 'Invalid details!',
            'data' => [
                'error' => 'Invalid details!',
            ]
        ], 400);
    }

    // Patient Appointment History
    public function getPatientHistory(Request $request){
        $userId = $request->user()->id;
        if(isset($userId) && !empty($userId)){      
            $user = User::find($userId);
            $patient = Patients::find($request->patient);
            if(!empty($user->varProfile)){
                $user->varProfile = config('app.url').'api/docterprofile/'.$user->varProfile;
            }
            if(!empty($patient->varProfile)){
                $patient->varProfile = config('app.url').'api/patientprofile/'.$patient->varProfile;
            }   
            $bookingHistory = Appointment::select('*')->where('patient_id', $request->patient)
                                            ->where('dr_id', $userId)
                                            ->where('chrIsAccepted','Y')
                                            ->where('charIsPaid','Y')
                                            ->orderBy('varAppointment', 'desc')
                                            ->orderBy('startTime', 'desc')
                                            ->get();

            $bookingHistoryResponse = [];
            $bookingHistoryResponseData = [];
            if(isset($bookingHistory) && count($bookingHistory) > 0){
                foreach($bookingHistory as $booking){
                    $bookingHistoryResponse['startTime'] = $booking->startTime;
                    $bookingHistoryResponse['endTime'] = $booking->endTime;
                    $bookingHistoryResponse['appointmentDate'] = $booking->varAppointment;
                    $bookingHistoryResponse['sympton'] = $booking->varSympton;
                    $bookingHistoryResponse['sympton_description'] = $booking->varSymptondesc;
                    $bookingHistoryResponse['IsPaid'] = $booking->charIsPaid;
                     $bookingHistoryResponse['Presc_Sympton'] = $booking->typeSympton;
                    $bookingHistoryResponse['Prescription'] = $booking->varPrescription;
                    $bookingHistoryResponseData[] = $bookingHistoryResponse;
                }
            }
           // Get next appointment after the current date      
    
    return response()->json([
        'message' => 'Success!',
        'data' => [
            'user' => $user,
            'patient' => $patient,
            'bookingHistory' => $bookingHistoryResponseData,
        ]
    ], 200);
}
         return response()->json([
                'message' => 'Invalid details!',
                 'data' => [
                'error' => 'Invalid details!',
            ]], 400);
    }


    public function BlockUnblock(Request $request){
        $userId = $request->user()->id;
        if(isset($userId) && !empty($userId)){      
            $user = User::find($userId);
            if(!empty($user->varProfile)){
                $user->varProfile = config('app.url').'api/docterprofile/'.$user->varProfile;
            }
            if(isset($request->patient) && !empty($request->patient)){
                $blockData = Block::where('patient_id',$request->patient)->where('dr_id',$userId)->first();
                if(isset($blockData) && !empty($blockData)) {
                    if(isset($request->block) && !empty($request->block)){
                        $blockData->chrIsBlock = $request->block;
                    }
                    $blockData->varReason = $request->reason;
                    $blockData->save();  
                } else{
                    $blockData = new Block();
                    $blockData->patient_id = $request->patient;
                    $blockData->dr_id = $userId;
                    $blockData->chrIsBlock = $request->block;
                    $blockData->varReason = $request->reason;
                    $blockData->save();
                }
                
            }
            if(isset($blockData)){
                $blockData->reason = $blockData->varReason;
                unset($blockData->varReason);
            }
             if($request->block == 'Y'){
                $message = 'Patient blocked Successfully!';
            }else{
                $message = 'Patient unblock Successfully!';
            }
            return response()->json([
                'message' => $message,
                'data' => [
                    'user' => $user,
                    'blockData' => $blockData
                ]
            ], 200);
        }
         return response()->json([
                'message' => 'Invalid details!',
                 'data' => [
                'error' => 'Invalid details!',
            ]], 400);
    }
    
     //doctor Schedule
    public function getSchedule(Request $request){
        $userData = $request->user();
        try{
            $request->validate([
                'date' => 'required|date_format:Y-m-d',
            ]);
            if($userData->chrApproval == 'Y'){
            $userId = $userData->id;
            $user = User::select('id','name as first_name','surname','category','country','state','email','gmc_registration_no','indemnity_insurance_provider','policy_no','india_registration_no','dha_reg','reg_no','chrSmartcard','varProfile as profile_picture','varSpeciality as speciality','varExperience as total_experience','varPostGraduation as post_graduation','varPostGraduationYear as pg_year','varGraduation as graduation','varGraduationYear as graduation_year','varFees as fees','varTimeDuration as consultation_time')->where('id',$userId)->first();
            $current_work_org = $this->getCurrentWorkOrg($userId, true);
            $current_work_org = json_encode($current_work_org);
            $user->current_work_org = $current_work_org;
            if(!empty($user->profile_picture)){
                $user->profile_picture = config('app.url').'api/docterprofile/'.$user->profile_picture;
            }
            $date = $request->date;
            $scheduleData = Appointment::select(
                'appointment.id',
                'appointment.patient_id AS patient',
                'appointment.dr_id AS doctor',
                'appointment.varAppointment AS date',
                'appointment.startTime AS start_time',
                'appointment.endTime AS end_time',
                'appointment.varSympton AS sympton',
                'appointment.varSymptondesc AS description',
                'appointment.chrIsAccepted AS isAccepted',
                'appointment.charIsPaid AS isPaid',
                'patients.name AS first_name',
                'patients.lastname AS last_name'
            )
            ->join('patients', 'appointment.patient_id', '=', 'patients.id')
            ->where('appointment.dr_id', $userId)
            ->where('appointment.chrIsAccepted', 'Y')
            ->where('appointment.charIsPaid', 'Y')
            ->where('appointment.charIsPaid', 'Y')
            ->where('appointment.chrIsCanceled', 'N')
            ->whereDate('appointment.varAppointment','=',$date)
            ->orderBy('appointment.varAppointment', 'asc')  // First, sort by date
            ->orderBy('appointment.startTime', 'asc')
            ->get();
            return response()->json([
                'message'=> 'successful!',
                'data'=>[
                    'user' => $user,
                    'schedule'=> (isset($scheduleData) && !empty($scheduleData)) ? $scheduleData : new \stdClass()
                    ]
                ],200);
            }
             return response()->json([
                    'message' => 'Your account is currently pending approval. Please contact your administration for further assistance.',
                    'status' => 200
                ], 200);
        }catch(\Exception $e){
            return response()->json([
                'message' => 'Please Provide Valid details!',
                'data' => [
                    'error' => 'Please Provide Valid details!'
                ]
            ], 400);
        }
        
    }

    private function getCurrentWorkOrg(int $userId, bool $withCurrentWorkFlag = false)
    {
        $user = User::with(['experiences' => function ($query) use ($withCurrentWorkFlag) {
            $query->select(
                'user_id',
                'title as org_name',
                'startYear as start_year',
                'endYear as end_year',
                'varDescription as description'
            );

            if ($withCurrentWorkFlag) {
                $query->addSelect('isCurrentworkOrg');
            }
        }])->select('id')->find($userId);

        return $user?->experiences ?? collect();
    }

    private function replaceCurrentWorkOrg(int $userId, $currentWorkOrg): void
    {
        OrgExperience::where('user_id', $userId)->delete();

        $entries = is_array($currentWorkOrg) ? $currentWorkOrg : json_decode($currentWorkOrg, true);
        if (!is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            OrgExperience::create([
                'user_id' => $userId,
                'title' => $entry['org_name'] ?? null,
                'startYear' => $entry['start_year'] ?? null,
                'endYear' => $entry['end_year'] ?? null,
                'varDescription' => $entry['description'] ?? null,
                'isCurrentworkOrg' => $entry['isCurrentworkOrg'] ?? 'N',
            ]);
        }
    }


}
