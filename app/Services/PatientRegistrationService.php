<?php

namespace App\Services;

use App\Http\Controllers\NotificationController;
use App\Models\Affiliate;
use App\Models\Country;
use App\Models\Language;
use App\Models\Patients;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PatientRegistrationService
{
    public function indiaCountryId(): ?int
    {
        return Country::query()
            ->where(function ($query) {
                $query->where('countryname', 'like', '%India%')
                    ->orWhere('code', 'IN')
                    ->orWhere('countrycode', 'IN');
            })
            ->value('id');
    }

    public function validationRules(bool $requirePasswordConfirmation = false): array
    {
        $passwordRule = 'required|string|min:6';
        if ($requirePasswordConfirmation) {
            $passwordRule .= '|confirmed';
        }

        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|unique:patients,email|valid_email_domain',
            'password' => $passwordRule,
            'phone' => 'required|string|max:20|unique:patients,phone',
            'state' => 'required|integer|exists:states,id',
            'language_id' => 'required|integer|exists:language_master,id',
            'country' => 'nullable|integer|exists:country_master,id',
            'fcm_token' => 'nullable|string',
            'affiliate_code' => 'nullable|string|exists:affiliates,code',
        ];
    }

    public function referralValidationRules(bool $requirePasswordConfirmation = false): array
    {
        $passwordRule = 'required|string|min:6';
        if ($requirePasswordConfirmation) {
            $passwordRule .= '|confirmed';
        }

        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'password' => $passwordRule,
            'phone' => ['required', 'digits:10', 'unique:patients,phone'],
            'country' => 'nullable|integer|exists:country_master,id',
        ];
    }

    public function formOptions(): array
    {
        return [
            'states' => State::orderBy('name')->get(['id', 'name']),
            'languages' => Language::select('id', 'language_name')
                ->where('chrPublish', 'Y')
                ->orderBy('language_name')
                ->get(),
            'indiaCountryId' => $this->indiaCountryId(),
        ];
    }

    public function createFromRequest(Request $request, ?int $affiliateId = null, bool $requirePasswordConfirmation = false): Patients
    {
        if (! $request->filled('email')) {
            $request->merge(['email' => null]);
        }

        $validated = $request->validate($this->validationRules($requirePasswordConfirmation));

        if (empty($validated['email'])) {
            $validated['email'] = $this->referralEmailForPhone($validated['phone']);
        }

        return $this->createPatient($validated, $affiliateId, $request);
    }

    public function createFromReferralRequest(Request $request, ?int $affiliateId = null): Patients
    {
        $request->merge([
            'phone' => $this->normalizePhone((string) $request->input('phone', '')),
        ]);

        $validated = $request->validate($this->referralValidationRules(true));
        $validated['email'] = $this->referralEmailForPhone($validated['phone']);
        $validated['state'] = null;
        $validated['language_id'] = null;

        return $this->createPatient($validated, $affiliateId, $request);
    }

    public function createPatient(array $data, ?int $affiliateId = null, ?Request $request = null): Patients
    {
        $patient = new Patients();
        $patient->name = $data['first_name'];
        $patient->lastname = $data['last_name'] ?? '';
        $patient->email = $data['email'];
        $patient->phone = $data['phone'];
        $patient->country = $data['country'] ?? $this->indiaCountryId();
        $patient->state = $data['state'] ?? null;
        $patient->language_id = $data['language_id'] ?? null;
        $patient->localization_id = $data['language_id'] ?? null;
        $patient->password = Hash::make($data['password']);
        $patient->fcm_token = $data['fcm_token'] ?? null;

        if ($affiliateId) {
            $patient->affiliate_id = $affiliateId;
        } elseif (! empty($data['affiliate_code'])) {
            $affiliate = Affiliate::where('code', strtoupper($data['affiliate_code']))
                ->where('is_active', true)
                ->first();
            if ($affiliate) {
                $patient->affiliate_id = $affiliate->id;
            }
        }

        if ($request && $request->hasFile('profile_picture') && ! empty($request->file('profile_picture'))) {
            $destinationPath = 'api/patientprofile';
            $myimage = time() . '_' . $request->profile_picture->getClientOriginalName();
            $request->profile_picture->move(public_path($destinationPath), $myimage);
            $patient->varProfile = $myimage;
        }

        $patient->save();

        $this->sendWelcomeNotification($patient);

        return $patient;
    }

    public function sendWelcomeNotification(Patients $patient): void
    {
        if (empty($patient->fcm_token)) {
            return;
        }

        $notificationController = new NotificationController();
        $notificationController->sendPushNotification(
            $patient->fcm_token,
            'Welcome to Our Application!',
            'Thank you for registering with us. We are glad to have you onboard!',
            'patient',
            [],
            'patient_register'
        );
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    private function referralEmailForPhone(string $phone): string
    {
        $email = $phone . '@refer.noraya.in';
        $suffix = 1;

        while (Patients::where('email', $email)->exists()) {
            $email = $phone . '+' . $suffix . '@refer.noraya.in';
            $suffix++;
        }

        return $email;
    }
}
