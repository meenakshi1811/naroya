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

        $formOptions = $this->registrationService->formOptions();

        return view('referral.register', array_merge(
            compact('affiliate'),
            $formOptions,
            ['googlePlayUrl' => config('app.patient_google_play_url')]
        ));
    }

    public function register(Request $request, string $code)
    {
        $affiliate = Affiliate::where('code', strtoupper($code))
            ->where('is_active', true)
            ->firstOrFail();

        if (! $request->filled('country')) {
            $request->merge(['country' => $this->registrationService->indiaCountryId()]);
        }

        $this->registrationService->createFromRequest($request, $affiliate->id, true);

        return redirect()
            ->route('referral.show', $affiliate->code)
            ->with('success', 'Registration successful! Download the Noraya patient app and sign in with your email and password.');
    }
}
