@extends('admin.admin')
@section('content')
<style>
    .affiliate-dashboard {
        --affiliate-green: #0f7f13;
        --affiliate-green-light: #e8f5e9;
        --affiliate-gold: #b8860b;
        --affiliate-cream: #faf9f6;
    }

    .affiliate-dashboard-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .affiliate-dashboard-header h1 {
        color: var(--affiliate-green);
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0 0 0.35rem;
    }

    .affiliate-dashboard-header p {
        color: #6b7280;
        margin: 0;
        font-size: 0.95rem;
    }

    .affiliate-month-picker {
        background: #fff;
        border: 1px solid #d7e7d8;
        border-radius: 999px;
        padding: 0.45rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }

    .affiliate-month-picker select {
        border: 0;
        background: transparent;
        font-weight: 600;
        color: #374151;
        outline: none;
        cursor: pointer;
    }

    .affiliate-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 991px) {
        .affiliate-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575px) {
        .affiliate-stat-grid {
            grid-template-columns: 1fr;
        }
    }

    .affiliate-stat-card {
        background: #fff;
        border: 1px solid #e5ebe6;
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
        box-shadow: 0 2px 8px rgba(15, 127, 19, 0.04);
    }

    .affiliate-stat-card .label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 0.35rem;
    }

    .affiliate-stat-card .value {
        font-size: 1.65rem;
        font-weight: 700;
        color: #111827;
        line-height: 1.2;
    }

    .affiliate-stat-card .value.commission {
        color: var(--affiliate-gold);
    }

    .affiliate-table-card {
        background: #fff;
        border: 1px solid #e5ebe6;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(15, 127, 19, 0.05);
    }

    .affiliate-table-card .table {
        margin-bottom: 0;
    }

    .affiliate-table-card thead th {
        background: var(--affiliate-green-light) !important;
        color: var(--affiliate-green) !important;
        border-color: #cfe8d1 !important;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 0.85rem 1rem;
    }

    .affiliate-table-card tbody td {
        padding: 0.9rem 1rem;
        border-color: #e8f0e9;
        vertical-align: middle;
    }

    .affiliate-table-card tbody tr:hover {
        background: #fbfdfb;
    }

    .affiliate-code-pill {
        display: inline-block;
        background: #f3f4f6;
        color: #4b5563;
        font-size: 0.78rem;
        font-weight: 600;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        letter-spacing: 0.03em;
    }

    .affiliate-commission-value {
        color: var(--affiliate-gold);
        font-weight: 700;
    }

    .affiliate-total-row td {
        background: var(--affiliate-green-light) !important;
        font-weight: 700;
        color: var(--affiliate-green);
        border-color: #cfe8d1 !important;
    }

    .affiliate-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }

    .affiliate-footer-note {
        color: #9ca3af;
        font-size: 0.82rem;
        margin-top: 1rem;
        line-height: 1.5;
    }

    .affiliate-modal .modal-content {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.15);
    }

    .affiliate-modal .modal-header {
        border-bottom: 1px solid #eef2ef;
        padding: 1.25rem 1.5rem;
    }

    .affiliate-modal .modal-body {
        padding: 1.25rem 1.5rem;
    }

    .affiliate-type-switch {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.35rem;
        background: #eef2ef;
        border: 1px solid #dce8dd;
        border-radius: 12px;
        padding: 0.35rem;
        margin-bottom: 1.35rem;
    }

    .affiliate-type-option {
        appearance: none;
        border: 0;
        background: transparent;
        color: #6b7280;
        font-weight: 600;
        font-size: 0.92rem;
        padding: 0.7rem 0.85rem;
        border-radius: 9px;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
        line-height: 1.2;
    }

    .affiliate-type-option:hover {
        color: var(--affiliate-green);
    }

    .affiliate-type-option.is-active {
        background: #fff;
        color: var(--affiliate-green);
        box-shadow: 0 1px 4px rgba(15, 127, 19, 0.12);
    }

    .affiliate-form-section {
        margin-bottom: 1.1rem;
    }

    .affiliate-form-section-title {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: 0.65rem;
    }

    .affiliate-default-commission {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
        background: #fff;
        border: 1px solid #dce8dd;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        margin-bottom: 1.25rem;
    }

    .affiliate-default-commission label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #374151;
        margin: 0;
        white-space: nowrap;
    }

    .affiliate-default-commission .commission-input-wrap {
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .affiliate-default-commission input {
        width: 72px;
        text-align: center;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 0.35rem 0.5rem;
        font-weight: 600;
    }

    .affiliate-default-commission .save-default-btn {
        margin-left: auto;
    }

    .affiliate-modal .form-label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.35rem;
    }

    .affiliate-modal .form-control,
    .affiliate-modal .form-select {
        border-radius: 10px;
        border-color: #d1d5db;
        padding: 0.65rem 0.85rem;
    }

    .affiliate-modal .form-control:focus,
    .affiliate-modal .form-select:focus {
        border-color: var(--affiliate-green);
        box-shadow: 0 0 0 0.2rem rgba(16, 144, 20, 0.15);
    }

    .affiliate-modal .input-group-text {
        background: #f3f4f6;
        border-color: #d1d5db;
        font-weight: 600;
        color: #6b7280;
    }

    .affiliate-modal .modal-footer {
        border-top: 1px solid #eef2ef;
        padding: 1rem 1.5rem;
    }

    .affiliate-code-input-group .btn {
        white-space: nowrap;
    }

    #qrModalCanvas {
        display: flex;
        justify-content: center;
        padding: 1rem 0;
    }

    #qrModalCanvas canvas,
    #qrModalCanvas img {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 8px;
        background: #fff;
    }

    .affiliate-referral-url {
        background: #f8faf8;
        border: 1px dashed #cfe8d1;
        border-radius: 8px;
        padding: 0.65rem 0.85rem;
        font-size: 0.82rem;
        word-break: break-all;
        color: #374151;
    }
</style>

<div class="container-fluid affiliate-dashboard">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="affiliate-dashboard-header">
        <div>
            <h1>Affiliate commission dashboard</h1>
            <p>Bookings and commission owed, tracked month by month.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="GET" action="{{ route('admin.affiliate') }}" class="affiliate-month-picker">
                <select name="month" onchange="this.form.submit()">
                    @foreach($monthOptions as $monthNumber => $monthLabel)
                        <option value="{{ $monthNumber }}" {{ (int) $selectedMonth === (int) $monthNumber ? 'selected' : '' }}>{{ $monthLabel }}</option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()">
                    @foreach($yearOptions as $yearOption)
                        <option value="{{ $yearOption }}" {{ (int) $selectedYear === (int) $yearOption ? 'selected' : '' }}>{{ $yearOption }}</option>
                    @endforeach
                </select>
            </form>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAffiliateModal">
                <i class="bi bi-plus-lg me-1"></i> Add Affiliate
            </button>
        </div>
    </div>

    <div class="affiliate-default-commission">
        <label for="defaultCommissionRate">Default commission rate</label>
        <form method="POST" action="{{ route('admin.affiliate.default-commission') }}" id="defaultCommissionForm" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
            @csrf
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">
            <div class="commission-input-wrap">
                <input type="number" step="0.01" min="0" max="100" name="affiliate_commission_percentage" id="defaultCommissionRate" value="{{ rtrim(rtrim(number_format($defaultCommissionRate, 2), '0'), '.') }}" required>
                <span class="text-muted fw-semibold">%</span>
            </div>
            <small class="text-muted">Applied to affiliates without a custom rate.</small>
            <button type="submit" class="btn btn-sm btn-outline-primary save-default-btn">Save default</button>
        </form>
    </div>

    <div class="affiliate-stat-grid">
        <div class="affiliate-stat-card">
            <div class="label">Active Affiliates</div>
            <div class="value">{{ $activeAffiliates }}</div>
        </div>
        <div class="affiliate-stat-card">
            <div class="label">Bookings This Month</div>
            <div class="value">{{ number_format($totalBookings) }}</div>
        </div>
        <div class="affiliate-stat-card">
            <div class="label">Booking Value</div>
            <div class="value">₹{{ number_format($totalValue, 0) }}</div>
        </div>
        <div class="affiliate-stat-card">
            <div class="label">Commission Owed</div>
            <div class="value commission">₹{{ number_format($totalCommission, 0) }}</div>
        </div>
    </div>

    <div class="affiliate-table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Affiliate</th>
                        <th>Code</th>
                        <th>Bookings</th>
                        <th>Total Value</th>
                        <th>Commission Owed</th>
                        <th>QR Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($affiliateStats as $row)
                        @php
                            $affiliate = $row['affiliate'];
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $affiliate->name }}</div>
                                @if($affiliate->doctor)
                                    <small class="text-muted">{{ $affiliate->doctor->email }}</small>
                                @endif
                            </td>
                            <td><span class="affiliate-code-pill">{{ $affiliate->code }}</span></td>
                            <td>{{ number_format($row['bookings']) }}</td>
                            <td>₹{{ number_format($row['total_value'], 0) }}</td>
                            <td><span class="affiliate-commission-value">₹{{ number_format($row['commission_owed'], 0) }}</span></td>
                            <td>
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm show-qr-btn"
                                    data-name="{{ $affiliate->name }}"
                                    data-code="{{ $affiliate->code }}"
                                    data-url="{{ $affiliate->referralUrl() }}">
                                    <i class="bi bi-qr-code"></i> View
                                </button>
                            </td>
                            <td>
                                <div class="affiliate-actions">
                                    <button type="button"
                                        class="btn btn-outline-secondary btn-sm edit-affiliate-btn"
                                        data-id="{{ $affiliate->id }}"
                                        data-name="{{ $affiliate->name }}"
                                        data-code="{{ $affiliate->code }}"
                                        data-doctor-id="{{ $affiliate->doctor_id }}"
                                        data-commission-rate="{{ $affiliate->commission_rate }}"
                                        data-is-active="{{ $affiliate->is_active ? '1' : '0' }}">
                                        Edit
                                    </button>
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm delete-affiliate-btn"
                                        data-id="{{ $affiliate->id }}"
                                        data-name="{{ $affiliate->name }}">
                                        Remove
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No affiliates yet. Click <strong>Add Affiliate</strong> to register a doctor or clinic.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($affiliateStats) > 0)
                    <tfoot>
                        <tr class="affiliate-total-row">
                            <td>Total</td>
                            <td></td>
                            <td>{{ number_format($totalBookings) }}</td>
                            <td>₹{{ number_format($totalValue, 0) }}</td>
                            <td>₹{{ number_format($totalCommission, 0) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <p class="affiliate-footer-note">
        Snapshot of affiliate commission for {{ $monthOptions[$selectedMonth] ?? '' }} {{ $selectedYear }}.
        Commission is calculated on paid booking value at each affiliate's rate (default {{ rtrim(rtrim(number_format($defaultCommissionRate, 2), '0'), '.') }}%).
        Patients who register via an affiliate QR code are linked to that affiliate for tracking.
    </p>
</div>

{{-- Add Affiliate Modal --}}
<div class="modal fade affiliate-modal" id="addAffiliateModal" tabindex="-1" aria-labelledby="addAffiliateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.affiliate.store') }}" id="addAffiliateForm">
                @csrf
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="source_type" id="addSourceType" value="existing_doctor">

                <div class="modal-header">
                    <h5 class="modal-title" id="addAffiliateModalLabel">Add Affiliate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="affiliate-form-section">
                        <div class="affiliate-form-section-title">Affiliate type</div>
                        <div class="affiliate-type-switch" role="tablist" aria-label="Affiliate type">
                            <button type="button" class="affiliate-type-option is-active" data-source="existing_doctor" data-form="add">
                                Existing Doctor
                            </button>
                            <button type="button" class="affiliate-type-option" data-source="custom" data-form="add">
                                New Affiliate
                            </button>
                        </div>
                    </div>

                    <div class="affiliate-form-section" id="addExistingDoctorBlock">
                        <div class="affiliate-form-section-title">Doctor details</div>
                        <div class="mb-0">
                            <label for="addDoctorId" class="form-label">Select Doctor <span class="text-danger">*</span></label>
                            <select class="form-select" name="doctor_id" id="addDoctorId">
                                <option value="">Choose an approved doctor...</option>
                                @foreach($approvedDoctors as $doctor)
                                    @if(!in_array($doctor->id, $existingDoctorIds))
                                        <option value="{{ $doctor->id }}">{{ trim($doctor->name . ' ' . ($doctor->surname ?? '')) }} — {{ $doctor->email }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="affiliate-form-section d-none" id="addCustomBlock">
                        <div class="affiliate-form-section-title">Affiliate details</div>
                        <div class="mb-0">
                            <label for="addAffiliateName" class="form-label">Affiliate / Clinic Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="addAffiliateName" placeholder="e.g. Dr. Sharma Clinic">
                        </div>
                    </div>

                    <div class="affiliate-form-section">
                        <div class="affiliate-form-section-title">Referral code</div>
                        <div class="mb-0">
                            <label for="addAffiliateCode" class="form-label">Affiliate Code</label>
                            <div class="input-group affiliate-code-input-group">
                                <input type="text" class="form-control text-uppercase" name="code" id="addAffiliateCode" placeholder="Leave blank to auto-generate" maxlength="50" pattern="[A-Za-z0-9_-]+">
                                <button type="button" class="btn btn-outline-secondary generate-code-btn" data-target="#addAffiliateCode">Generate</button>
                            </div>
                            <small class="text-muted d-block mt-1">Enter a custom code or click Generate for a unique code.</small>
                        </div>
                    </div>

                    <div class="affiliate-form-section mb-0">
                        <div class="affiliate-form-section-title">Commission</div>
                        <div class="mb-0">
                            <label for="addCommissionRate" class="form-label">Commission Rate</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control" name="commission_rate" id="addCommissionRate" placeholder="{{ rtrim(rtrim(number_format($defaultCommissionRate, 2), '0'), '.') }}">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted d-block mt-1">Leave blank to use the default rate (currently {{ rtrim(rtrim(number_format($defaultCommissionRate, 2), '0'), '.') }}%).</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Affiliate &amp; QR</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Affiliate Modal --}}
<div class="modal fade affiliate-modal" id="editAffiliateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="editAffiliateForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="month" value="{{ $selectedMonth }}">
                <input type="hidden" name="year" value="{{ $selectedYear }}">
                <input type="hidden" name="source_type" id="editSourceType" value="existing_doctor">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Affiliate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="affiliate-form-section">
                        <div class="affiliate-form-section-title">Affiliate type</div>
                        <div class="affiliate-type-switch" role="tablist" aria-label="Affiliate type">
                            <button type="button" class="affiliate-type-option is-active" data-source="existing_doctor" data-form="edit">
                                Existing Doctor
                            </button>
                            <button type="button" class="affiliate-type-option" data-source="custom" data-form="edit">
                                Custom Affiliate
                            </button>
                        </div>
                    </div>

                    <div class="affiliate-form-section" id="editExistingDoctorBlock">
                        <div class="affiliate-form-section-title">Doctor details</div>
                        <div class="mb-0">
                            <label for="editDoctorId" class="form-label">Linked Doctor</label>
                            <select class="form-select" name="doctor_id" id="editDoctorId">
                                <option value="">None</option>
                                @foreach($approvedDoctors as $doctor)
                                    <option value="{{ $doctor->id }}">{{ trim($doctor->name . ' ' . ($doctor->surname ?? '')) }} — {{ $doctor->email }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="affiliate-form-section d-none" id="editCustomBlock">
                        <div class="affiliate-form-section-title">Affiliate details</div>
                        <div class="mb-0">
                            <label for="editAffiliateName" class="form-label">Affiliate / Clinic Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="editAffiliateName">
                        </div>
                    </div>

                    <div class="affiliate-form-section">
                        <div class="affiliate-form-section-title">Referral code</div>
                        <div class="mb-0">
                            <label for="editAffiliateCode" class="form-label">Affiliate Code</label>
                            <div class="input-group affiliate-code-input-group">
                                <input type="text" class="form-control text-uppercase" name="code" id="editAffiliateCode" maxlength="50" pattern="[A-Za-z0-9_-]+">
                                <button type="button" class="btn btn-outline-secondary generate-code-btn" data-target="#editAffiliateCode">Generate</button>
                            </div>
                        </div>
                    </div>

                    <div class="affiliate-form-section">
                        <div class="affiliate-form-section-title">Commission</div>
                        <div class="mb-3">
                            <label for="editCommissionRate" class="form-label">Commission Rate</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" class="form-control" name="commission_rate" id="editCommissionRate" placeholder="{{ rtrim(rtrim(number_format($defaultCommissionRate, 2), '0'), '.') }}">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted d-block mt-1">Leave blank to use the default rate (currently {{ rtrim(rtrim(number_format($defaultCommissionRate, 2), '0'), '.') }}%).</small>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editIsActive" checked>
                            <label class="form-check-label" for="editIsActive">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- QR Code Modal --}}
<div class="modal fade affiliate-modal" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalTitle">Affiliate QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <p class="text-muted mb-2" id="qrModalSubtitle"></p>
                <div id="qrModalCanvas"></div>
                <div class="affiliate-referral-url mt-2" id="qrModalUrl"></div>
                <p class="small text-muted mt-2 mb-0">Scan to open the patient referral registration page.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-outline-primary" id="downloadQrBtn">
                    <i class="bi bi-download me-1"></i> Download QR
                </button>
                <button type="button" class="btn btn-primary" id="copyReferralUrlBtn">
                    <i class="bi bi-clipboard me-1"></i> Copy Link
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    let qrInstance = null;
    let currentReferralUrl = '';

    function setSourceType(formPrefix, sourceType) {
        const isExisting = sourceType === 'existing_doctor';
        $(`#${formPrefix}SourceType`).val(sourceType);

        const tabs = $(`.affiliate-type-option[data-form="${formPrefix}"]`);
        tabs.removeClass('is-active');
        tabs.filter(`[data-source="${sourceType}"]`).addClass('is-active');

        if (formPrefix === 'add') {
            $('#addExistingDoctorBlock').toggleClass('d-none', !isExisting);
            $('#addCustomBlock').toggleClass('d-none', isExisting);
            $('#addDoctorId').prop('required', isExisting);
            $('#addAffiliateName').prop('required', !isExisting);
        } else {
            $('#editExistingDoctorBlock').toggleClass('d-none', !isExisting);
            $('#editCustomBlock').toggleClass('d-none', isExisting);
            $('#editAffiliateName').prop('required', !isExisting);
        }
    }

    function renderQr(url, title, subtitle) {
        currentReferralUrl = url;
        $('#qrModalTitle').text(title);
        $('#qrModalSubtitle').text(subtitle);
        $('#qrModalUrl').text(url);

        const container = document.getElementById('qrModalCanvas');
        container.innerHTML = '';
        qrInstance = new QRCode(container, {
            text: url,
            width: 220,
            height: 220,
            colorDark: '#0f7f13',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
    }

    $(function () {
        $('.affiliate-type-option').on('click', function () {
            setSourceType($(this).data('form'), $(this).data('source'));
        });

        $('.generate-code-btn').on('click', function () {
            const target = $($(this).data('target'));
            const preferred = target.val();

            $.get('{{ route('admin.affiliate.generate-code') }}', { preferred: preferred }, function (data) {
                target.val(data.code);
            });
        });

        $('.show-qr-btn').on('click', function () {
            const name = $(this).data('name');
            const code = $(this).data('code');
            const url = $(this).data('url');
            renderQr(url, 'QR Code — ' + name, 'Code: ' + code);
            new bootstrap.Modal(document.getElementById('qrModal')).show();
        });

        $('#copyReferralUrlBtn').on('click', function () {
            navigator.clipboard.writeText(currentReferralUrl).then(function () {
                $('#copyReferralUrlBtn').html('<i class="bi bi-check-lg me-1"></i> Copied!');
                setTimeout(function () {
                    $('#copyReferralUrlBtn').html('<i class="bi bi-clipboard me-1"></i> Copy Link');
                }, 2000);
            });
        });

        $('#downloadQrBtn').on('click', function () {
            const canvas = document.querySelector('#qrModalCanvas canvas');
            if (!canvas) return;
            const link = document.createElement('a');
            link.download = 'affiliate-qr.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        });

        $('.edit-affiliate-btn').on('click', function () {
            const id = $(this).data('id');
            const doctorId = $(this).data('doctor-id');
            const sourceType = doctorId ? 'existing_doctor' : 'custom';

            $('#editAffiliateForm').attr('action', '{{ url('/admin/affiliate') }}/' + id);
            $('#editAffiliateName').val($(this).data('name'));
            $('#editAffiliateCode').val($(this).data('code'));
            $('#editDoctorId').val(doctorId || '');
            $('#editCommissionRate').val($(this).data('commission-rate') || '');
            $('#editIsActive').prop('checked', $(this).data('is-active') === 1 || $(this).data('is-active') === '1');

            setSourceType('edit', sourceType);
            new bootstrap.Modal(document.getElementById('editAffiliateModal')).show();
        });

        $('.delete-affiliate-btn').on('click', function () {
            const id = $(this).data('id');
            const name = $(this).data('name');

            if (!confirm('Remove affiliate "' + name + '"? Existing patient links will be kept for records.')) {
                return;
            }

            $.ajax({
                url: '{{ url('/admin/affiliate') }}/' + id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function () {
                    location.reload();
                },
                error: function () {
                    alert('Unable to remove affiliate. Please try again.');
                }
            });
        });

        $('#addAffiliateModal').on('hidden.bs.modal', function () {
            $('#addAffiliateForm')[0].reset();
            setSourceType('add', 'existing_doctor');
        });
    });
</script>
@endsection
