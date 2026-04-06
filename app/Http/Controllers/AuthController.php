<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Patients;
use App\Models\Block;
use App\Models\OrgExperience;
use App\Models\GeneralSetting;
use Carbon\Carbon;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use App\Http\Controllers\NotificationController;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;
use App\Mail\UserOnboardingMail;

class AuthController extends Controller
{
    /* ================= HELPER METHODS ================= */

    private function formatProfile($path, $type = 'doctor')
    {
        if (!$path) return null;
        $folder = $type === 'doctor' ? 'docterprofile' : 'patientprofile';
        return config('app.url') . "api/{$folder}/" . $path;
    }

    private function attachExperiences($user)
    {
        $user->current_work_org = json_encode(
            $user->experiences->map(function ($exp) {
                return [
                    'org_name' => $exp->title,
                    'start_year' => $exp->startYear,
                    'end_year' => $exp->endYear,
                    'description' => $exp->varDescription,
                    'isCurrentworkOrg' => $exp->isCurrentworkOrg
                ];
            })
        );

        if ($user->varProfile) {
            $user->varProfile = $this->formatProfile($user->varProfile);
        }

        return $user;
    }

    private function getUser($userId)
    {
        $user = User::with('experiences')->find($userId);
        return $this->attachExperiences($user);
    }

    private function mapAppointment($item)
    {
        $item->varProfile = $this->formatProfile(optional($item->patient)->varProfile, 'patient');
        return $item;
    }

    /* ================= LOGIN ================= */

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User Dose not exist!','data'=>['error'=>'User Dose not exist!']],400);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message'=>'Invalid credentials!','data'=>['error'=>'Invalid credentials!']],400);
        }

        if (!$user->chrApproval) {
            return response()->json([
                'message'=>'Your account is currently pending approval. Please contact your administration for further assistance.'
            ],200);
        }

        $token = $user->createToken('authToken');

        return response()->json([
            'message'=>'Login successful!',
            'data'=>[
                'user'=>$user,
                'token_type'=>'Bearer',
                'expires_in'=>null,
                'access_token'=>$token->accessToken,
                'refresh_token'=>null
            ]
        ],200);
    }

    /* ================= LOGOUT ================= */

    public function logout(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message'=>'User not authenticated!',
                'data'=>['error'=>'User not authenticated']
            ],401);
        }

        $request->user()->token()->revoke();

        Token::where('user_id',$user->id)->update(['revoked'=>true]);

        RefreshToken::whereIn('access_token_id',function($q) use ($user){
            $q->select('id')->from('oauth_access_tokens')->where('user_id',$user->id);
        })->delete();

        return response()->json([
            'message'=>'Logout successful!',
            'data'=>['success'=>true]
        ],200);
    }

    /* ================= USER DATA ================= */

    public function getUserData(Request $request)
    {
        $user = $this->getUser($request->user()->id);

        if ($user->chrApproval != 'Y') {
            return response()->json([
                'message'=>'Your account is currently pending approval. Please contact your administration for further assistance.',
                'status'=>200
            ],200);
        }

        return response()->json([
            'message'=>'successful!',
            'data'=>['user'=>$user]
        ],200);
    }

    /* ================= HOME ================= */

    public function getHome(Request $request)
    {
        $userData = $request->user();

        if ($userData->chrApproval != 'Y') {
            return response()->json([
                'message'=>'Your account is currently pending approval. Please contact your administration for further assistance.',
                'status'=>200
            ],200);
        }

        $user = $this->getUser($userData->id);

        $today = Carbon::today();
        $now = now()->format('H:i');

        $appointments = Appointment::with('patient')
            ->where('dr_id',$user->id)
            ->where('chrIsCanceled','N')
            ->orderBy('varAppointment')
            ->orderBy('startTime')
            ->get();

        $requestList = $appointments->filter(fn($a)=>
            $a->chrIsAccepted=='N' && $a->chrIsRejected=='N'
        )->take(5)->map(fn($a)=>$this->mapAppointment($a))->values();

        $todayList = $appointments->filter(fn($a)=>
            $a->chrIsAccepted=='Y' &&
            $a->charIsPaid=='Y' &&
            $a->varAppointment==$today->format('Y-m-d') &&
            $a->endTime >= $now
        )->take(5)->map(fn($a)=>$this->mapAppointment($a))->values();

        $nextList = $appointments->filter(fn($a)=>
            $a->chrIsAccepted=='Y' &&
            $a->charIsPaid=='Y' &&
            $a->varAppointment > $today->format('Y-m-d')
        )->take(5)->map(fn($a)=>$this->mapAppointment($a))->values();

        return response()->json([
            'message'=>'successful!',
            'data'=>[
                'doctor'=>$user,
                'requestList'=>$requestList,
                'today-appointment'=>$todayList,
                'next-appointment'=>$nextList
            ]
        ],200);
    }

    /* ================= REGISTER ================= */

    public function register(Request $request)
    {
        $request->validate([
            'first_name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required'
        ]);

        $time = GeneralSetting::where('field_name','time_duration')->first();

        $user = User::create([
            'name'=>$request->first_name,
            'email'=>$request->email,
            'password'=>bcrypt($request->password),
            'varTimeDuration'=>$time->field_value
        ]);

        Stripe::setApiKey(env('STRIPE_SECRET'));

        $account = Account::create([
            'type'=>'standard',
            'country'=>'GB',
            'email'=>$request->email
        ]);

        $user->stripe_account_id = $account->id;
        $user->save();

        $accountLink = AccountLink::create([
            'account'=>$account->id,
            'refresh_url'=>route('stripe.refresh',['userId'=>$user->id]),
            'return_url'=>env('STRIPE_RETURN_URL'),
            'type'=>'account_onboarding',
        ]);

        Mail::to($user->email)->send(new UserOnboardingMail($user,$accountLink->url));

        return response()->json([
            'message'=>'Your request has been sent successfully for approval!',
            'data'=>[
                'user'=>$user,
                'onboarding_url'=>$accountLink->url
            ]
        ],200);
    }

    /* ================= REQUEST ACCEPT/REJECT ================= */

    public function patientRequest(Request $request)
    {
        $user = $request->user();
        $appointment = Appointment::find($request->requestId);

        if (!$appointment) {
            return response()->json(['message'=>'Invalid details!'],400);
        }

        $patient = Patients::find($request->patient);

        if ($request->isAccept == 'Y') {
            $appointment->chrIsAccepted = 'Y';

            (new NotificationController())->sendPushNotification(
                $patient->fcm_token,
                'Appointment Accepted',
                'Your appointment request has been accepted by Dr. '.$user->name,
                'doctor'
            );

            $message = "Appointment Accepted Successfully";
        } else {
            $appointment->chrIsRejected = 'Y';
            $appointment->varReason = $request->reason;

            $message = "Appointment Declined Successfully";
        }

        $appointment->save();

        return response()->json([
            'message'=>$message,
            'data'=>[
                'user'=>$user,
                'request'=>$appointment
            ]
        ],200);
    }

    /* ================= BLOCK / UNBLOCK ================= */

    public function BlockUnblock(Request $request)
    {
        $user = $request->user();

        $block = Block::updateOrCreate(
            ['patient_id'=>$request->patient,'dr_id'=>$user->id],
            ['chrIsBlock'=>$request->block,'varReason'=>$request->reason]
        );

        return response()->json([
            'message'=>$request->block=='Y' ? 'Patient blocked Successfully!' : 'Patient unblock Successfully!',
            'data'=>[
                'user'=>$user,
                'blockData'=>$block
            ]
        ],200);
    }

    /* ================= COUNTRIES ================= */

    public function countries()
    {
        $countries = \App\Models\Country::where('chrPublish','Y')->get();

        foreach ($countries as $c) {
            $c->countryImage = config('app.url').'flags/'.strtolower($c->code).'.png';
        }

        return response()->json([
            'message'=>'success',
            'data'=>['countries'=>$countries]
        ],200);
    }

    /* ================= SPECIALITY ================= */

    public function speciality()
    {
        return response()->json([
            'message'=>'success',
            'data'=>['speciality'=>\App\Models\Category::all()]
        ],200);
    }

    /* ================= STATES ================= */

    public function stateList($countryId)
    {
        return response()->json([
            'message'=>'success',
            'data'=>[
                'states'=>\App\Models\State::where('country_id',$countryId)->get()
            ]
        ],200);
    }
}