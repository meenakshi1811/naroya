<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Models\Patients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ReferralController extends Controller
{
    public function show(string $code)
    {
        $affiliate = Affiliate::where('code', strtoupper($code))
            ->where('is_active', true)
            ->firstOrFail();

        return view('referral.register', compact('affiliate'));
    }

    public function register(Request $request, string $code)
    {
        $affiliate = Affiliate::where('code', strtoupper($code))
            ->where('is_active', true)
            ->firstOrFail();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|unique:patients,email|valid_email_domain',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
        ]);

        $patient = new Patients();
        $patient->name = $validated['first_name'];
        $patient->lastname = $validated['last_name'] ?? '';
        $patient->email = $validated['email'];
        $patient->phone = $validated['phone'];
        $patient->country = $validated['country'] ?? null;
        $patient->state = $validated['state'] ?? null;
        $patient->password = Hash::make($validated['password']);
        $patient->affiliate_id = $affiliate->id;
        $patient->save();

        return redirect()
            ->route('referral.show', $affiliate->code)
            ->with('success', 'Registration successful! You can now sign in to the Noraya patient app with your email and password.');
    }
}
