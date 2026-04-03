<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Illuminate\Support\Facades\Http;
use App\Models\Patients;
use App\Models\Appointment;
use App\Models\Favourite;
use App\Models\GeneralSetting;
use App\Models\User;
use App\Models\Rating;
use Carbon\Carbon;
use Storage;
use DB;
use App\Http\Controllers\NotificationController;
use Exception;


class PatientController extends Controller
{

    public function listData()
    {
        $patients = Patients::get();
        return view('admin.patients', compact(['patients']));
    }
 public function deletePatient($id)
    {
        try {
            // Find the patient record by ID
            $patient = Patients::findOrFail($id);

            // Delete the patient record
            $patient->delete();

            return response()->json([
                'success' => true,
                'message' => 'Patient record deleted successfully.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting patient: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
                'fcm_token' => 'nullable|string', // Optional FCM token
            ]);

            // Find the patient by email
            $patient = Patients::where('email', $request->email)->first();

            // Check if the patient exists and the password is correct
            if ($patient && Hash::check($request->password, $patient->password)) {
                $patientsData = ["id" => $patient->id];
                $encodeToken = encrypt($patientsData);

                $patient->remember_token = $encodeToken;
                $patient->save();
                
                // Check for a new device (new FCM token)
                if ($request->fcm_token && $patient->fcm_token !== $request->fcm_token) {
                // If the FCM token is different, send a push notification to the previous device
                if ($patient->fcm_token) {
                    $notificationController = new NotificationController();
                    $notificationController->sendPushNotification(
                        $patient->fcm_token,
                        'New Device Login',
                        'Your account was logged in from a new device. If this was not you, please contact support.',
                        'patient'
                    );
                }

                    // Update the patient's FCM token to the new token
                    $patient->fcm_token = $request->fcm_token;
                    $patient->save();
                }



                return response()->json([
                    'message' => 'Login successful!',
                    'data' => [
                        'user' => [
                            'id' => $patient->id,
                            'name' => $patient->name,
                            'lastname' => $patient->lastname,
                            'country' => $patient->country,
                            'email' => $patient->email,
                            'profile' => !empty($patient->varProfile) ? config('app.url') . 'api/patientprofile/' . $patient->varProfile : 'null'
                        ],
                        'token_type' => "Bearer",
                        'access_token' => $encodeToken
                    ]
                ], 200);
            }

            return response()->json([
                'message' => 'Invalid credentials!',
                'data' => [
                    'error' => 'Invalid credentials!'
                ]
            ], 400);
        } catch (\Exception $e) {
            if (method_exists($e, 'errors')) {
                return response()->json([
                    'message' => 'Please Provide Valid details!',
                    'data' => [
                        'error' => 'Please Provide Valid details!'
                    ]
                ], 400);
            }

            return response()->json([
                'message' => 'Please Provide Valid details!',
                'data' => [
                    'error' => 'Please Provide Valid details!'
                ],
            ], 400);
        }
    }
    
     public function logout(Request $request)
    {
        try {
            // Extract patient ID from the token
            $decodedData = decrypt($request->bearerToken()); // Decrypt token
            $patientId = $decodedData['id'] ?? null;
    
            if (!$patientId) {
                return response()->json([
                    'message' => 'Invalid token!',
                    'data' => ['error' => 'Invalid token!']
                ], 400);
            }
    
            // Find the patient by ID
            $patient = Patients::find($patientId);
    
            if (!$patient) {
                return response()->json([
                    'message' => 'User not found!',
                    'data' => ['error' => 'User not found!']
                ], 404);
            }
    
            // Remove the stored token to invalidate it
            $patient->remember_token = null;
            $patient->save();
    
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
    
    public function register(Request $request)
    {
        try {
            $request->validate([
                'first_name' => 'required|string',
                'email' => 'required|string|email|unique:patients|valid_email_domain',
                'password' => 'required|string',
                'fcm_token' => 'nullable|string', // Capture FCM token if provided
            ]);
            $patients = new Patients();
            $patients->name = $request->first_name;
            $patients->lastname = $request->last_name;
            $patients->country = $request->country;
            $patients->state = $request->state;
            $patients->email = $request->email;
            $patients->password = bcrypt($request->password);
            $patients->fcm_token = $request->fcm_token; // Store FCM token if provided
            if ($request->hasFile('profile_picture') && !empty($request->file('profile_picture'))) {
                $destinationPath = 'api/patientprofile';
                $myimage = time() . '_' . $request->profile_picture->getClientOriginalName();
                $request->profile_picture->move(public_path($destinationPath), $myimage);
                $patients->varProfile =  $myimage;
            }

            $patients->save();
            
            // Send push notification if FCM token is provided
            if (!empty($patients->fcm_token)) {
                $notificationController = new NotificationController();
                $notificationController->sendPushNotification(
                    $patients->fcm_token,
                    'Welcome to Our Application!',
                    'Thank you for registering with us. We are glad to have you onboard!',
                        'patient'
                );
            }
            
            return response()->json([
                'message' => 'Successfully created user!',
                'data' => [
                    'user' => [
                        'id' => $patients->id,
                        'name' => $patients->name,
                        'lastname' => $patients->lastname,
                        'country' => $patients->country,
                        'state' => $patients->state,
                        'email' => $patients->email,
                        'varProfile' => !empty($patients->varProfile) ? config('app.url') . 'api/patientprofile/' . $patients->varProfile : 'null'
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
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
                    'error' => 'Please Provide Valid details!'
                ],
            ], 400);
        }
    }

    // Feedback
    public function handleFeedback(Request $request)
    {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        if (!empty($headerArray[1])) {
            try {
                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {
                    $patients = Patients::find($tokenData['id']);
                    if (!empty($patients)) {
                        $request->validate([
                            'doctor' => 'required',
                            'ratings' => 'required',
                            'review' => 'required|string'
                        ]);
                        $review = new Rating();
                        $review->patinet_id = $tokenData['id'];
                        $review->doctor_id = $request->doctor;
                        $review->rating = $request->ratings;
                        $review->varShortTitle = $request->shorttitle;
                        $review->varReview = $request->review;

                        $review->save();

                        return response()->json([
                            'message' => 'Reviewed Succsessfully!',
                            'data' => [
                                'review' => [
                                    'patient' => $review->patinet_id,
                                    'doctor' => $review->doctor_id,
                                    'ratings' => $review->rating,
                                    'shorttitle' => $review->varShortTitle,
                                    'review' => $review->varReview
                                ]
                            ]
                        ], 200);
                    }  return response()->json([
                        'message' => 'Unauthorized request',
                        'data' => [
                            'error' => 'Unauthorized request'
                        ]
                    ], 401);
                }
            } catch (\Exception $e) {
                if (method_exists($e, 'errors')) {
                    return response()->json([
                        'message' => 'Please Provide Valid details!',
                        'data' => [
                            'error' => 'Please Provide Valid details!'
                        ]
                    ], 400);
                }

                return response()->json([
                    'message' => 'Please Provide Valid details!',
                    'data' => [
                        'error' => 'Please Provide Valid details!'
                    ],
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


    public function updateData(Request $request)
    {

        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        if (!empty($headerArray[1])) {
            try {
                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {
                    $patients = Patients::find($tokenData['id']);
                    $patients->name = $request->first_name;
                    $patients->lastname = $request->last_name;
                    $patients->country = $request->country;
                    $patients->state = $request->state;
                    if ($request->hasFile('profile_picture') && !empty($request->file('profile_picture'))) {
                        $destinationPath = '/api/patientprofile/';
                        $file_path = public_path() . $destinationPath . $patients->varProfile;
                        if (Storage::exists($file_path)) {
                            unlink($file_path);
                        }
                        $myimage = time() . '_' . $request->profile_picture->getClientOriginalName();
                        $request->profile_picture->move(public_path($destinationPath), $myimage);
                        $patients->varProfile =  $myimage;
                    }

                    $patients->save();

                    return response()->json([
                        'message' => 'Successfully Updated user!',
                        'data' => [
                            'user' => [
                                'id' => $patients->id,
                                'name' => $patients->name,
                                'lastname' => $patients->lastname,
                                'country' => $patients->lastname,
                                'state' => $patients->state,
                                'email' => $patients->email,
                                'varProfile' => !empty($patients->varProfile) ? config('app.url') . 'api/patientprofile/' . $patients->varProfile : 'null'
                            ]
                        ]
                    ], 200);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Unauthorized request',
                    'data' => [
                        'error' => 'Unauthorized request'
                    ]
                ], 401);
            }
        }
    }


    public function patientDetails(Request $request)
    {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        if (!empty($headerArray[1])) {
            try {
                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {
                    $patient = Patients::find($tokenData['id']);
                    if (!empty($patient)) {
                        $patient->varProfile =  !empty($patient->varProfile) ? config('app.url') . 'api/patientprofile/' . $patient->varProfile : 'null';
                        return response()->json([
                            'message' => 'success',
                            'data' => [
                                'patient' => [$patient]
                            ]
                        ], 200);
                    }
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Unauthorized request',
                    'data' => [
                        'error' => 'Unauthorized request'
                    ]
                ], 401);
            }
        }
        return response()->json([
            'message' => 'Unauthorized request',
            'data' => [
                'error' => 'Unauthorized request'
            ]
        ], 401);
    }
    // Home
    public function home(Request $request)
    {
        $headers = $request->header('Authorization');
        $today = Carbon::today();
        $currentDateTime = now();
        $headerArray = explode('Bearer ', $headers);
        if (!empty($headerArray[1])) {
            try {
                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {

                    $patient = Patients::find($tokenData['id']);
                    if (!empty($patient)) {
                        $topDoctor = User::select(
                            'users.id',
                            'users.name',
                            'users.email',
                            'users.surname',
                            'users.country',
                            'users.state',
                            'users.gmc_registration_no',
                            'users.indemnity_insurance_provider',
                            'users.policy_no',
                            'users.india_registration_no',
                            'users.dha_reg',
                            'users.reg_no',
                            'users.chrSmartcard',
                            'users.varProfile',
                            'users.varSpeciality',
                            'users.varExperience',
                            'users.varPostGraduation',
                            'users.varPostGraduationYear',
                            'users.varGraduation',
                            'users.varGraduationYear',
                            'users.chrApproval',
                            'users.category as category',
                            'dr_category.title as categoryName',
                            'users.varFees as Fees',
                            'users.varTimeDuration as Consaltation Time'
                        )
                            ->where('category', $request->speciality)
                            ->where('chrApproval', 'Y')
                            ->join('dr_category', 'users.category', '=', 'dr_category.id')
                            ->where('country', $patient->country)
                            ->leftJoin('block', function($join) use ($patient) {
                                $join->on('block.dr_id', '=', 'users.id')
                                     ->where('block.patient_id', '=', $patient->id)
                                     ->where('block.chrIsBlock', '=', 'Y'); // Only blocked doctors
                            })
                            ->whereNull('block.id')
                            ->limit('5')
                            ->get();
                        $favDoctor = DB::table('favourite')
                            ->select(
                                'users.id',
                                'users.name',
                                'users.email',
                                'users.surname',
                                'users.country',
                                'users.state',
                                'users.gmc_registration_no',
                                'users.indemnity_insurance_provider',
                                'users.policy_no',
                                'users.india_registration_no',
                                'users.dha_reg',
                                'users.reg_no',
                                'users.chrSmartcard',
                                'users.varProfile',
                                'users.varSpeciality',
                                'users.varExperience',
                                'users.varPostGraduation',
                                'users.varPostGraduationYear',
                                'users.varGraduation',
                                'users.varGraduationYear',
                                'users.rattings',
                                'users.varFees as Fees',
                                'users.varTimeDuration as Consaltation Time',
                                'users.chrApproval',
                                'users.category as category',
                                'dr_category.title as categoryName',
                                'favourite.chrFav as isFavouriteFlag'
                            )
                            ->join('users', 'favourite.user_id', '=', 'users.id') // Ensure user_id exists in the favourite table
                            ->join('dr_category', 'users.category', '=', 'dr_category.id')
                            ->where('category', $request->speciality)
                            ->where('favourite.patinet_id', $patient->id) // Make sure to prefix the column with the table name
                            ->where('favourite.chrFav', 'Y')
                            ->leftJoin('block', function($join) use ($patient) {
                                $join->on('block.dr_id', '=', 'users.id')
                                     ->where('block.patient_id', '=', $patient->id)
                                     ->where('block.chrIsBlock', '=', 'Y'); // Only blocked doctors
                            })
                            ->whereNull('block.id')
                            ->limit('5')->get();

                        $acceptedApointment = Appointment::getAcceptedList($patient->id);
                        // dd(now()->format('H:i'));
                        $rejectedAppointment = Appointment::getRejectedList($patient->id);
                        $acceptedResponse = [];
                        if (isset($acceptedApointment) && count($acceptedApointment) > 0) {
                            foreach ($acceptedApointment as $accepted) {
                                $isToday = $accepted->varAppointment === $today->format('Y-m-d');
                                if ($isToday && $accepted['endTime'] >= now()->format('H:i')) {
                                    if (!empty($accepted->varReason)) {
                                        $accepted->reason = $accepted->varReason;
                                        unset($accepted->varReason);
                                    } else {
                                        $accepted->reason = $accepted->varReason;
                                        unset($accepted->varReason);
                                    }
                                } else if (!$isToday) {
                                    if (!empty($accepted->varReason)) {
                                        $accepted->reason = $accepted->varReason;
                                        unset($accepted->varReason);
                                    } else {
                                        $accepted->reason = $accepted->varReason;
                                        unset($accepted->varReason);
                                    }
                                }
                                  $accepted->varProfile = !empty($accepted->varProfile) ? config('app.url') . 'api/docterprofile/' . $accepted->varProfile : 'null';
                                $acceptedResponse[] = $accepted;
                            }
                        }

                        if (isset($rejectedAppointment) && count($rejectedAppointment) > 0) {
                            foreach ($rejectedAppointment as $rejected) {
                                $rejected->varProfile = !empty($rejected->varProfile) ? config('app.url') . 'api/docterprofile/' . $rejected->varProfile : 'null';


                                if (isset($rejected) && !empty($rejected->varReason)) {
                                    $rejected->reason = $rejected->varReason;
                                    unset($rejected->varReason);
                                } else {
                                    $rejected->reason = $rejected->varReason;
                                    unset($rejected->varReason);
                                }
                            }
                        }
                        if (isset($topDoctor) && count($topDoctor) > 0) {
                            foreach ($topDoctor as $key => $dr) {
                                $dr->varProfile = !empty($dr->varProfile) ? config('app.url') . 'api/docterprofile/' . $dr->varProfile : 'null';
                                $fav = DB::table('favourite')->select('chrFav as isFavouriteFlag')->where('user_id', $dr->id)->where('patinet_id', $patient->id)->first();
                                if (isset($fav) && !empty($fav)) {
                                    $dr->isFavouriteFlag = $fav->isFavouriteFlag;
                                } else {
                                    $dr->isFavouriteFlag = 'N';
                                }
                                
                                if(!empty($dr)){
                                    $ratingData = Rating::selectRaw('IFNULL(AVG(ratings.rating), 0) as ratings')
                                                        ->selectRaw('IFNULL(COUNT(ratings.id), 0) as review_count')
                                                        ->where('doctor_id', $dr->id)
                                                        ->first();
                                    $dr->ratings = $ratingData->ratings;
                                    $dr->review_count = $ratingData->review_count;
                                
                                }
            
                                if (is_null($ratingData)) {
                                    $dr->ratings = 0;
                                    $dr->review_count = 0;
                                }
            
                                // Structure the ratings as an object with keys 'ratings' and 'review_count' inside an array
                                $dr->ratings = [
                                    [
                                        'ratings' => $dr->ratings,
                                        'review_count' => $dr->review_count
                                    ]
                                ];
            
                                // Remove the review_count field from the root object (optional)
                                unset($dr->review_count);
                            }
                        }
                        if (isset($favDoctor) && count($favDoctor) > 0) {
                            foreach ($favDoctor as $key => $favdr) {
                                $favdr->varProfile = !empty($favdr->varProfile) ? config('app.url') . 'api/docterprofile/' . $favdr->varProfile : 'null';
                                
                                if(!empty($favdr)){
                                    $ratingData = Rating::selectRaw('IFNULL(AVG(ratings.rating), 0) as ratings')
                                                        ->selectRaw('IFNULL(COUNT(ratings.id), 0) as review_count')
                                                        ->where('doctor_id', $favdr->id)
                                                        ->first();
                                    $favdr->ratings = $ratingData->ratings;
                                    $favdr->review_count = $ratingData->review_count;
                                
                                }
            
                                if (is_null($ratingData)) {
                                    $favdr->ratings = 0;
                                    $favdr->review_count = 0;
                                }
            
                                // Structure the ratings as an object with keys 'ratings' and 'review_count' inside an array
                                $favdr->ratings = [
                                    [
                                        'ratings' => $favdr->ratings,
                                        'review_count' => $favdr->review_count
                                    ]
                                ];
            
                                // Remove the review_count field from the root object (optional)
                                unset($favdr->review_count);
                            }
                        }
                        $patient->varProfile =  !empty($patient->varProfile) ? config('app.url') . 'api/patientprofile/' . $patient->varProfile : 'null';
                        return response()->json([
                            'message' => 'success',
                            'data' => [
                                'patient' => [$patient],
                                'topdoctor' => $topDoctor,
                                'favourite' => $favDoctor,
                                'accepted' => isset($acceptedApointment) && !empty($acceptedApointment) ? $acceptedApointment : new \stdClass(),
                                'rejected' => isset($rejectedAppointment) && !empty($rejectedAppointment) ? $rejectedAppointment : new \stdClass(),
                            ]
                        ], 200);
                    }
                }
            } catch (\Exception $e) {
                dd($e);
                return response()->json([
                    'message' => 'Unauthorized request',
                    'data' => [
                        'error' => 'Unauthorized request'
                    ]
                ], 401);
            }
        }
        return response()->json([
            'message' => 'Unauthorized request',
            'data' => [
                'error' => 'Unauthorized request'
            ]
        ], 401);
    }

    // ViewAll
    public function viewAll(Request $request)
    {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        $topDoctorResponse = [];
        $favDoctorResponse = [];

        if (!empty($headerArray[1])) {
            try {
                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {
                    $patient = Patients::find($tokenData['id']);
                    if (!empty($patient)) {
                        $limit = !empty($request->pageSize) ? $request->pageSize : 5;
                        $page = !empty($request->pageNumber) ? $request->pageNumber : 1;

                        if (isset($request->topDoctor) && $request->topDoctor == 'Y') {
                            $topDoctor = User::select(
                                'users.id',
                                'users.name',
                                'users.email',
                                'users.surname',
                                'users.country',
                                'users.state',
                                'users.gmc_registration_no',
                                'users.indemnity_insurance_provider',
                                'users.policy_no',
                                'users.india_registration_no',
                                'users.dha_reg',
                                'users.reg_no',
                                'users.chrSmartcard',
                                'users.varProfile',
                                'users.varSpeciality',
                                'users.varExperience',
                                'users.varPostGraduation',
                                'users.varPostGraduationYear',
                                'users.varGraduation',
                                'users.varGraduationYear',
                                'users.chrApproval',
                                'users.category as speciality_id',
                                'users.varFees as Fees',
                                'users.varTimeDuration as Consaltation Time',
                                'dr_category.title as speciality'
                            )
                                ->where('users.chrApproval', 'Y')
                                ->where('users.category', $request->speciality)
                                ->join('dr_category', 'users.category', '=', 'dr_category.id')
                                ->where('users.country', $patient->country)
                                ->leftJoin('block', function($join) use ($patient) {
                                    $join->on('block.dr_id', '=', 'users.id')
                                         ->where('block.patient_id', '=', $patient->id)
                                         ->where('block.chrIsBlock', '=', 'Y'); // Only blocked doctors
                                })
                                ->whereNull('block.id')
                                ->paginate($limit, ['*'], 'page', $page);
                        }

                        if (isset($request->favDoctor) && $request->favDoctor == 'Y') {
                            $favDoctor = DB::table('favourite')
                                ->select(
                                    'users.id',
                                    'users.name',
                                    'users.email',
                                    'users.surname',
                                    'users.country',
                                    'users.state',
                                    'users.gmc_registration_no',
                                    'users.indemnity_insurance_provider',
                                    'users.policy_no',
                                    'users.india_registration_no',
                                    'users.dha_reg',
                                    'users.reg_no',
                                    'users.chrSmartcard',
                                    'users.varProfile',
                                    'users.varSpeciality',
                                    'users.varExperience',
                                    'users.varPostGraduation',
                                    'users.varPostGraduationYear',
                                    'users.varGraduation',
                                    'users.varGraduationYear',
                                    'users.varFees as Fees',
                                    'users.varTimeDuration as Consaltation Time',
                                    'users.chrApproval',
                                    'users.category as speciality_id',
                                    'dr_category.title as speciality',
                                    'favourite.chrFav as isFavouriteFlag'
                                )
                                ->join('users', 'favourite.user_id', '=', 'users.id') // Ensure user_id exists in the favourite table
                                ->join('dr_category', 'users.category', '=', 'dr_category.id')
                                ->where('users.category', $request->speciality)
                                ->where('favourite.patinet_id', $patient->id) // Make sure to prefix the column with the table name
                                ->where('favourite.chrFav', 'Y') // Same here   
                                ->leftJoin('block', function($join) use ($patient) {
                                    $join->on('block.dr_id', '=', 'users.id')
                                         ->where('block.patient_id', '=', $patient->id)
                                         ->where('block.chrIsBlock', '=', 'Y'); // Only blocked doctors
                                })
                                ->whereNull('block.id')                            
                                ->paginate($limit, ['*'], 'page', $page);
                        }


                        $acceptedApointment = Appointment::getAllAcceptedList($patient->id, $limit, $page);
                        if (isset($acceptedApointment) && count($acceptedApointment) > 0) {
                            foreach ($acceptedApointment as $accepted) {
                                $accepted->varProfile = !empty($accepted->varProfile) ? config('app.url') . 'api/docterprofile/' . $accepted->varProfile : 'null';

                                if (isset($accepted) && !empty($accepted->varReason)) {
                                    $accepted->reason = $accepted->varReason;
                                    unset($accepted->varReason);
                                } else {
                                    $accepted->reason = $accepted->varReason;
                                    unset($accepted->varReason);
                                }
                            }

                            $acceptedApointmentResponse = [
                                'accepted-appointment' => $acceptedApointment,
                                'total' => $acceptedApointment->count(),
                            ];
                        }
                        $rejectedAppointment = Appointment::getAllRejectedList($patient->id, $limit, $page);

                        if (isset($rejectedAppointment) && count($rejectedAppointment) > 0) {
                            foreach ($rejectedAppointment as $rejected) {
                                $rejected->varProfile = !empty($rejected->varProfile) ? config('app.url') . 'api/docterprofile/' . $rejected->varProfile : 'null';

                                if (isset($rejected) && !empty($rejected->varReason)) {
                                    $rejected->reason = $rejected->varReason;
                                    unset($rejected->varReason);
                                } else {
                                    $rejected->reason = $rejected->varReason;
                                    unset($rejected->varReason);
                                }
                            }

                            $rejectedAppointmentResponse = [
                                'rejected-appointment' => $rejectedAppointment,
                                'total' => $rejectedAppointment->count(),
                            ];
                        }


                        // Format topDoctor profiles
                        if (isset($topDoctor) && count($topDoctor) > 0) {
                            foreach ($topDoctor as $dr) {
                                $dr->varProfile = !empty($dr->varProfile) ? config('app.url') . 'api/docterprofile/' . $dr->varProfile : 'null';

                                $fav = DB::table('favourite')->select('chrFav as isFavouriteFlag')->where('user_id', $dr->id)->where('patinet_id', $patient->id)->first();
                                if (isset($fav) && !empty($fav)) {
                                    $dr->isFavouriteFlag = $fav->isFavouriteFlag;
                                } else {
                                    $dr->isFavouriteFlag = 'N';
                                }
                                
                                if(!empty($dr)){
                                    $ratingData = Rating::selectRaw('IFNULL(AVG(ratings.rating), 0) as ratings')
                                                        ->selectRaw('IFNULL(COUNT(ratings.id), 0) as review_count')
                                                        ->where('doctor_id', $dr->id)
                                                        ->first();
                                    $dr->ratings = $ratingData->ratings;
                                    $dr->review_count = $ratingData->review_count;
                                
                                }
            
                                if (is_null($ratingData)) {
                                    $dr->ratings = 0;
                                    $dr->review_count = 0;
                                }
            
                                // Structure the ratings as an object with keys 'ratings' and 'review_count' inside an array
                                $dr->ratings = [
                                    [
                                        'ratings' => $dr->ratings,
                                        'review_count' => $dr->review_count
                                    ]
                                ];
            
                                // Remove the review_count field from the root object (optional)
                                unset($dr->review_count);
                            }

                            $topDoctorResponse = [
                                'current_page' => $topDoctor->currentPage(),
                                'doctor' => $topDoctor->items(),
                                'total' => $topDoctor->total(),
                            ];
                        }

                        // Format favourite doctor profiles
                        if (isset($favDoctor) && count($favDoctor) > 0) {
                            foreach ($favDoctor as $favdr) {
                                $favdr->varProfile = !empty($favdr->varProfile) ? config('app.url') . 'api/docterprofile/' . $favdr->varProfile : 'null';
                                
                                 if(!empty($favdr)){
                                    $ratingData = Rating::selectRaw('IFNULL(AVG(ratings.rating), 0) as ratings')
                                                        ->selectRaw('IFNULL(COUNT(ratings.id), 0) as review_count')
                                                        ->where('doctor_id', $favdr->id)
                                                        ->first();
                                    $favdr->ratings = $ratingData->ratings;
                                    $favdr->review_count = $ratingData->review_count;
                                
                                }
            
                                if (is_null($ratingData)) {
                                    $favdr->ratings = 0;
                                    $favdr->review_count = 0;
                                }
            
                                // Structure the ratings as an object with keys 'ratings' and 'review_count' inside an array
                                $favdr->ratings = [
                                    [
                                        'ratings' => $favdr->ratings,
                                        'review_count' => $favdr->review_count
                                    ]
                                ];
            
                                // Remove the review_count field from the root object (optional)
                                unset($favdr->review_count);
                            }
                            $favDoctorResponse = [
                                'current_page' => $favDoctor->currentPage(),
                                'doctor' => $favDoctor->items(),
                                'total' => $favDoctor->total(),
                            ];
                        }

                        $patient->varProfile = !empty($patient->varProfile) ? config('app.url') . 'api/patientprofile/' . $patient->varProfile : 'null';

                        // Return the response
                        return response()->json([
                            'message' => 'success',
                            'data' => [
                                'patient' => [$patient],
                                'topdoctor' =>  isset($topDoctorResponse) && !empty($topDoctorResponse) ? $topDoctorResponse : new \stdClass(),
                                'favourite' => isset($favDoctorResponse) && !empty($favDoctorResponse) ? $favDoctorResponse : new \stdClass(),
                                'accepted' => isset($acceptedApointmentResponse) && !empty($acceptedApointmentResponse) ? $acceptedApointmentResponse : new \stdClass(),
                                'rejected' => isset($rejectedAppointmentResponse) && !empty($rejectedAppointmentResponse) ? $rejectedAppointmentResponse : new \stdClass(),
                            ]
                        ], 200);
                    }
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Unauthorized request',
                    'data' => [
                        'error' => 'Unauthorized request'
                    ]
                ], 401);
            }
        }

        return response()->json([
            'message' => 'Unauthorized request',
            'data' => [
                'error' => 'Unauthorized request'
            ]
        ], 401);
    }


    public function Favourite(Request $request)
    {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        if (!empty($headerArray[1])) {
            try {
                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {
                    $patient = Patients::find($tokenData['id']);
                    $request->validate([
                        'doctor' => 'required',
                        'fav' => 'required'
                    ]);
                    if (!empty($request->doctor) && !empty($tokenData['id'])) {
                        $favourite = Favourite::select('*')->where('patinet_id', $tokenData['id'])->where('user_id', $request->doctor)->first();
                        if (!empty($favourite) && !empty($favourite)) {
                            $favourite->patinet_id = $tokenData['id'];
                            $favourite->user_id = $request->doctor;
                            $favourite->chrFav = $request->fav;
                            $favourite->save();
                        } else {
                            $favourite = new Favourite();
                            $favourite->patinet_id = $tokenData['id'];
                            $favourite->user_id = $request->doctor;
                            $favourite->chrFav = $request->fav;
                            $favourite->save();
                        }
                    }
                    if ($request->fav == 'Y') {
                        $message = 'Doctor added to favourite!';
                    } else {
                        $message = 'Doctor removed from favourite!';
                    }

                    return response()->json([
                        'message' => $message,
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
            } catch (\Exception $e) {
                if (method_exists($e, 'errors')) {
                    return response()->json([
                        'message' => 'Please Provide Valid details!',
                        'data' => [
                            'error' => 'Please Provide Valid details!'
                        ]
                    ], 400);
                }

                return response()->json([
                    'message' => 'Please Provide Valid details!',
                    'data' => [
                        'error' => 'Please Provide Valid details!'
                    ],
                ], 400);
            }
        }
    }


    // Find Doctor
    public function Search(Request $request)
    {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        $searchDoctor = [];
        
        if (!empty($headerArray[1])) {
            try {
                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {
                    $patient = Patients::find($tokenData['id']);
                    if (!empty($patient)) {
                        $limit = !empty($request->pageSize) ? $request->pageSize : 5;
                        $page = !empty($request->pageNumber) ? $request->pageNumber : 1;
                        $search = !empty($request->searchvalue) ? $request->searchvalue : '';
    
                        // Start the query to search for doctors
                        $searchDoctor = User::select(
                            'users.id',
                            'users.name',
                            'users.email',
                            'users.surname',
                            'users.country',
                            'users.state',
                            'users.gmc_registration_no',
                            'users.indemnity_insurance_provider',
                            'users.policy_no',
                            'users.india_registration_no',
                            'users.dha_reg',
                            'users.reg_no',
                            'users.chrSmartcard',
                            'users.varProfile',
                            'users.varSpeciality',
                            'users.varExperience',
                            'users.varPostGraduation',
                            'users.varPostGraduationYear',
                            'users.varGraduation',
                            'users.varGraduationYear',
                            'users.chrApproval',
                            'users.varFees as Fees',
                            'users.varTimeDuration as Consaltation Time',
                            'users.category as category',
                            'dr_category.title as categoryName'
                        )
                            ->where('chrApproval', 'Y')  // Only approved doctors
                            ->join('dr_category', 'users.category', '=', 'dr_category.id')
                            ->where('country', $patient->country)
                            ->where('users.category', $request->category)
                            ->orderBy('rattings');
    
                        // Exclude doctors who are blocked by the current patient
                        $searchDoctor->leftJoin('block', function($join) use ($patient) {
                            $join->on('block.dr_id', '=', 'users.id')
                                 ->where('block.patient_id', '=', $patient->id)
                                 ->where('block.chrIsBlock', '=', 'Y'); // Only blocked doctors
                        })
                        ->whereNull('block.id');  // Make sure the doctor is not in the block table (i.e., not blocked)
    
                        // Add search functionality
                        if (!empty($search)) {
                            $searchDoctor->where(function ($query) use ($search) {
                                $query->where('users.name', 'like', '%' . $search . '%')
                                      ->orWhere('users.surname', 'like', '%' . $search . '%')
                                      ->orWhere('users.email', 'like', '%' . $search . '%');
                            });
                        }
    
                        // Paginate the results
                        $searchDoctor = $searchDoctor->paginate($limit, ['*'], 'page', $page);
                    }
    
                    // Format topDoctor profiles
                    if (isset($searchDoctor) && count($searchDoctor) > 0) {
                        foreach ($searchDoctor as $dr) {
                            $dr->varProfile = !empty($dr->varProfile) ? config('app.url') . 'api/docterprofile/' . $dr->varProfile : 'null';
                            
                            if(!empty($dr)){
                                $ratingData = Rating::selectRaw('IFNULL(AVG(ratings.rating), 0) as ratings')
                                                    ->selectRaw('IFNULL(COUNT(ratings.id), 0) as review_count')
                                                    ->where('doctor_id', $dr->id)
                                                    ->first();
                                $dr->ratings = $ratingData->ratings;
                                $dr->review_count = $ratingData->review_count;
                            
                            }
        
                            if (is_null($ratingData)) {
                                $dr->ratings = 0;
                                $dr->review_count = 0;
                            }
                             $dr->ratings = [
                                [
                                    'ratings' => $dr->ratings,
                                    'review_count' => $dr->review_count
                                ]
                            ];
        
                            // Remove the review_count field from the root object (optional)
                            unset($dr->review_count);
                            
                          $favData = Favourite::select('*')->where('user_id',$dr->id)->where('patinet_id', $patient->id)->first();
                            if(isset($favData) && !empty($favData)){
                                $dr->isFavouriteFlag = $favData->chrFav;
                            }else{
                                $dr->isFavouriteFlag = 'N';
                            }
                        }
    
                        $topDoctorResponse = [
                            'current_page' => $searchDoctor->currentPage(),
                            'doctor' => $searchDoctor->items(),
                            'total' => $searchDoctor->total(),
                        ];
                    }
    
                    // Format patient profile
                    $patient->varProfile = !empty($patient->varProfile) ? config('app.url') . 'api/patientprofile/' . $patient->varProfile : 'null';
    
                    // Return the response
                    return response()->json([
                        'message' => 'success',
                        'data' => [
                            'patient' => [$patient],
                            'searchDoctor' => $topDoctorResponse ??  new \stdClass(), // Use topDoctorResponse if set
                        ]
                    ], 200);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Unauthorized request',
                    'data' => [
                        'error' => 'Unauthorized request'
                    ]
                ], 401);
            }
        }
    
        return response()->json([
            'message' => 'Unauthorized request',
            'data' => [
                'error' => 'Unauthorized request'
            ]
        ], 401);
    }
    

    //  public function getTimeSlots(Request $request)
    // {
    //     try {
    //         $headers = $request->header('Authorization');
    //         $headerArray = explode('Bearer ', $headers);
    //         $tokenData = decrypt($headerArray[1]);
    //         if (!empty($tokenData['id'])) {
    //             $patient = Patients::find($tokenData['id']);
    //         }
    //         $request->validate([
    //             'doctor' => 'required',
    //             'date' => 'required'
    //         ]);
    //         // Get the duration from the request, default to 30 minutes if not provided
    //         if (isset($request->timeRange) && !empty($request->timeRange)) {
    //             $duration = $request->input('duration', $request->timeRange); // in minutes
    //         } else {
    //             $time_range = GeneralSetting::select('field_value')->where('field_name', 'time_duration')->first();
    //             $duration = $request->input('duration', $time_range->field_value);
    //         }
    //         $today = Carbon::today();
    //         $startTime = $request->input('start_time', '08:00'); // default start time
    //         $endTime = $request->input('end_time', '20:00'); // default end time
    //         $date = $request->input('date', now()->format('Y-m-d'));
    //         // if (!$date) {
    //         //     $date = now()->format('d-m-Y'); // Default to today's date if not provided
    //         // }
    //         // Convert start and end time to Carbon instances
    //         $start = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "{$date} {$startTime}");
    //         $end = \Carbon\Carbon::createFromFormat('Y-m-d H:i', "{$date} {$endTime}");


    //         $dateToCheck = $date; // Replace with the actual date
    //         $storedTimeSlots = Appointment::where('varAppointment', $dateToCheck)
    //                                         ->where('dr_id', $request->doctor)
    //                                         ->where('patient_id', $patient->id)
    //                                         ->where('chrIsCanceled', 'N')
    //                                         ->where('chrIsRejected', 'N')
    //                                         ->orderBy('startTime', 'asc')
    //                                         ->orderBy('varAppointment', 'asc')
    //                                         ->get();

    //         // Initialize an empty array for time slots
    //         $timeSlots = [];

    //         // Generate time slots
    //         while ($start < $end) {
    //             $timeSlots[] = [
    //                 'start' => $start->format('H:i'), // Include the date with time
    //                 'end' => $start->copy()->addMinutes($duration)->format('H:i'), // Include the date with time
    //             ];

    //             // Move to the next time slot
    //             $start->addMinutes($duration);
    //         }


    //         // Initialize an array to store the response with booked flags
    //         $responseTimeSlots = [];
    //         $isToday = $date === $today->format('Y-m-d');
    //         // Iterate through each dynamically generated time slot
    //         foreach ($timeSlots as $timeSlot) {
    //             $isBooked = false; // Flag to track if the slot is booked
    //             $isrequested = false; // Flag to track if the slot is booked

    //             // Check this time slot against stored time slots
    //             foreach ($storedTimeSlots as $storedTimeSlot) {

    //                 $storedStart = $storedTimeSlot->startTime; // e.g., '08:00'
    //                 $storedEnd = $storedTimeSlot->endTime; // e.g., '08:30'

    //                 // Check for overlaps
    //                 if (
    //                     ($storedStart >= $timeSlot['start'] && $storedStart < $timeSlot['end']) ||
    //                     ($storedEnd > $timeSlot['start'] && $storedEnd <= $timeSlot['end']) ||
    //                     ($storedStart <= $timeSlot['start'] && $storedEnd >= $timeSlot['end'])
    //                 ) {
    //                     if($storedTimeSlot->charIsPaid == 'Y' && $storedTimeSlot->chrIsAccepted == 'Y'){
    //                         $isBooked = true; // Mark as booked if there's an overlap
    //                         break; // No need to check further for this time slot
    //                     }else{
    //                         $isrequested = true; // Mark as booked if there's an overlap
    //                         break; 
    //                     }
                      
    //                 }
    //             }

    //             // If it's today, only add time slots greater than the current time
    //             if ($isToday && $timeSlot['start'] <= now()->format('H:i')) {
    //                 continue; // Skip slots that are not in the future
    //             }


    //             // Add the time slot with the booked flag
    //             $responseTimeSlots[] = [
    //                 'start' => $timeSlot['start'],
    //                 'end' => $timeSlot['end'],
    //                 'booked' => $isBooked ? 'Y' : 'N', // Set booked flag
    //                 'requested' => $isrequested ? 'Y': 'N',
    //                 'Ispaid' => isset($storedTimeSlot) ? $storedTimeSlot->charIsPaid : "N",
    //             ];
    //         }

    //         // Prepare the final response
    //         $response = [
    //             'time_slots' => $responseTimeSlots,
    //         ];

    //         // Handle the API response
    //         if ($response) {
    //             return response()->json(['message' => 'Time slots created successfully', 'data' => $response], 200);
    //         }
    //     } catch (\Exception $e) {
    //         // dd($e);
    //         return response()->json(['message' => 'Failed to create time slots',  'data' => ['error' => 'Please Provide Valid Data']], 400);
    //     }
    // }

    public function getTimeSlots(Request $request)
{
    try {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        $tokenData = decrypt($headerArray[1]);
        if (!empty($tokenData['id'])) {
            $loggedInPatient = Patients::find($tokenData['id']);
        }

        $request->validate([
            'doctor' => 'required',
            'date' => 'required'
        ]);

        if (isset($request->timeRange) && !empty($request->timeRange)) {
            $duration = $request->input('duration', $request->timeRange);
        } else {
            $time_range = GeneralSetting::select('field_value')->where('field_name', 'time_duration')->first();
            $duration = $request->input('duration', $time_range->field_value);
        }

        $today = Carbon::today();
        $startTime = $request->input('start_time', '08:00');
        $endTime = $request->input('end_time', '20:00');
        $date = $request->input('date', now()->format('Y-m-d'));

        $start = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$startTime}");
        $end = Carbon::createFromFormat('Y-m-d H:i', "{$date} {$endTime}");

        // Fetch booked slots for the given doctor
        $storedTimeSlots = Appointment::where('varAppointment', $date)
            ->where('dr_id', $request->doctor)
            ->where('chrIsCanceled', 'N')
            ->where('chrIsRejected', 'N')
            ->orderBy('startTime', 'asc')
            ->get();

        $timeSlots = [];
        while ($start < $end) {
            $timeSlots[] = [
                'start' => $start->format('H:i'),
                'end' => $start->copy()->addMinutes($duration)->format('H:i'),
            ];
            $start->addMinutes($duration);
        }

        $responseTimeSlots = [];
        $isToday = $date === $today->format('Y-m-d');

        foreach ($timeSlots as $timeSlot) {
            $isBooked = false;
            $isRequested = false;
            $isPaid = "N";

            foreach ($storedTimeSlots as $storedTimeSlot) {
                $storedStart = $storedTimeSlot->startTime;
                $storedEnd = $storedTimeSlot->endTime;

                if (
                    ($storedStart >= $timeSlot['start'] && $storedStart < $timeSlot['end']) ||
                    ($storedEnd > $timeSlot['start'] && $storedEnd <= $timeSlot['end']) ||
                    ($storedStart <= $timeSlot['start'] && $storedEnd >= $timeSlot['end'])
                ) {
                    if ($storedTimeSlot->charIsPaid == 'Y' && $storedTimeSlot->chrIsAccepted == 'Y') {
                        $isBooked = true;
                        $isPaid = "Y";
                        break;
                    } elseif ($storedTimeSlot->patient_id == $loggedInPatient->id) {
                        $isRequested = true;
                        $isPaid = $storedTimeSlot->charIsPaid;
                    }
                }
            }

            if ($isToday && $timeSlot['start'] <= now()->format('H:i')) {
                continue;
            }

            $responseTimeSlots[] = [
                'start' => $timeSlot['start'],
                'end' => $timeSlot['end'],
                'booked' => $isBooked ? 'Y' : 'N',
                'requested' => $isRequested ? 'Y' : 'N',
                'Ispaid' => $isPaid,
            ];
        }

        return response()->json(['message' => 'Time slots created successfully', 'data' => ['time_slots' => $responseTimeSlots]], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to create time slots', 'data' => ['error' => 'Please Provide Valid Data']], 400);
    }
}




    public function sendRequest(Request $request)
    {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        if (!empty($headerArray[1])) {
            try {

                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {
                    $patient = Patients::find($tokenData['id']);
                    if (!empty($patient)) {

                        $request->validate([
                            'doctor' => 'required', // Assuming you have a doctors table
                            'date' => 'required|date_format:Y-m-d',
                            'time_start' => 'required|date_format:H:i',
                            'time_end' => 'required|date_format:H:i',
                            'sympton' => 'required|string',
                            'symton_desc' => 'required|string'
                        ]);


                        // Check if the time slot is already booked
                        $existingAppointments = Appointment::where('dr_id', $request->doctor)
                            ->where('varAppointment', $request->date)
                            ->where('chrIsCanceled', 'N')
                            ->where('chrIsRejected', 'N')
                            ->where('charIsPaid', 'Y')
                            // Ensure date is in YYYY-MM-DD format
                            // ->where(function ($query) use ($request) {
                            //     $query->whereBetween('startTime', [$request->time_start, $request->time_end])
                            //           ->orWhereBetween('endTime', [$request->time_start, $request->time_end])
                            //           ->orWhere(function ($query) use ($request) {
                            //               $query->where('startTime', '<=', $request->time_start)
                            //                     ->where('endTime', '>=', $request->time_end);
                            //           });
                            // })
                            ->where(function ($query) use ($request) {
                                $query->where('startTime', '<=', $request->time_start)
                                    ->where('endTime', '>=', $request->time_end);
                            })
                            ->exists();

                        if ($existingAppointments) {
                            return response()->json([
                                'message' => 'Time slot already booked!',
                                'data' =>  new \stdClass(),
                            ], 200);
                        }





                        // Create the appointment
                        $appointment = new Appointment();
                        $appointment->patient_id = $patient->id; // Make sure you pass patient_id in request
                        $appointment->dr_id = $request->doctor;
                        $appointment->varAppointment = $request->date; // Modify based on your needs
                        $appointment->startTime = $request->time_start;
                        $appointment->endTime = $request->time_end;
                        $appointment->varSympton = $request->sympton;
                        $appointment->varSymptondesc = $request->symton_desc;
                        // $appointment->charIsPaid = $request->paid;
                        $appointment->save();


                        
                        $doctor = User::find($request->doctor); // Assuming you have a Doctors model
                        if ($doctor && $doctor->fcm_token) {
                            $notificationController = new NotificationController();
                           $notificationController->sendPushNotification(
                                $doctor->fcm_token,
                                'New Appointment Request',
                                'You have a new appointment request from ' . $patient->name,
                        'patient'
                            );
                        }
                        
                        return response()->json([
                            'message' => 'Appointment request sent!',
                            'data' => [
                                'appointment' => $appointment
                            ]
                        ], 200);
                    }
                }
            } catch (\Exception $e) {
                dd($e);
                if (method_exists($e, 'errors')) {
                    return response()->json([
                        'message' => 'Please Provide Valid details!',
                        'data' => [
                            'error' => 'Please Provide Valid details!'
                        ]
                    ], 400);
                }

                return response()->json([
                    'message' => 'Please Provide Valid details!',
                    'data' => [
                        'error' => 'Please Provide Valid details!'
                    ],
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


      public function getDoctorData(Request $request)
    {
        $headers = $request->header('Authorization');
        $headerArray = explode('Bearer ', $headers);
        $reviewPageSize = max(1, min((int) $request->input('reviewPageSize', $request->input('pageSize', 10)), 100));
        $reviewPage = max(1, (int) $request->input('reviewPageNumber', 1));
        $shouldPaginateReviews = $request->filled('reviewPageNumber') || $request->filled('reviewPageSize') || $request->filled('pageSize');
        if (!empty($headerArray[1])) {
            try {
                $request->validate([
                    'doctor' => 'required',
                ]);
                $tokenData = decrypt($headerArray[1]);
                if (!empty($tokenData['id'])) {
                    $doctor = User::select(
                        'users.id',
                        'users.name as first_name',
                        'users.surname',
                        'dr_category.title as category',
                        'users.country',
                        'users.state',
                        'users.email',
                        'users.gmc_registration_no',
                        'users.indemnity_insurance_provider',
                        'users.policy_no',
                        'users.india_registration_no',
                        'users.dha_reg',
                        'users.reg_no',
                        'users.chrSmartcard',
                        'users.varProfile as profile_picture',
                        'users.varSpeciality as speciality',
                        'users.varExperience as total_experience',
                        'users.varPostGraduation as post_graduation',
                        'users.varPostGraduationYear as pg_year',
                        'users.varGraduation as graduation',
                        'users.varGraduationYear as graduation_year',
                        'users.varFees as fees',
                        'users.varTimeDuration as consultation_time'
                    )
                        ->join('dr_category', 'users.category', 'dr_category.id')
                        ->leftJoin('appointment','users.id','appointment.dr_id')
                        ->leftJoin('ratings', 'ratings.doctor_id', '=', 'users.id')
                        ->selectRaw('IFNULL(AVG(ratings.rating), 0) as ratings')
                        ->selectRaw('IFNULL(COUNT(ratings.rating), 0) as review_count')
                        ->selectRaw('IFNULL(COUNT(DISTINCT appointment.patient_id), 0) as patient_count')
                        ->where('users.id', $request->doctor)
                        ->groupBy(
                            'users.id',
                            'users.name',
                            'users.surname',
                            'users.country',
                            'users.state',
                            'users.email',
                            'users.gmc_registration_no',
                            'users.indemnity_insurance_provider',
                            'users.policy_no',
                            'users.india_registration_no',
                            'users.dha_reg',
                            'users.reg_no',
                            'users.chrSmartcard',
                            'users.varProfile',
                            'users.varSpeciality',
                            'users.varExperience',
                            'users.varPostGraduation',
                            'users.varPostGraduationYear',
                            'users.varGraduation',
                            'users.varGraduationYear',
                            'users.varFees',
                            'users.varTimeDuration',
                            'dr_category.title'
                        )
                        ->first();
                   
                   
                    if (!$doctor) {
                        return response()->json(['message' => 'Doctor not found'], 404);
                    }
                    if(!empty($doctor)){
                        $ratingData = Rating::selectRaw('IFNULL(AVG(ratings.rating), 0) as ratings')
                                            ->selectRaw('IFNULL(COUNT(ratings.id), 0) as review_count')
                                            ->where('doctor_id', $doctor->id)
                                            ->first();
                        $doctor->ratings = $ratingData->ratings;
                        $doctor->review_count = $ratingData->review_count;
                    
                    }

                    if (is_null($ratingData)) {
                        $doctor->ratings = 0;
                        $doctor->review_count = 0;
                    }
                    // Structure the ratings as an object with keys 'ratings' and 'review_count' inside an array
                    $doctor->ratings = [
                        [
                            'ratings' => $doctor->ratings,
                            'review_count' => $doctor->review_count
                        ]
                    ];

                    // Remove the review_count field from the root object (optional)
                    unset($doctor->review_count);



                    $current_work_org = DB::table('org_experiance')->select('title as org_name', 'startYear as start_year', 'endYear as end_year', 'varDescription as description', 'isCurrentworkOrg')->where('user_id', $request->doctor)->get();
                    $current_work_org = json_encode($current_work_org);
                    $doctor->current_work_org = $current_work_org;
                    if (!empty($doctor)) {
                        $doctor->profile_picture =  !empty($doctor->profile_picture) ? config('app.url') . 'api/docterprofile/' . $doctor->profile_picture : 'null';
                        $doctor->biography =  !empty($doctor->biography) ? $doctor->biography : 'null';
                        $doctor->language =  !empty($doctor->language) ? $doctor->language : 'null';

                        $reviewQuery = Rating::select('rating', 'varShortTitle as title', 'varReview as review','ratings.created_at as date' , 'patients.name', 'patients.lastname', 'patients.varProfile')
                            ->join('patients', 'ratings.patinet_id', 'patients.id')
                            ->where('doctor_id', $doctor->id)
                            ->orderBy('ratings.created_at', 'desc');
                        $reviews = $shouldPaginateReviews
                            ? $reviewQuery->paginate($reviewPageSize, ['*'], 'reviewPage', $reviewPage)
                            : $reviewQuery->get();
                        $reviewItems = $shouldPaginateReviews ? $reviews->items() : $reviews;
                        if (isset($reviewItems) && !empty($reviewItems)) {
                            foreach ($reviewItems as $review) {
                                $review->profile_picture =  !empty($review->varProfile) ? config('app.url') . 'api/patientprofile/' . $review->varProfile : 'null';
                            }
                        }
                        return response()->json([
                            'message' => 'success',
                            'data' => [
                                'doctor' => [$doctor],
                                'reviews' => isset($reviewItems) && !empty($reviewItems) ? $reviewItems : new \stdClass(),
                                'reviewsPagination' => $shouldPaginateReviews ? [
                                    'current_page' => $reviews->currentPage(),
                                    'per_page' => $reviews->perPage(),
                                    'total' => $reviews->total(),
                                    'last_page' => $reviews->lastPage(),
                                ] : new \stdClass()
                            ]
                        ], 200);
                    }
                }
            } catch (\Exception $e) {
                dd($e);
                return response()->json([
                    'message' => 'Unauthorized request',
                    'data' => [
                        'error' => 'Unauthorized request'
                    ]
                ], 401);
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
