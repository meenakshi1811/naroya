@extends('admin.admin')
@section('content')
@include('admin.clinics.partials.styles')

@php
    $isEdit = $clinic->exists;
    $logoUrl = $clinic->logo_url;
    $selectedState = old('state', $clinic->state);
    $selectedCity = old('city_id', $clinic->city_id);
@endphp

<div class="container-fluid cm">
    <div class="cm-page-header">
        <div>
            <nav class="cm-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.clinic') }}">Clinics</a>
                @if($isEdit)
                    <i class="bi bi-chevron-right"></i>
                    <a href="{{ route('admin.clinic.show', $clinic) }}">{{ $clinic->name }}</a>
                @endif
                <i class="bi bi-chevron-right"></i>
                <span>{{ $isEdit ? 'Edit profile' : 'New clinic' }}</span>
            </nav>
            <h1 class="cm-title">{{ $isEdit ? 'Edit clinic profile' : 'Create clinic profile' }}</h1>
            <p class="cm-subtitle">
                {{ $isEdit ? 'Update how this clinic is identified and contacted.' : 'Set up the clinic first. You can add doctors and employees right after saving.' }}
            </p>
        </div>
    </div>

    @include('admin.clinics.partials.alerts')

    <form method="POST"
          action="{{ $isEdit ? route('admin.clinic.update', $clinic) : route('admin.clinic.store') }}"
          enctype="multipart/form-data"
          class="cm-card"
          novalidate>
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <section class="cm-form-section">
            <div>
                <h2 class="cm-section-heading"><i class="bi bi-hospital"></i> Clinic identity</h2>
                <p class="cm-section-desc">The name and logo shown wherever this clinic appears.</p>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <div class="cm-uploader" data-cm-uploader>
                        <div class="cm-uploader-preview {{ $logoUrl ? 'has-image' : '' }}">
                            <img src="{{ $logoUrl }}" alt="Clinic logo preview" @if(! $logoUrl) hidden @endif>
                            <span class="cm-uploader-fallback" @if($logoUrl) hidden @endif><i class="bi bi-image"></i></span>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap gap-2">
                                <label for="logo" class="btn btn-outline-primary btn-sm mb-0"><i class="bi bi-upload me-1"></i> Upload logo</label>
                                <button type="button" class="btn btn-light btn-sm" data-cm-remove @if(! $logoUrl) hidden @endif>Remove</button>
                            </div>
                            <div class="form-text">PNG, JPG or WEBP, up to 2 MB. A square image works best.</div>
                            <input type="file" id="logo" name="logo" class="d-none" accept="image/png,image/jpeg,image/webp">
                            <input type="hidden" name="remove_logo" value="0" data-cm-remove-flag>
                            @error('logo')<div class="text-danger cm-small mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="col-md-7">
                    <label for="name" class="form-label">Clinic name<span class="cm-required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $clinic->name) }}" placeholder="e.g. Sunrise Family Clinic" maxlength="255" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label for="registration_no" class="form-label">Registration / licence no.<span class="cm-optional">Optional</span></label>
                    <input type="text" id="registration_no" name="registration_no" class="form-control @error('registration_no') is-invalid @enderror" value="{{ old('registration_no', $clinic->registration_no) }}" placeholder="e.g. MH/CL/2024/0193" maxlength="100">
                    @error('registration_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">About the clinic<span class="cm-optional">Optional</span></label>
                    <textarea id="description" name="description" rows="4" class="form-control @error('description') is-invalid @enderror" maxlength="2000" placeholder="Services, facilities, opening hours…">{{ old('description', $clinic->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="cm-form-section">
            <div>
                <h2 class="cm-section-heading"><i class="bi bi-telephone"></i> Contact</h2>
                <p class="cm-section-desc">How patients and the Noraya team can reach the clinic.</p>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $clinic->email) }}" placeholder="frontdesk@clinic.com" maxlength="255">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $clinic->phone) }}" placeholder="+91 98765 43210" maxlength="30">
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="website" class="form-label">Website<span class="cm-optional">Optional</span></label>
                    <input type="text" id="website" name="website" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $clinic->website) }}" placeholder="www.clinic.com" maxlength="255">
                    @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="cm-form-section">
            <div>
                <h2 class="cm-section-heading"><i class="bi bi-geo-alt"></i> Address</h2>
                <p class="cm-section-desc">Where the clinic is located. Cities load once you choose a state.</p>
            </div>
            <div class="row g-3">
                <div class="col-12">
                    <label for="address" class="form-label">Street address</label>
                    <input type="text" id="address" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $clinic->address) }}" placeholder="Building, street, area" maxlength="500">
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="country" class="form-label">Country</label>
                    <select id="country" name="country" class="form-select @error('country') is-invalid @enderror">
                        <option value="">Select country</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country', $clinic->country) === (string) $country->id)>{{ $country->countryname }}</option>
                        @endforeach
                    </select>
                    @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="postal_code" class="form-label">Postal code</label>
                    <input type="text" id="postal_code" name="postal_code" class="form-control @error('postal_code') is-invalid @enderror" value="{{ old('postal_code', $clinic->postal_code) }}" placeholder="400001" maxlength="20">
                    @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="state" class="form-label">State</label>
                    <select id="state" name="state" class="form-select @error('state') is-invalid @enderror">
                        <option value="">Select state</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" @selected((string) $selectedState === (string) $state->id)>{{ $state->name }}</option>
                        @endforeach
                    </select>
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="city_id" class="form-label">City</label>
                    <select id="city_id" name="city_id" class="form-select @error('city_id') is-invalid @enderror" @disabled(! $selectedState)>
                        <option value="">{{ $selectedState ? 'Select city' : 'Select a state first' }}</option>
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}" @selected((string) $selectedCity === (string) $city->id)>{{ $city->name }}</option>
                        @endforeach
                    </select>
                    @error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="cm-form-section">
            <div>
                <h2 class="cm-section-heading"><i class="bi bi-toggle-on"></i> Status</h2>
                <p class="cm-section-desc">Mark clinics you no longer work with as inactive instead of deleting them.</p>
            </div>
            <div>
                <div class="form-check form-switch d-flex align-items-center gap-2 ps-0">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input ms-0" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $clinic->is_active))>
                    <label class="form-check-label fw-semibold" for="is_active">Clinic is active</label>
                </div>
            </div>
        </section>

        <div class="cm-form-actions">
            <span class="cm-form-actions-hint"><span class="text-danger">*</span> Required field</span>
            <div class="d-flex gap-2">
                <a href="{{ $isEdit ? route('admin.clinic.show', $clinic) : route('admin.clinic') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2 me-1"></i> {{ $isEdit ? 'Save changes' : 'Create clinic' }}
                </button>
            </div>
        </div>
    </form>
</div>

@include('admin.clinics.partials.form-scripts')
@endsection
