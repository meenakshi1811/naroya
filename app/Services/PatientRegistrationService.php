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
            'email' => 'required|string|email|unique:patients,email|valid_email_domain',
            'password' => $passwordRule,
            'phone' => 'required|string|max:20',
            'state' => 'required|integer|exists:states,id',
            'language_id' => 'required|integer|exists:language_master,id',
            'country' => 'nullable|integer|exists:country_master,id',
            'fcm_token' => 'nullable|string',
            'affiliate_code' => 'nullable|string|exists:affiliates,code',
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
        $validated = $request->validate($this->validationRules($requirePasswordConfirmation));

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
        $patient->state = $data['state'];
        $patient->language_id = $data['language_id'];
        $patient->localization_id = $data['language_id'];
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
}
