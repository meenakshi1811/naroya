<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use App\Models\Patients;
use App\Mail\ForgotPassword;
use DB;
use Mail;

class ForgetPasswordController extends Controller
{

    //use SendsPasswordResetEmails;

    public function EmailSend(Request $request)
    {
        try{
            $request->validate([       
                'email' => 'required|string|email',           
            ]);
            if(isset($request->email) && !empty($request->email))
            {
                $email = $request->email;
                if(isset($request->chrIsDr) && !empty($request->chrIsDr)){
                    if($request->chrIsDr == 'Y'){
                        $checkEmail = User::where('email',$email)->first();
                    }else{
                        $checkEmail = Patients::where('email',$email)->first();
                    }
                }            
                if(!empty($checkEmail))
                {
                
                    $digits = 4;
                    $otp = rand(pow(10, $digits-1), pow(10, $digits)-1);
                    $userId = $checkEmail->id;
                    // $is_Doctor = ($request->chrIsDr == 'Y') ? 1 : 2;
                    // DB::table('otp_master')
                    //     ->insert([
                    //         'otp'=>$otp,
                    //         'isDoctor' => $is_Doctor,
                    //         'user_id' => $userId,
                    //         'created_at'=>date('Y-m-d H:i:s'),
                    //         'updated_at' => date('Y-m-d H:i:s')
                    //     ]);
                        $patientsData = ["email"=>$checkEmail->email,'chrIsDr' => $request->chrIsDr];
                        $token = encrypt($patientsData);
                    $this->SendEmail($email,$request->chrIsDr,$token);
                    return response()->json([
                        'message' => 'We have sent Forget password link to your registered email address',
                        'data'=> [
                            'email' => $email,
                            'chrIsDr'=>$request->chrIsDr,                  
                            'token'=>$token,                  
                        ]
                    ], 200);
                }
                return response()->json([
                    'message' => 'Please Provide Valid Email address!',
                    'data' => [
                         "error"=> 'Please Provide Valid Email address!'
                        ]
                ], 400);
            } else
            {               
                return response()->json([
                    'message' => 'Please Provide Valid Email address!',
                    'data' => [
                        "error"=> 'Please Provide Valid Email address!'
                        ]
                ], 400);
            }
        } catch (\Exception $e) {
        if (method_exists($e, 'errors')) {
            return response()->json([
                'message' => 'Please Provide Valid Details!',
                'data' => [
                    "error"=> 'Please Provide Valid Details!'
                ]
            ], 400);
        }
        return response()->json([
            'message' => 'Please Provide Valid Details!',
            'data' => [
            "error"=> 'Please Provide Valid Details!'
          ]
        ], 400);
    }
}


    public function SendEmail($email,$flag,$token){
        // Mail::to($email)->later(now()->addMinutes(1), new ForgotPassword($email,$flag,$token));
        Mail::to($email)->queue(new ForgotPassword($email,$flag,$token));
        return response(['sucsess' => "Email Sent"]);
    }
}
