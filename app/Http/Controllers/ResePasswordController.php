<?php

namespace App\Http\Controllers;

use App\Models\Patients;
use App\Models\User;
use App\Services\PatientRegistrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ResePasswordController extends Controller
{
    public function __construct(
        private PatientRegistrationService $registrationService
    ) {}

    public function create(Request $request, $token)
    {
        $tokenData = decrypt($token);

        return view('reset-password', compact('tokenData'));
    }

    public function checkPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            ['password' => $this->registrationService->passwordValidationRules(false)],
            $this->registrationService->passwordValidationMessages()
        );

        if ($validator->fails()) {
            return response()->json([
                'valid' => false,
                'message' => $validator->errors()->first('password'),
            ], 422);
        }

        return response()->json(['valid' => true]);
    }

    public function update(Request $request)
    {
        $emailRule = $request->isDoctor == 'Y'
            ? 'required|email|exists:users,email'
            : 'required|email|exists:patients,email';

        $validator = Validator::make(
            $request->all(),
            [
                'email' => $emailRule,
                'password' => $this->registrationService->passwordValidationRules(true),
            ],
            $this->registrationService->passwordValidationMessages()
        );

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

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

        $accountType = $request->isDoctor == 'Y' ? 'doctor' : 'patient';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('password.reset.success', ['type' => $accountType]),
            ]);
        }

        return redirect()->route('password.reset.success', ['type' => $accountType]);
    }

    public function success(Request $request)
    {
        $type = $request->query('type', 'patient') === 'doctor' ? 'doctor' : 'patient';

        return view('password-reset-success', [
            'accountType' => $type,
            'googlePlayUrl' => config('app.patient_google_play_url'),
        ]);
    }
}
