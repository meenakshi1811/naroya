<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ProvidesLocationOptions;
use App\Http\Controllers\Controller;
use App\Mail\ClinicMemberCredentialsMail;
use App\Models\Clinic;
use App\Models\DoctorActivity;
use App\Models\DrCategory;
use App\Models\GeneralSetting;
use App\Models\Language;
use App\Models\OrgExperience;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClinicMemberController extends Controller
{
    use ProvidesLocationOptions;

    // Same folder the doctor API uses, so existing profile URLs keep working.
    private const PROFILE_PATH = 'api/docterprofile';

    public function create(Request $request, Clinic $clinic)
    {
        $member = new User();
        $member->role = $request->input('role') === User::ROLE_EMPLOYEE ? User::ROLE_EMPLOYEE : User::ROLE_DOCTOR;
        $member->varTimeDuration = $this->defaultConsultationTime();

        return view('admin.clinics.member-form', array_merge(
            compact('clinic', 'member'),
            ['experiences' => []],
            $this->memberFormOptions(old('state'))
        ));
    }

    public function store(Request $request, Clinic $clinic)
    {
        $validated = $this->validateMember($request);

        $member = new User();
        $member->clinic_id = $clinic->id;
        $member->chrApproval = 'Y';
        $member->varTimeDuration = $this->defaultConsultationTime();
        $member->password = Hash::make($validated['password']);
        $this->fillMember($member, $request, $validated);
        $member->save();

        if ($member->role === User::ROLE_DOCTOR) {
            $this->replaceExperiences($member->id, $validated['experiences'] ?? []);
        }

        $message = $this->displayName($member) . ' was added to ' . $clinic->name . ' as ' . strtolower(User::ROLES[$member->role]) . '.';

        if ($request->boolean('send_credentials')) {
            $message .= $this->sendCredentials($member, $clinic, $validated['password'])
                ? ' Login details were emailed to ' . $member->email . '.'
                : ' The login email could not be sent, so please share the password manually.';
        }

        return redirect()
            ->route('admin.clinic.show', $clinic)
            ->with('success', $message);
    }

    public function edit(Clinic $clinic, User $member)
    {
        $this->ensureBelongsToClinic($clinic, $member);

        $experiences = OrgExperience::where('user_id', $member->id)
            ->get()
            ->map(fn ($experience) => [
                'org_name' => $experience->title,
                'start_year' => $experience->startYear,
                'end_year' => $experience->endYear,
                'description' => $experience->varDescription,
                'is_current' => $experience->isCurrentworkOrg === 'Y' ? 'Y' : 'N',
            ])
            ->all();

        return view('admin.clinics.member-form', array_merge(
            compact('clinic', 'member', 'experiences'),
            $this->memberFormOptions(old('state', $member->state))
        ));
    }

    public function update(Request $request, Clinic $clinic, User $member)
    {
        $this->ensureBelongsToClinic($clinic, $member);

        $validated = $this->validateMember($request, $member);
        $previousFees = $member->varFees;

        $this->fillMember($member, $request, $validated);

        $newPassword = $validated['password'] ?? null;
        if (! empty($newPassword)) {
            $member->password = Hash::make($newPassword);
        }

        $member->save();

        if ($member->role === User::ROLE_DOCTOR) {
            $this->replaceExperiences($member->id, $validated['experiences'] ?? []);

            if ((string) $previousFees !== (string) $member->varFees) {
                DoctorActivity::create([
                    'doctor_id' => $member->id,
                    'activity_type' => 'consultation_fee_updated',
                    'description' => 'Consultation fee updated from ' . $previousFees . ' to ' . $member->varFees . ' by admin.',
                    'meta' => [
                        'old_fee' => $previousFees,
                        'new_fee' => $member->varFees,
                    ],
                ]);
            }
        }

        $message = $this->displayName($member) . '\'s profile was updated.';

        if (! empty($newPassword) && $request->boolean('send_credentials')) {
            $message .= $this->sendCredentials($member, $clinic, $newPassword)
                ? ' The new password was emailed to ' . $member->email . '.'
                : ' The password email could not be sent, so please share it manually.';
        }

        return redirect()
            ->route('admin.clinic.show', $clinic)
            ->with('success', $message);
    }

    public function link(Request $request, Clinic $clinic)
    {
        $validated = $request->validate([
            'doctor_ids' => 'required|array|min:1',
            'doctor_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_DOCTOR)->whereNull('clinic_id'),
            ],
        ], [
            'doctor_ids.required' => 'Select at least one doctor to link.',
            'doctor_ids.*.exists' => 'One of the selected doctors is already linked to a clinic.',
        ]);

        $linked = User::doctors()
            ->whereIn('id', $validated['doctor_ids'])
            ->whereNull('clinic_id')
            ->update(['clinic_id' => $clinic->id]);

        return redirect()
            ->route('admin.clinic.show', $clinic)
            ->with('success', $linked === 1
                ? '1 doctor was linked to ' . $clinic->name . '.'
                : $linked . ' doctors were linked to ' . $clinic->name . '.');
    }

    public function destroy(Clinic $clinic, User $member)
    {
        $this->ensureBelongsToClinic($clinic, $member);

        $name = $this->displayName($member);

        // Employees only exist inside a clinic, so removing one deletes the login.
        // Doctors keep their account and stay bookable; they are only unlinked.
        if ($member->isEmployee()) {
            $this->deleteProfilePicture($member->varProfile);
            $member->delete();

            return redirect()
                ->route('admin.clinic.show', $clinic)
                ->with('success', $name . '\'s employee account was deleted.');
        }

        $member->clinic_id = null;
        $member->save();

        return redirect()
            ->route('admin.clinic.show', $clinic)
            ->with('success', $name . ' was removed from ' . $clinic->name . '. Their doctor account is still active.');
    }

    private function validateMember(Request $request, ?User $member = null): array
    {
        $rules = [
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'first_name' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member?->id)],
            'phoneNumber' => 'nullable|string|max:30',
            'password' => [$member ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'country' => 'nullable|integer|exists:country_master,id',
            'state' => 'nullable|integer|exists:states,id',
            'city_id' => ['nullable', 'integer', $this->cityInSelectedStateRule($request)],
            'language_ids' => 'nullable|array',
            'language_ids.*' => 'integer|exists:language_master,id',
        ];

        if ($request->input('role') !== User::ROLE_EMPLOYEE) {
            $rules += [
                'category' => 'required|integer|exists:dr_category,id',
                'speciality' => 'nullable|string|max:255',
                'bio_handle' => 'nullable|string|max:5000',
                'total_experience' => 'nullable|string|max:50',
                'fees' => 'nullable|numeric|min:0',
                'consultation_time' => 'nullable|integer|min:1|max:600',
                'graduation' => 'nullable|string|max:255',
                'graduation_year' => 'nullable|string|max:20',
                'post_graduation' => 'nullable|string|max:255',
                'pg_year' => 'nullable|string|max:20',
                'gmc_registration_no' => 'nullable|string|max:100',
                'indemnity_insurance_provider' => 'nullable|string|max:255',
                'policy_no' => 'nullable|string|max:100',
                'india_registration_no' => 'nullable|string|max:100',
                'dha_reg' => 'nullable|string|max:100',
                'reg_no' => 'nullable|string|max:100',
                'chrSmartcard' => 'nullable|in:Y,N',
                'experiences' => 'nullable|array',
                'experiences.*.org_name' => 'nullable|string|max:255',
                'experiences.*.start_year' => 'nullable|string|max:20',
                'experiences.*.end_year' => 'nullable|string|max:20',
                'experiences.*.description' => 'nullable|string|max:1000',
                'experiences.*.is_current' => 'nullable|in:Y,N',
            ];
        }

        return $request->validate($rules, [
            'category.required' => 'Choose a category so the doctor can appear in the patient app.',
            'city_id.exists' => 'The selected city does not belong to the selected state.',
            'profile_picture.max' => 'The profile photo must not be larger than 4 MB.',
        ], [
            'first_name' => 'first name',
            'phoneNumber' => 'phone number',
            'city_id' => 'city',
            'language_ids.*' => 'language',
            'fees' => 'consultation fee',
            'consultation_time' => 'consultation time',
            'experiences.*.org_name' => 'organisation name',
        ]);
    }

    private function fillMember(User $member, Request $request, array $data): void
    {
        $member->role = $data['role'];
        $member->name = $data['first_name'];
        $member->surname = $data['surname'] ?? null;
        $member->email = $data['email'];
        $member->phoneNumber = $data['phoneNumber'] ?? null;
        $member->country = $data['country'] ?? null;
        $member->state = $data['state'] ?? null;
        $member->city_id = $data['city_id'] ?? null;

        $languageIds = array_values(array_unique(array_map('intval', $data['language_ids'] ?? [])));
        $member->language_ids = ! empty($languageIds) ? $languageIds : null;

        if ($request->hasFile('profile_picture')) {
            $this->deleteProfilePicture($member->varProfile);
            $member->varProfile = $this->storeProfilePicture($request->file('profile_picture'));
        } elseif ($request->boolean('remove_profile_picture')) {
            $this->deleteProfilePicture($member->varProfile);
            $member->varProfile = null;
        }

        if ($member->role !== User::ROLE_DOCTOR) {
            return;
        }

        $member->category = $data['category'];
        $member->varSpeciality = $data['speciality'] ?? null;
        $member->bio_handle = $data['bio_handle'] ?? null;
        $member->varExperience = $data['total_experience'] ?? null;
        $member->varFees = $data['fees'] ?? null;
        $member->varTimeDuration = $data['consultation_time'] ?? $this->defaultConsultationTime();
        $member->varGraduation = $data['graduation'] ?? null;
        $member->varGraduationYear = $data['graduation_year'] ?? null;
        $member->varPostGraduation = $data['post_graduation'] ?? null;
        $member->varPostGraduationYear = $data['pg_year'] ?? null;
        $member->gmc_registration_no = $data['gmc_registration_no'] ?? null;
        $member->indemnity_insurance_provider = $data['indemnity_insurance_provider'] ?? null;
        $member->policy_no = $data['policy_no'] ?? null;
        $member->india_registration_no = $data['india_registration_no'] ?? null;
        $member->dha_reg = $data['dha_reg'] ?? null;
        $member->reg_no = $data['reg_no'] ?? null;
        $member->chrSmartcard = $data['chrSmartcard'] ?? null;
    }

    private function replaceExperiences(int $userId, array $entries): void
    {
        OrgExperience::where('user_id', $userId)->delete();

        foreach ($entries as $entry) {
            $orgName = trim((string) ($entry['org_name'] ?? ''));
            if ($orgName === '') {
                continue;
            }

            $isCurrent = ($entry['is_current'] ?? 'N') === 'Y';

            OrgExperience::create([
                'user_id' => $userId,
                'title' => $orgName,
                'startYear' => $entry['start_year'] ?? null,
                'endYear' => $isCurrent ? null : ($entry['end_year'] ?? null),
                'varDescription' => $entry['description'] ?? null,
                'isCurrentworkOrg' => $isCurrent ? 'Y' : 'N',
            ]);
        }
    }

    private function memberFormOptions($stateId): array
    {
        return array_merge($this->locationOptions($stateId), [
            'languages' => Language::where('chrPublish', 'Y')->orderBy('language_name')->get(['id', 'language_name']),
            'categories' => DrCategory::orderBy('title')->get(['id', 'title']),
            'defaultConsultationTime' => $this->defaultConsultationTime(),
        ]);
    }

    private function defaultConsultationTime(): ?string
    {
        return GeneralSetting::where('field_name', 'time_duration')->value('field_value');
    }

    private function ensureBelongsToClinic(Clinic $clinic, User $member): void
    {
        abort_unless((int) $member->clinic_id === (int) $clinic->id, 404);
    }

    private function displayName(User $member): string
    {
        return trim($member->name . ' ' . ($member->surname ?? ''));
    }

    private function sendCredentials(User $member, Clinic $clinic, string $password): bool
    {
        try {
            Mail::to($member->email)->send(new ClinicMemberCredentialsMail(
                $this->displayName($member) ?: 'there',
                User::ROLES[$member->role] ?? 'Member',
                $clinic->name,
                $member->email,
                $password
            ));
        } catch (\Throwable $e) {
            report($e);

            return false;
        }

        return true;
    }

    private function storeProfilePicture(UploadedFile $file): string
    {
        $fileName = time() . '_' . Str::random(8) . '.' . $file->extension();
        $file->move(public_path(self::PROFILE_PATH), $fileName);

        return $fileName;
    }

    private function deleteProfilePicture(?string $fileName): void
    {
        if (! $fileName) {
            return;
        }

        $path = public_path(self::PROFILE_PATH . '/' . $fileName);
        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
