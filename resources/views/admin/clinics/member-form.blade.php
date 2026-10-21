@extends('admin.admin')
@section('content')
@include('admin.clinics.partials.styles')

@php
    $isEdit = $member->exists;
    $role = old('role', $member->role ?: \App\Models\User::ROLE_DOCTOR);
    $isDoctor = $role !== \App\Models\User::ROLE_EMPLOYEE;
    $photoUrl = $member->varProfile ? asset('api/docterprofile/' . $member->varProfile) : null;
    $memberName = trim($member->name . ' ' . ($member->surname ?? ''));
    $selectedState = old('state', $member->state);
    $selectedCity = old('city_id', $member->city_id);
    $selectedLanguages = collect(old('language_ids', $member->language_ids ?? []))->map(fn ($id) => (int) $id)->all();
    $experienceRows = old('experiences', $experiences);
    $nextExperienceIndex = empty($experienceRows) ? 0 : max(array_map('intval', array_keys($experienceRows))) + 1;
@endphp

<div class="container-fluid cm">
    <div class="cm-page-header">
        <div>
            <nav class="cm-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.clinic') }}">Clinics</a>
                <i class="bi bi-chevron-right"></i>
                <a href="{{ route('admin.clinic.show', $clinic) }}">{{ $clinic->name }}</a>
                <i class="bi bi-chevron-right"></i>
                <span>{{ $isEdit ? 'Edit member' : 'Add member' }}</span>
            </nav>
            <h1 class="cm-title">
                @if($isEdit)
                    Edit {{ $memberName }}
                @else
                    <span data-role-text data-doctor="Add doctor" data-employee="Add employee">{{ $isDoctor ? 'Add doctor' : 'Add employee' }}</span>
                @endif
            </h1>
            <p class="cm-subtitle">
                {{ $isEdit ? 'Update this member\'s profile at ' . $clinic->name . '.' : 'The account is created already approved and linked to ' . $clinic->name . '.' }}
            </p>
        </div>
    </div>

    @include('admin.clinics.partials.alerts')

    <form method="POST"
          action="{{ $isEdit ? route('admin.clinic.members.update', [$clinic, $member]) : route('admin.clinic.members.store', $clinic) }}"
          enctype="multipart/form-data"
          class="cm-card"
          id="memberForm"
          novalidate>
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Member type --}}
        <section class="cm-form-section">
            <div>
                <h2 class="cm-section-heading"><i class="bi bi-diagram-3"></i> Member type</h2>
                <p class="cm-section-desc">Doctors get a full consultation profile. Employees only need personal and sign-in details.</p>
            </div>
            <div>
                <div class="cm-role-picker" role="radiogroup" aria-label="Member type">
                    <label class="cm-role-option">
                        <input type="radio" name="role" value="{{ \App\Models\User::ROLE_DOCTOR }}" @checked($isDoctor)>
                        <span class="cm-role-card">
                            <span class="cm-role-icon"><i class="bi bi-heart-pulse"></i></span>
                            <span>
                                <span class="cm-role-title">Doctor</span>
                                <span class="cm-role-desc">Consulting practitioner. Listed in the patient app and can receive appointments.</span>
                            </span>
                            <i class="bi bi-check-circle-fill cm-role-check"></i>
                        </span>
                    </label>
                    <label class="cm-role-option">
                        <input type="radio" name="role" value="{{ \App\Models\User::ROLE_EMPLOYEE }}" @checked(! $isDoctor)>
                        <span class="cm-role-card">
                            <span class="cm-role-icon"><i class="bi bi-person-badge"></i></span>
                            <span>
                                <span class="cm-role-title">Employee</span>
                                <span class="cm-role-desc">Clinic staff such as front desk or administration. Never shown to patients.</span>
                            </span>
                            <i class="bi bi-check-circle-fill cm-role-check"></i>
                        </span>
                    </label>
                </div>
                @error('role')<div class="text-danger cm-small mt-2">{{ $message }}</div>@enderror
            </div>
        </section>

        {{-- Personal details --}}
        <section class="cm-form-section">
            <div>
                <h2 class="cm-section-heading"><i class="bi bi-person"></i> Personal details</h2>
                <p class="cm-section-desc">Name, photo and contact details for this member.</p>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="cm-uploader" data-cm-uploader>
                        <div class="cm-uploader-preview is-round {{ $photoUrl ? 'has-image' : '' }}">
                            <img src="{{ $photoUrl }}" alt="Profile photo preview" @if(! $photoUrl) hidden @endif>
                            <span class="cm-uploader-fallback" @if($photoUrl) hidden @endif><i class="bi bi-person"></i></span>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap gap-2">
                                <label for="profile_picture" class="btn btn-outline-primary btn-sm mb-0"><i class="bi bi-upload me-1"></i> Upload photo</label>
                                <button type="button" class="btn btn-light btn-sm" data-cm-remove @if(! $photoUrl) hidden @endif>Remove</button>
                            </div>
                            <div class="form-text">PNG, JPG or WEBP, up to 4 MB.</div>
                            <input type="file" id="profile_picture" name="profile_picture" class="d-none" accept="image/png,image/jpeg,image/webp">
                            <input type="hidden" name="remove_profile_picture" value="0" data-cm-remove-flag>
                            @error('profile_picture')<div class="text-danger cm-small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First name<span class="cm-required">*</span></label>
                    <input type="text" id="first_name" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name', $member->name) }}" maxlength="255" autocomplete="off" required>
                    @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="surname" class="form-label">Surname</label>
                    <input type="text" id="surname" name="surname" class="form-control @error('surname') is-invalid @enderror" value="{{ old('surname', $member->surname) }}" maxlength="255" autocomplete="off">
                    @error('surname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email<span class="cm-required">*</span></label>
                    <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $member->email) }}" maxlength="255" autocomplete="off" placeholder="name@example.com" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @else
                        <div class="form-text">Used to sign in to the Noraya app.</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="phoneNumber" class="form-label">Phone number</label>
                    <input type="tel" id="phoneNumber" name="phoneNumber" class="form-control @error('phoneNumber') is-invalid @enderror" value="{{ old('phoneNumber', $member->phoneNumber) }}" maxlength="30" placeholder="+91 98765 43210">
                    @error('phoneNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        {{-- Sign-in --}}
        <section class="cm-form-section">
            <div>
                <h2 class="cm-section-heading"><i class="bi bi-shield-lock"></i> Sign-in</h2>
                <p class="cm-section-desc">
                    {{ $isEdit ? 'Set a new password only if you need to replace the current one.' : 'Set the password this member will use with their email to sign in.' }}
                </p>
            </div>
            <div class="row g-3">
                <div class="col-lg-9">
                    <label for="password" class="form-label">{{ $isEdit ? 'New password' : 'Password' }}@if($isEdit)<span class="cm-optional">Optional</span>@else<span class="cm-required">*</span>@endif</label>
                    <div class="input-group has-validation">
                        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="6" maxlength="100" placeholder="{{ $isEdit ? 'Leave blank to keep the current password' : 'At least 6 characters' }}">
                        <button type="button" class="btn btn-light" id="togglePassword" aria-label="Show password" title="Show password"><i class="bi bi-eye"></i></button>
                        <button type="button" class="btn btn-light" id="generatePassword"><i class="bi bi-stars me-1"></i> Generate</button>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="hidden" name="send_credentials" value="0">
                        <input class="form-check-input" type="checkbox" id="send_credentials" name="send_credentials" value="1" @checked(old('send_credentials', ! $isEdit))>
                        <label class="form-check-label" for="send_credentials">
                            {{ $isEdit ? 'Email the new password to this member' : 'Email the sign-in details to this member' }}
                        </label>
                    </div>
                </div>
            </div>
        </section>

        {{-- Location & languages --}}
        <section class="cm-form-section">
            <div>
                <h2 class="cm-section-heading"><i class="bi bi-geo-alt"></i> Location &amp; languages</h2>
                <p class="cm-section-desc">Where this member is based and the languages they speak. Patients filter doctors by both.</p>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="country" class="form-label">Country</label>
                    <select id="country" name="country" class="form-select @error('country') is-invalid @enderror">
                        <option value="">Select country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country', $member->country) === (string) $country->id)>{{ $country->countryname }}</option>
                        @endforeach
                    </select>
                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="state" class="form-label">State</label>
                    <select id="state" name="state" class="form-select @error('state') is-invalid @enderror">
                        <option value="">Select state</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" @selected((string) $selectedState === (string) $state->id)>{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="city_id" class="form-label">City</label>
                    <select id="city_id" name="city_id" class="form-select @error('city_id') is-invalid @enderror" @disabled(! $selectedState)>
                        <option value="">{{ $selectedState ? 'Select city' : 'Select a state first' }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" @selected((string) $selectedCity === (string) $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <span class="form-label d-block">Languages spoken</span>
                    @if($languages->isEmpty())
                        <div class="form-text mt-0">No published languages yet. Add them under <a href="{{ route('admin.language') }}">Language</a>.</div>
                    @else
                        <div class="cm-chip-group" role="group" aria-label="Languages spoken">
                            @foreach($languages as $language)
                                <label class="cm-chip">
                                    <input type="checkbox" name="language_ids[]" value="{{ $language->id }}" @checked(in_array((int) $language->id, $selectedLanguages, true))>
                                    <span><i class="bi bi-check2"></i>{{ $language->language_name }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                    @error('language_ids.*')<div class="text-danger cm-small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        {{-- Doctor-only sections: disabled (and hidden) for employees so nothing is submitted. --}}
        <fieldset id="doctorFields" @disabled(! $isDoctor)>
            <section class="cm-form-section">
                <div>
                    <h2 class="cm-section-heading"><i class="bi bi-heart-pulse"></i> Professional profile</h2>
                    <p class="cm-section-desc">Shown on the doctor's profile in the patient app. A category is required for the doctor to be listed.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="category" class="form-label">Category<span class="cm-required">*</span></label>
                        <select id="category" name="category" class="form-select @error('category') is-invalid @enderror">
                            <option value="">Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category', $member->category) === (string) $category->id)>{{ $category->title }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="speciality" class="form-label">Speciality</label>
                        <input type="text" id="speciality" name="speciality" class="form-control @error('speciality') is-invalid @enderror" value="{{ old('speciality', $member->varSpeciality) }}" maxlength="255" placeholder="e.g. Interventional cardiology">
                        @error('speciality')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="total_experience" class="form-label">Total experience</label>
                        <div class="input-group has-validation">
                            <input type="text" inputmode="numeric" id="total_experience" name="total_experience" class="form-control @error('total_experience') is-invalid @enderror" value="{{ old('total_experience', $member->varExperience) }}" maxlength="50" placeholder="e.g. 8">
                            <span class="input-group-text">years</span>
                            @error('total_experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="fees" class="form-label">Consultation fee</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0" id="fees" name="fees" class="form-control @error('fees') is-invalid @enderror" value="{{ old('fees', $member->varFees) }}" placeholder="0.00">
                            @error('fees')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="consultation_time" class="form-label">Consultation time</label>
                        <div class="input-group has-validation">
                            <input type="number" min="1" max="600" step="1" id="consultation_time" name="consultation_time" class="form-control @error('consultation_time') is-invalid @enderror" value="{{ old('consultation_time', $member->varTimeDuration ?? $defaultConsultationTime) }}" placeholder="{{ $defaultConsultationTime }}">
                            <span class="input-group-text">min</span>
                            @error('consultation_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="bio_handle" class="form-label">Biography<span class="cm-optional">Optional</span></label>
                        <textarea id="bio_handle" name="bio_handle" rows="4" class="form-control @error('bio_handle') is-invalid @enderror" maxlength="5000" placeholder="A short introduction patients will read on the doctor's profile.">{{ old('bio_handle', $member->bio_handle) }}</textarea>
                        @error('bio_handle')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="cm-form-section">
                <div>
                    <h2 class="cm-section-heading"><i class="bi bi-mortarboard"></i> Education</h2>
                    <p class="cm-section-desc">Medical degrees and the year each was completed.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="graduation" class="form-label">Graduation</label>
                        <input type="text" id="graduation" name="graduation" class="form-control @error('graduation') is-invalid @enderror" value="{{ old('graduation', $member->varGraduation) }}" maxlength="255" placeholder="e.g. MBBS">
                        @error('graduation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="graduation_year" class="form-label">Graduation year</label>
                        <input type="text" inputmode="numeric" id="graduation_year" name="graduation_year" class="form-control @error('graduation_year') is-invalid @enderror" value="{{ old('graduation_year', $member->varGraduationYear) }}" maxlength="20" placeholder="YYYY">
                        @error('graduation_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-8">
                        <label for="post_graduation" class="form-label">Post graduation</label>
                        <input type="text" id="post_graduation" name="post_graduation" class="form-control @error('post_graduation') is-invalid @enderror" value="{{ old('post_graduation', $member->varPostGraduation) }}" maxlength="255" placeholder="e.g. MD, General Medicine">
                        @error('post_graduation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="pg_year" class="form-label">Post graduation year</label>
                        <input type="text" inputmode="numeric" id="pg_year" name="pg_year" class="form-control @error('pg_year') is-invalid @enderror" value="{{ old('pg_year', $member->varPostGraduationYear) }}" maxlength="20" placeholder="YYYY">
                        @error('pg_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="cm-form-section">
                <div>
                    <h2 class="cm-section-heading"><i class="bi bi-patch-check"></i> Registration &amp; compliance</h2>
                    <p class="cm-section-desc">Licensing and indemnity details. Fill in the registrations that apply to this doctor.</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="india_registration_no" class="form-label">India registration no.</label>
                        <input type="text" id="india_registration_no" name="india_registration_no" class="form-control @error('india_registration_no') is-invalid @enderror" value="{{ old('india_registration_no', $member->india_registration_no) }}" maxlength="100" placeholder="NMC / state medical council">
                        @error('india_registration_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="gmc_registration_no" class="form-label">GMC registration no.</label>
                        <input type="text" id="gmc_registration_no" name="gmc_registration_no" class="form-control @error('gmc_registration_no') is-invalid @enderror" value="{{ old('gmc_registration_no', $member->gmc_registration_no) }}" maxlength="100" placeholder="UK General Medical Council">
                        @error('gmc_registration_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="dha_reg" class="form-label">DHA registration</label>
                        <input type="text" id="dha_reg" name="dha_reg" class="form-control @error('dha_reg') is-invalid @enderror" value="{{ old('dha_reg', $member->dha_reg) }}" maxlength="100" placeholder="Dubai Health Authority">
                        @error('dha_reg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="reg_no" class="form-label">Other registration no.</label>
                        <input type="text" id="reg_no" name="reg_no" class="form-control @error('reg_no') is-invalid @enderror" value="{{ old('reg_no', $member->reg_no) }}" maxlength="100">
                        @error('reg_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="indemnity_insurance_provider" class="form-label">Indemnity insurance provider</label>
                        <input type="text" id="indemnity_insurance_provider" name="indemnity_insurance_provider" class="form-control @error('indemnity_insurance_provider') is-invalid @enderror" value="{{ old('indemnity_insurance_provider', $member->indemnity_insurance_provider) }}" maxlength="255">
                        @error('indemnity_insurance_provider')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="policy_no" class="form-label">Policy no.</label>
                        <input type="text" id="policy_no" name="policy_no" class="form-control @error('policy_no') is-invalid @enderror" value="{{ old('policy_no', $member->policy_no) }}" maxlength="100">
                        @error('policy_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="chrSmartcard" class="form-label">Smartcard</label>
                        @php
                            $smartcard = old('chrSmartcard', $member->chrSmartcard);
                        @endphp
                        <select id="chrSmartcard" name="chrSmartcard" class="form-select @error('chrSmartcard') is-invalid @enderror">
                            {{-- Blank keeps the stored value, so only offer it when there is no Yes/No yet. --}}
                            @unless(in_array($member->chrSmartcard, ['Y', 'N'], true))
                                <option value="">Not specified</option>
                            @endunless
                            <option value="Y" @selected($smartcard === 'Y')>Yes</option>
                            <option value="N" @selected($smartcard === 'N')>No</option>
                        </select>
                        @error('chrSmartcard')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>

            <section class="cm-form-section">
                <div>
                    <h2 class="cm-section-heading"><i class="bi bi-briefcase"></i> Work experience</h2>
                    <p class="cm-section-desc">Hospitals and clinics the doctor has worked at, listed on their profile.</p>
                </div>
                <div>
                    <div class="cm-repeater" id="experienceList">
                        @foreach($experienceRows as $index => $experience)
                            @include('admin.clinics.partials.experience-row', ['index' => $index, 'experience' => $experience])
                        @endforeach
                    </div>
                    <div class="cm-repeater-empty" id="experienceEmpty" @if(! empty($experienceRows)) hidden @endif>
                        No work experience added yet.
                    </div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-3" id="addExperience">
                        <i class="bi bi-plus-lg me-1"></i> Add experience
                    </button>
                    <template id="experienceTemplate">
                        @include('admin.clinics.partials.experience-row', ['index' => '__INDEX__', 'experience' => []])
                    </template>
                </div>
            </section>
        </fieldset>

        <div class="cm-form-actions">
            <span class="cm-form-actions-hint"><span class="text-danger">*</span> Required field</span>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.clinic.show', $clinic) }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2 me-1"></i>
                    @if($isEdit)
                        Save changes
                    @else
                        <span data-role-text data-doctor="Create doctor" data-employee="Create employee">{{ $isDoctor ? 'Create doctor' : 'Create employee' }}</span>
                    @endif
                </button>
            </div>
        </div>
    </form>
</div>

@include('admin.clinics.partials.form-scripts')
<script>
    (function () {
        // Role switch: doctor-only sections are hidden and excluded from the submission for employees.
        var doctorFields = document.getElementById('doctorFields');
        var roleInputs = document.querySelectorAll('input[name="role"]');

        function syncRole() {
            var checked = document.querySelector('input[name="role"]:checked');
            var role = checked ? checked.value : 'doctor';
            doctorFields.disabled = role !== 'doctor';
            document.querySelectorAll('[data-role-text]').forEach(function (element) {
                element.textContent = element.getAttribute('data-' + role);
            });
        }

        roleInputs.forEach(function (input) {
            input.addEventListener('change', syncRole);
        });

        // Password: show/hide and generate.
        var passwordInput = document.getElementById('password');
        var toggleButton = document.getElementById('togglePassword');

        function setPasswordVisible(visible) {
            passwordInput.type = visible ? 'text' : 'password';
            toggleButton.innerHTML = visible ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
            toggleButton.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
            toggleButton.setAttribute('title', visible ? 'Hide password' : 'Show password');
        }

        toggleButton.addEventListener('click', function () {
            setPasswordVisible(passwordInput.type === 'password');
        });

        document.getElementById('generatePassword').addEventListener('click', function () {
            var alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789@#$%&';
            var values = new Uint32Array(12);
            window.crypto.getRandomValues(values);
            passwordInput.value = Array.prototype.map.call(values, function (value) {
                return alphabet.charAt(value % alphabet.length);
            }).join('');
            setPasswordVisible(true);
            passwordInput.focus();
        });

        // Work experience repeater.
        var experienceList = document.getElementById('experienceList');
        var experienceEmpty = document.getElementById('experienceEmpty');
        var experienceTemplate = document.getElementById('experienceTemplate');
        var nextExperienceIndex = {{ $nextExperienceIndex }};

        function syncExperienceEmpty() {
            experienceEmpty.hidden = experienceList.children.length > 0;
        }

        document.getElementById('addExperience').addEventListener('click', function () {
            var wrapper = document.createElement('div');
            wrapper.innerHTML = experienceTemplate.innerHTML.replace(/__INDEX__/g, String(nextExperienceIndex++)).trim();
            var row = wrapper.firstElementChild;
            experienceList.appendChild(row);
            syncExperienceEmpty();
            row.querySelector('input').focus();
        });

        experienceList.addEventListener('click', function (event) {
            var removeButton = event.target.closest('[data-remove-experience]');
            if (!removeButton) {
                return;
            }
            removeButton.closest('[data-experience-row]').remove();
            syncExperienceEmpty();
        });

        experienceList.addEventListener('change', function (event) {
            if (!event.target.matches('[data-current-toggle]')) {
                return;
            }
            var endYear = event.target.closest('[data-experience-row]').querySelector('[data-end-year]');
            endYear.disabled = event.target.checked;
            endYear.placeholder = event.target.checked ? 'Present' : 'YYYY';
            if (event.target.checked) {
                endYear.value = '';
            }
        });
    })();
</script>
@endsection
