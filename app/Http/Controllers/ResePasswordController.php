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

    public function update(Request $request)
    {
        $emailRule = $request->isDoctor == 'Y'
            ? 'required|email|exists:users,email'
            : 'required|email|exists:patients,email';

        $validator = Validator::make($request->all(), [
            'email' => $emailRule,
            'password' => [
                'required',
                'min:8',
                'regex:/[a-z]/',      // lowercase
                'regex:/[A-Z]/',      // uppercase
                'regex:/[0-9]/',      // number
                'regex:/[@$!%*#?&]/', // special character
            ],
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->isDoctor == 'Y') {
            $user = User::where('email', $request->email)->first();
        } else {
            $user = Patients::where('email', $request->email)->first();
        }

        if ($user) {
            $user->password = bcrypt($request->password);
            $user->save();
        }

        return redirect()->back()->with('status', 'Password updated successfully. Please go back to login page!');
    }
}
