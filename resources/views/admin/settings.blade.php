@extends('admin.admin') <!-- Extend your admin layout -->

@section('content')
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@php
    $activeTab = session('active_tab', 'general');
@endphp

<div class="app-content-header"> <!--begin::Container-->
    <div class="container-fluid"> <!--begin::Row-->
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0">Settings</h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ url('/admin/settings') }}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                    Settings
                    </li>
                </ol>
            </div>
        </div> <!--end::Row-->
    </div> <!--end::Container-->
</div>

<div class="container-fluid">
    <div class="card card-primary card-outline mb-4">
        <div class="card-header p-0 border-bottom-0">
            <ul class="nav nav-tabs card-header-tabs px-3 pt-2" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'general' ? 'active' : '' }}" id="general-tab-btn" data-bs-toggle="tab" data-bs-target="#general-tab" type="button" role="tab" aria-controls="general-tab" aria-selected="{{ $activeTab === 'general' ? 'true' : 'false' }}">
                        General Settings
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'offer' ? 'active' : '' }}" id="offer-tab-btn" data-bs-toggle="tab" data-bs-target="#offer-tab" type="button" role="tab" aria-controls="offer-tab" aria-selected="{{ $activeTab === 'offer' ? 'true' : 'false' }}">
                        App User Offer
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="settingsTabsContent">
            {{-- General settings --}}
            <div class="tab-pane fade {{ $activeTab === 'general' ? 'show active' : '' }}" id="general-tab" role="tabpanel" aria-labelledby="general-tab-btn">
                <div class="card-body border-0 pb-0">
                    <p class="text-muted small mb-3">Current platform configuration. Update values below and save.</p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="text-muted small">Time duration</div>
                                <div class="fw-semibold">{{ $settings['time_duration'] ?? '—' }} min</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="text-muted small">Commission %</div>
                                <div class="fw-semibold">{{ $settings['percentage'] ?? '—' }}%</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="text-muted small">Affiliate commission</div>
                                <div class="fw-semibold">{{ $settings['affiliate_commission_percentage'] ?? '3' }}%</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="text-muted small">Reset book count</div>
                                <div class="fw-semibold">
                                    @if(!empty($settings['reset_book_date']))
                                        {{ \Carbon\Carbon::parse($settings['reset_book_date'])->format('M j, Y g:i A') }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf

                        <div class="form-group mb-3">
                            <label for="time_duration" class="form-label">Time Duration</label>
                            <input type="text" class="form-control" id="time_duration" name="time_duration" value="{{ $settings['time_duration'] ?? '' }}" placeholder="Enter Time Duaration in Minutes" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="percentage" class="form-label">Commision Percentage</label>
                            <input type="text" class="form-control" id="percentage" name="percentage" value="{{ $settings['percentage'] ?? '' }}" placeholder="Enter Percentage" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="affiliate_commission_percentage" class="form-label">Affiliate Commission Percentage</label>
                            <input type="text" class="form-control" id="affiliate_commission_percentage" name="affiliate_commission_percentage" value="{{ $settings['affiliate_commission_percentage'] ?? '3' }}" placeholder="Enter affiliate commission %" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="reset_book_date" class="form-label">Reset Book Count Date & Time</label>
                            <input type="datetime-local" class="form-control" id="reset_book_date" name="reset_book_date"
                                value="{{ !empty($settings['reset_book_date']) ? \Carbon\Carbon::parse($settings['reset_book_date'])->format('Y-m-d\TH:i') : '' }}"
                                required>
                        </div>

                        <button type="submit" class="btn btn-primary">Save General Settings</button>
                    </form>
                </div>
            </div>

            {{-- App user offer --}}
            <div class="tab-pane fade {{ $activeTab === 'offer' ? 'show active' : '' }}" id="offer-tab" role="tabpanel" aria-labelledby="offer-tab-btn">
                <div class="card-body border-0">
                    <p class="text-muted small mb-3">
                        Configure a signup or promotional offer for app users (e.g. free booking slots). The mobile app will read these values from the API when that integration is added.
                    </p>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 {{ $appUserOfferEnabled ? 'border-success' : 'border-secondary' }}">
                                <div class="text-muted small">Offer status</div>
                                <div class="fw-semibold">
                                    @if($appUserOfferEnabled)
                                        <span class="text-success">Enabled</span>
                                    @else
                                        <span class="text-secondary">Disabled</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100 bg-light">
                                <div class="text-muted small">Free slots (current)</div>
                                <div class="fw-semibold">{{ $appUserFreeSlots }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mb-4" id="offer-preview">
                        @if($appUserOfferEnabled)
                            App users will see: <strong>{{ $appUserFreeSlots }} free booking slot{{ $appUserFreeSlots === 1 ? '' : 's' }}</strong>.
                        @else
                            The offer is off — app users will not receive free slots until you enable it.
                        @endif
                    </div>

                    <form action="{{ route('admin.settings.offer.update') }}" method="POST" id="app-user-offer-form">
                        @csrf

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="app_user_offer_enabled" name="app_user_offer_enabled" value="1" {{ $appUserOfferEnabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="app_user_offer_enabled">Enable free slots offer for app users</label>
                        </div>

                        <div class="form-group mb-3" style="max-width: 12rem;">
                            <label for="app_user_free_slots" class="form-label">Number of free slots</label>
                            <input type="number" class="form-control @error('app_user_free_slots') is-invalid @enderror" id="app_user_free_slots" name="app_user_free_slots" value="{{ old('app_user_free_slots', $appUserFreeSlots) }}" min="0" max="999" required>
                            @error('app_user_free_slots')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Example: set to 5 to offer “5 free slots” in the app.</div>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Offer Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var enabledInput = document.getElementById('app_user_offer_enabled');
        var slotsInput = document.getElementById('app_user_free_slots');
        var preview = document.getElementById('offer-preview');

        if (!enabledInput || !slotsInput || !preview) {
            return;
        }

        function updateOfferPreview() {
            var enabled = enabledInput.checked;
            var slots = parseInt(slotsInput.value, 10);
            if (isNaN(slots) || slots < 0) {
                slots = 0;
            }
            if (enabled) {
                var label = slots === 1 ? 'slot' : 'slots';
                preview.innerHTML = 'App users will see: <strong>' + slots + ' free booking ' + label + '</strong>.';
                preview.className = 'alert alert-info mb-4';
            } else {
                preview.textContent = 'The offer is off — app users will not receive free slots until you enable it.';
                preview.className = 'alert alert-secondary mb-4';
            }
        }

        enabledInput.addEventListener('change', updateOfferPreview);
        slotsInput.addEventListener('input', updateOfferPreview);
    });
</script>
@endsection
