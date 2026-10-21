@extends('admin.admin')
@section('content')
@include('admin.clinics.partials.styles')

@php
    $doctorCount = $members->where('role', \App\Models\User::ROLE_DOCTOR)->count();
    $employeeCount = $members->where('role', \App\Models\User::ROLE_EMPLOYEE)->count();
    $addressLine = collect([$clinic->address, $clinic->cityRel?->name, $clinic->stateRel?->name, $clinic->postal_code, $clinic->countryRel?->countryname])
        ->filter()
        ->implode(', ');
@endphp

<div class="container-fluid cm">
    <div class="cm-page-header">
        <div>
            <nav class="cm-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.clinic') }}">Clinics</a>
                <i class="bi bi-chevron-right"></i>
                <span>{{ $clinic->name }}</span>
            </nav>
        </div>
    </div>

    @include('admin.clinics.partials.alerts')

    <div class="cm-card cm-hero">
        <div class="cm-hero-main">
            <div class="cm-hero-identity">
                <span class="cm-avatar cm-avatar-xl">
                    @if($clinic->logo_url)
                        <img src="{{ $clinic->logo_url }}" alt="{{ $clinic->name }} logo">
                    @else
                        {{ $clinic->initials }}
                    @endif
                </span>
                <div style="min-width: 0;">
                    <div class="cm-hero-name-row">
                        <h1 class="cm-title">{{ $clinic->name }}</h1>
                        @if($clinic->is_active)
                            <span class="cm-badge cm-badge-green cm-badge-dot">Active</span>
                        @else
                            <span class="cm-badge cm-badge-gray cm-badge-dot">Inactive</span>
                        @endif
                    </div>
                    <div class="cm-hero-sub">
                        {{ $clinic->registration_no ? 'Registration no. ' . $clinic->registration_no : 'No registration number on file' }}
                        @if($clinic->created_at)
                            · Added {{ $clinic->created_at->format('d M Y') }}
                        @endif
                    </div>
                    <div class="cm-hero-meta">
                        @if($addressLine !== '')
                            <span><i class="bi bi-geo-alt"></i>{{ $addressLine }}</span>
                        @endif
                        @if($clinic->email)
                            <a href="mailto:{{ $clinic->email }}"><i class="bi bi-envelope"></i>{{ $clinic->email }}</a>
                        @endif
                        @if($clinic->phone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $clinic->phone) }}"><i class="bi bi-telephone"></i>{{ $clinic->phone }}</a>
                        @endif
                        @if($clinic->website)
                            <a href="{{ $clinic->website }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-globe2"></i>{{ preg_replace('#^https?://#i', '', $clinic->website) }}</a>
                        @endif
                    </div>
                    @if($clinic->description)
                        <p class="cm-hero-about">{{ $clinic->description }}</p>
                    @endif
                </div>
            </div>
            <div class="cm-header-actions">
                <a href="{{ route('admin.clinic.edit', $clinic) }}" class="btn btn-light">
                    <i class="bi bi-pencil me-1"></i> Edit profile
                </a>
            </div>
        </div>
        <div class="cm-hero-stats">
            <div>
                <div class="cm-stat-label">Doctors</div>
                <div class="cm-hero-stat-value">{{ $doctorCount }}</div>
            </div>
            <div>
                <div class="cm-stat-label">Employees</div>
                <div class="cm-hero-stat-value">{{ $employeeCount }}</div>
            </div>
            <div>
                <div class="cm-stat-label">Clinic hours</div>
                <div class="cm-hero-stat-text">
                    @forelse($clinic->timing_labels as $label)
                        <div>{{ $label }}</div>
                    @empty
                        <span class="cm-muted">Not set</span>
                    @endforelse
                </div>
            </div>
            <div>
                <div class="cm-stat-label">Max tokens / day</div>
                <div class="cm-hero-stat-value">
                    @if($clinic->max_tokens)
                        {{ number_format($clinic->max_tokens) }}
                    @else
                        <span class="cm-hero-stat-text cm-muted d-inline-block">No limit set</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="cm-card">
        <div class="cm-card-header">
            <div>
                <h2 class="cm-card-title">Clinic team</h2>
                <p class="cm-card-subtitle">Doctors and employees associated with {{ $clinic->name }}.</p>
            </div>
            <div class="cm-header-actions">
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#linkDoctorModal">
                    <i class="bi bi-link-45deg me-1"></i> Link existing doctor
                </button>
                <a href="{{ route('admin.clinic.members.create', $clinic) }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i> Add member
                </a>
            </div>
        </div>

        @if($members->isEmpty())
            <div class="cm-empty">
                <span class="cm-empty-icon"><i class="bi bi-people"></i></span>
                <h3>No one is assigned to this clinic yet</h3>
                <p>Create a new doctor or employee account for this clinic, or link a doctor who already uses Noraya.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('admin.clinic.members.create', $clinic) }}" class="btn btn-primary"><i class="bi bi-heart-pulse me-1"></i> Add doctor</a>
                    <a href="{{ route('admin.clinic.members.create', ['clinic' => $clinic, 'role' => \App\Models\User::ROLE_EMPLOYEE]) }}" class="btn btn-outline-primary"><i class="bi bi-person-badge me-1"></i> Add employee</a>
                </div>
            </div>
        @else
            <div class="cm-toolbar">
                <div class="cm-tabs" role="tablist" aria-label="Filter members by role">
                    <button type="button" class="cm-tab is-active" data-member-filter="all" role="tab" aria-selected="true">
                        All <span class="cm-tab-count">{{ $members->count() }}</span>
                    </button>
                    <button type="button" class="cm-tab" data-member-filter="doctor" role="tab" aria-selected="false">
                        Doctors <span class="cm-tab-count">{{ $doctorCount }}</span>
                    </button>
                    <button type="button" class="cm-tab" data-member-filter="employee" role="tab" aria-selected="false">
                        Employees <span class="cm-tab-count">{{ $employeeCount }}</span>
                    </button>
                </div>
                <div class="cm-search">
                    <i class="bi bi-search"></i>
                    <input type="search" id="memberSearch" class="form-control" placeholder="Search name, email or phone" aria-label="Search members">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table cm-table" id="membersTable">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Role</th>
                            <th>Speciality</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Added</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($members as $member)
                            @php
                                $memberName = trim($member->name . ' ' . ($member->surname ?? ''));
                                $isEmployee = $member->isEmployee();
                                $initials = collect(preg_split('/\s+/', $memberName))->filter()->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
                            @endphp
                            <tr data-role="{{ $member->role }}" data-search="{{ mb_strtolower($memberName . ' ' . $member->email . ' ' . $member->phoneNumber) }}">
                                <td style="max-width: 320px;">
                                    <div class="cm-identity">
                                        <span class="cm-avatar is-round {{ $isEmployee ? 'is-employee' : '' }}">
                                            @if($member->varProfile)
                                                <img src="{{ asset('api/docterprofile/' . $member->varProfile) }}" alt="">
                                            @else
                                                {{ $initials ?: '?' }}
                                            @endif
                                        </span>
                                        <div style="min-width: 0;">
                                            <a href="{{ route('admin.clinic.members.edit', [$clinic, $member]) }}" class="cm-identity-name">{{ $memberName }}</a>
                                            <div class="cm-identity-meta">{{ $member->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($isEmployee)
                                        <span class="cm-badge cm-badge-blue"><i class="bi bi-person-badge"></i> Employee</span>
                                    @else
                                        <span class="cm-badge cm-badge-green"><i class="bi bi-heart-pulse"></i> Doctor</span>
                                    @endif
                                </td>
                                <td class="cm-nowrap">
                                    @if(! $isEmployee && ($member->categoryRel || $member->varSpeciality))
                                        <div>{{ $member->categoryRel->title ?? $member->varSpeciality }}</div>
                                        @if($member->categoryRel && $member->varSpeciality)
                                            <div class="cm-small cm-muted">{{ $member->varSpeciality }}</div>
                                        @endif
                                    @else
                                        <span class="cm-muted">—</span>
                                    @endif
                                </td>
                                <td class="cm-nowrap">{{ $member->phoneNumber ?: '—' }}</td>
                                <td>
                                    @if($member->chrApproval === 'Y')
                                        <span class="cm-badge cm-badge-green cm-badge-dot">Active</span>
                                    @else
                                        <span class="cm-badge cm-badge-amber cm-badge-dot">Pending approval</span>
                                    @endif
                                </td>
                                <td class="cm-nowrap cm-muted">{{ optional($member->created_at)->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <div class="cm-row-actions">
                                        <a href="{{ route('admin.clinic.members.edit', [$clinic, $member]) }}" class="cm-icon-btn" title="Edit profile" aria-label="Edit {{ $memberName }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button"
                                            class="cm-icon-btn js-reset-password"
                                            title="Reset password"
                                            aria-label="Reset password for {{ $memberName }}"
                                            data-id="{{ $member->id }}"
                                            data-name="{{ $memberName }}"
                                            data-url="{{ route('admin.doctor.reset-password', $member->id) }}">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        @if($isEmployee)
                                            <button type="button"
                                                class="cm-icon-btn is-danger"
                                                title="Delete employee"
                                                aria-label="Delete {{ $memberName }}"
                                                data-cm-confirm="{{ route('admin.clinic.members.destroy', [$clinic, $member]) }}"
                                                data-title="Delete {{ $memberName }}?"
                                                data-message="This permanently deletes the employee's account and they will no longer be able to sign in."
                                                data-confirm-label="Delete employee">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @else
                                            <button type="button"
                                                class="cm-icon-btn is-danger"
                                                title="Remove from clinic"
                                                aria-label="Remove {{ $memberName }} from clinic"
                                                data-cm-confirm="{{ route('admin.clinic.members.destroy', [$clinic, $member]) }}"
                                                data-title="Remove {{ $memberName }} from {{ $clinic->name }}?"
                                                data-message="The doctor keeps their Noraya account, appointments and listing. They are only unlinked from this clinic."
                                                data-confirm-label="Remove from clinic">
                                                <i class="bi bi-person-dash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        <tr id="membersNoMatch" hidden>
                            <td colspan="7" class="text-center cm-muted py-4">No members match this filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Link existing doctors --}}
    <div class="modal fade cm-modal" id="linkDoctorModal" tabindex="-1" aria-labelledby="linkDoctorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.clinic.members.link', $clinic) }}">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title" id="linkDoctorModalLabel">Link existing doctors</h5>
                            <p class="cm-card-subtitle mb-0">Only doctors who are not part of another clinic are listed.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($availableDoctors->isEmpty())
                            <div class="cm-empty py-4">
                                <span class="cm-empty-icon"><i class="bi bi-person-check"></i></span>
                                <h3>Every doctor is already assigned</h3>
                                <p class="mb-0">All doctors on Noraya are linked to a clinic. Use <strong>Add member</strong> to create a new doctor account.</p>
                            </div>
                        @else
                            <div class="cm-search mb-3" style="max-width: none;">
                                <i class="bi bi-search"></i>
                                <input type="search" id="linkDoctorSearch" class="form-control" placeholder="Search doctors by name or email" aria-label="Search doctors">
                            </div>
                            <div class="cm-pick-list" id="linkDoctorList">
                                @foreach($availableDoctors as $doctor)
                                    @php
                                        $doctorName = trim($doctor->name . ' ' . ($doctor->surname ?? ''));
                                    @endphp
                                    <label class="cm-pick-item" data-search="{{ mb_strtolower($doctorName . ' ' . $doctor->email) }}">
                                        <input type="checkbox" class="form-check-input" name="doctor_ids[]" value="{{ $doctor->id }}">
                                        <span class="cm-avatar cm-avatar-sm is-round">
                                            @if($doctor->varProfile)
                                                <img src="{{ asset('api/docterprofile/' . $doctor->varProfile) }}" alt="">
                                            @else
                                                {{ mb_strtoupper(mb_substr($doctor->name, 0, 1) . mb_substr((string) $doctor->surname, 0, 1)) }}
                                            @endif
                                        </span>
                                        <span class="flex-grow-1" style="min-width: 0;">
                                            <span class="cm-identity-name">{{ $doctorName }}</span>
                                            <span class="cm-identity-meta d-block">{{ $doctor->email }}</span>
                                        </span>
                                        @if($doctor->chrApproval !== 'Y')
                                            <span class="cm-badge cm-badge-amber">Pending approval</span>
                                        @endif
                                    </label>
                                @endforeach
                                <div class="text-center cm-muted py-4" id="linkDoctorNoMatch" hidden>No doctors match your search.</div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        @if($availableDoctors->isNotEmpty())
                            <button type="submit" class="btn btn-primary" id="linkDoctorSubmit" disabled>Link selected</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('admin.clinics.partials.confirm-modal')
    @include('admin.partials.reset-password-modal')
</div>

<script>
    (function () {
        // Role tabs + search on the members table.
        var table = document.getElementById('membersTable');
        if (table) {
            var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-role]'));
            var noMatch = document.getElementById('membersNoMatch');
            var searchInput = document.getElementById('memberSearch');
            var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-member-filter]'));
            var activeRole = 'all';

            var applyFilters = function () {
                var term = searchInput.value.trim().toLowerCase();
                var visible = 0;

                rows.forEach(function (row) {
                    var matchesRole = activeRole === 'all' || row.getAttribute('data-role') === activeRole;
                    var matchesTerm = term === '' || row.getAttribute('data-search').indexOf(term) !== -1;
                    row.hidden = !(matchesRole && matchesTerm);
                    if (!row.hidden) {
                        visible++;
                    }
                });

                noMatch.hidden = visible !== 0;
            };

            tabs.forEach(function (tab) {
                tab.addEventListener('click', function () {
                    activeRole = tab.getAttribute('data-member-filter');
                    tabs.forEach(function (other) {
                        var isActive = other === tab;
                        other.classList.toggle('is-active', isActive);
                        other.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });
                    applyFilters();
                });
            });

            searchInput.addEventListener('input', applyFilters);
        }

        // Reset password reuses the shared admin modal.
        document.querySelectorAll('.js-reset-password').forEach(function (button) {
            button.addEventListener('click', function () {
                openResetPasswordModal(button.getAttribute('data-id'), button.getAttribute('data-name'), button.getAttribute('data-url'));
            });
        });

        // Link doctors modal: search + selection count.
        var linkList = document.getElementById('linkDoctorList');
        if (linkList) {
            var linkItems = Array.prototype.slice.call(linkList.querySelectorAll('.cm-pick-item'));
            var linkSearch = document.getElementById('linkDoctorSearch');
            var linkNoMatch = document.getElementById('linkDoctorNoMatch');
            var linkSubmit = document.getElementById('linkDoctorSubmit');

            linkSearch.addEventListener('input', function () {
                var term = linkSearch.value.trim().toLowerCase();
                var visible = 0;
                linkItems.forEach(function (item) {
                    item.hidden = term !== '' && item.getAttribute('data-search').indexOf(term) === -1;
                    if (!item.hidden) {
                        visible++;
                    }
                });
                linkNoMatch.hidden = visible !== 0;
            });

            linkList.addEventListener('change', function () {
                var selected = linkList.querySelectorAll('input[type="checkbox"]:checked').length;
                linkSubmit.disabled = selected === 0;
                linkSubmit.textContent = selected > 0
                    ? 'Link ' + selected + ' doctor' + (selected === 1 ? '' : 's')
                    : 'Link selected';
            });
        }
    })();
</script>
@endsection
