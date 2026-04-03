<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; // Import Validator
use Illuminate\Support\Facades\Hash; // Import Hash for hashing passwords
use App\Models\User;
use App\Models\Patients;


class ResePasswordController extends Controller
{
    public function create(Request $request,$token){
        $tokenData = decrypt($token);
        return view('reset-password',compact('tokenData'));
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Find the user
        if($request->isDoctor == 'Y' ){
            $user = User::where('email', $request->email)->first();
        }else{
            $user = Patients::where('email', $request->email)->first();
        }

        // Update the password
        if ($user) {
            $user->password = bcrypt($request->password);
            $user->save();
        }

        return redirect()->back()->with('status', 'Password updated successfully Plese Go back to login page!');
    }
}
