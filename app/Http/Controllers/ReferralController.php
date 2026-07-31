<?php

namespace App\Http\Controllers;

use App\Models\Affiliate;
use App\Services\PatientRegistrationService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(
        private PatientRegistrationService $registrationService
    ) {}

    public function show(string $code)
    {
        $affiliate = Affiliate::where('code', strtoupper($code))
            ->where('is_active', true)
            ->firstOrFail();

        return view('referral.register', [
            'affiliate' => $affiliate,
            'indiaCountryId' => $this->registrationService->indiaCountryId(),
            'googlePlayUrl' => config('app.patient_google_play_url'),
        ]);
    }

    public function register(Request $request, string $code)
    {
        $affiliate = Affiliate::where('code', strtoupper($code))
            ->where('is_active', true)
            ->firstOrFail();

        if (! $request->filled('country')) {
            $request->merge(['country' => $this->registrationService->indiaCountryId()]);
        }

        $this->registrationService->createFromReferralRequest($request, $affiliate->id);

        return redirect()
            ->route('referral.show', $affiliate->code)
            ->with('success', 'Registration successful! Download the Noraya patient app and sign in with your phone number and password.');
    }
}
