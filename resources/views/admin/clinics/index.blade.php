@extends('admin.admin')
@section('content')
@include('admin.clinics.partials.styles')

<div class="container-fluid cm">
    <div class="cm-page-header">
        <div>
            <h1 class="cm-title">Clinics</h1>
            <p class="cm-subtitle">Manage clinic profiles and the doctors and employees who work under them.</p>
        </div>
        <div class="cm-header-actions">
            <a href="{{ route('admin.clinic.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> New clinic
            </a>
        </div>
    </div>

    @include('admin.clinics.partials.alerts')

    <div class="cm-stats">
        <div class="cm-stat">
            <span class="cm-stat-icon cm-tone-green"><i class="bi bi-hospital"></i></span>
            <div>
                <div class="cm-stat-label">Total clinics</div>
                <div class="cm-stat-value">{{ number_format($stats['total']) }}</div>
            </div>
        </div>
        <div class="cm-stat">
            <span class="cm-stat-icon cm-tone-teal"><i class="bi bi-check2-circle"></i></span>
            <div>
                <div class="cm-stat-label">Active clinics</div>
                <div class="cm-stat-value">{{ number_format($stats['active']) }}</div>
            </div>
        </div>
        <div class="cm-stat">
            <span class="cm-stat-icon cm-tone-green"><i class="bi bi-heart-pulse"></i></span>
            <div>
                <div class="cm-stat-label">Clinic doctors</div>
                <div class="cm-stat-value">{{ number_format($stats['doctors']) }}</div>
            </div>
        </div>
        <div class="cm-stat">
            <span class="cm-stat-icon cm-tone-blue"><i class="bi bi-person-badge"></i></span>
            <div>
                <div class="cm-stat-label">Employees</div>
                <div class="cm-stat-value">{{ number_format($stats['employees']) }}</div>
            </div>
        </div>
    </div>

    <div class="cm-card">
        <form method="GET" action="{{ route('admin.clinic') }}" class="cm-toolbar" role="search">
            <div class="cm-search">
                <i class="bi bi-search"></i>
                <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search by name, registration no, email or phone" aria-label="Search clinics">
            </div>
            <div class="cm-filter-group">
                <select name="status" class="form-select w-auto" aria-label="Filter by status" onchange="this.form.submit()">
                    <option value="all" @selected($status === 'all')>All statuses</option>
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="inactive" @selected($status === 'inactive')>Inactive</option>
                </select>
                <button type="submit" class="btn btn-outline-primary">Search</button>
                @if($search !== '' || $status !== 'all')
                    <a href="{{ route('admin.clinic') }}" class="btn btn-light">Clear</a>
                @endif
            </div>
        </form>

        @if($clinics->isEmpty())
            <div class="cm-empty">
                @if($search !== '' || $status !== 'all')
                    <span class="cm-empty-icon"><i class="bi bi-search"></i></span>
                    <h3>No clinics match your filters</h3>
                    <p>Try a different name or registration number, or clear the filters to see every clinic.</p>
                    <a href="{{ route('admin.clinic') }}" class="btn btn-light">Clear filters</a>
                @else
                    <span class="cm-empty-icon"><i class="bi bi-hospital"></i></span>
                    <h3>No clinics yet</h3>
                    <p>Create a clinic profile, then add the doctors and employees who work there.</p>
                    <a href="{{ route('admin.clinic.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Create first clinic</a>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="table cm-table">
                    <thead>
                        <tr>
                            <th>Clinic</th>
                            <th>Location</th>
                            <th>Contact</th>
                            <th>Doctors</th>
                            <th>Employees</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clinics as $clinic)
                            @php
                                $memberCount = $clinic->doctors_count + $clinic->employees_count;
                            @endphp
                            <tr>
                                <td style="max-width: 320px;">
                                    <div class="cm-identity">
                                        <span class="cm-avatar">
                                            @if($clinic->logo_url)
                                                <img src="{{ $clinic->logo_url }}" alt="">
                                            @else
                                                {{ $clinic->initials }}
                                            @endif
                                        </span>
                                        <div style="min-width: 0;">
                                            <a href="{{ route('admin.clinic.show', $clinic) }}" class="cm-identity-name">{{ $clinic->name }}</a>
                                            <div class="cm-identity-meta">
                                                {{ $clinic->registration_no ? 'Reg. ' . $clinic->registration_no : 'No registration number' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="cm-nowrap">
                                    @if($clinic->location_label !== '')
                                        {{ $clinic->location_label }}
                                    @else
                                        <span class="cm-muted">—</span>
                                    @endif
                                </td>
                                <td class="cm-nowrap">
                                    @if($clinic->email || $clinic->phone)
                                        @if($clinic->email)
                                            <div>{{ $clinic->email }}</div>
                                        @endif
                                        @if($clinic->phone)
                                            <div class="{{ $clinic->email ? 'cm-small cm-muted' : '' }}">{{ $clinic->phone }}</div>
                                        @endif
                                    @else
                                        <span class="cm-muted">—</span>
                                    @endif
                                </td>
                                <td><span class="cm-count"><i class="bi bi-heart-pulse"></i>{{ $clinic->doctors_count }}</span></td>
                                <td><span class="cm-count"><i class="bi bi-person-badge"></i>{{ $clinic->employees_count }}</span></td>
                                <td>
                                    @if($clinic->is_active)
                                        <span class="cm-badge cm-badge-green cm-badge-dot">Active</span>
                                    @else
                                        <span class="cm-badge cm-badge-gray cm-badge-dot">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="cm-row-actions">
                                        <a href="{{ route('admin.clinic.show', $clinic) }}" class="cm-icon-btn" title="View clinic" aria-label="View {{ $clinic->name }}">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.clinic.edit', $clinic) }}" class="cm-icon-btn" title="Edit profile" aria-label="Edit {{ $clinic->name }}">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if($memberCount > 0)
                                            <span class="d-inline-block" tabindex="0" title="Remove all {{ $memberCount }} members before deleting this clinic">
                                                <button type="button" class="cm-icon-btn is-danger" disabled aria-label="Delete unavailable while the clinic has members">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </span>
                                        @else
                                            <button type="button"
                                                class="cm-icon-btn is-danger"
                                                title="Delete clinic"
                                                aria-label="Delete {{ $clinic->name }}"
                                                data-cm-confirm="{{ route('admin.clinic.destroy', $clinic) }}"
                                                data-title="Delete {{ $clinic->name }}?"
                                                data-message="This permanently deletes the clinic profile and its logo. This can't be undone."
                                                data-confirm-label="Delete clinic">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($clinics->hasPages())
                <div class="cm-pagination">
                    {{ $clinics->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @endif
    </div>

    @include('admin.clinics.partials.confirm-modal')
</div>
@endsection
