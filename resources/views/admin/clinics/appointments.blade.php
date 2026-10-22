@extends('admin.admin')
@section('content')
@include('admin.clinics.partials.styles')

@php
    $dateString = $date->toDateString();
    $routeFor = fn ($day) => route('admin.clinic.appointments', ['clinic' => $clinic, 'date' => $day]);
    $formatTime = fn ($time) => $time ? date('g:i A', strtotime($time)) : null;
    $personName = fn ($person, string $lastNameField) => $person ? trim($person->name . ' ' . ($person->{$lastNameField} ?? '')) : null;
@endphp

<div class="container-fluid cm">
    <div class="cm-page-header">
        <div>
            <nav class="cm-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('admin.clinic') }}">Clinics</a>
                <i class="bi bi-chevron-right"></i>
                <a href="{{ route('admin.clinic.show', $clinic) }}">{{ $clinic->name }}</a>
                <i class="bi bi-chevron-right"></i>
                <span>Appointments &amp; tokens</span>
            </nav>
            <h1 class="cm-title">Appointments &amp; tokens</h1>
            <p class="cm-subtitle">{{ $clinic->name }} · {{ $date->format('l, d F Y') }}</p>
        </div>
        <form method="GET" action="{{ route('admin.clinic.appointments', $clinic) }}" class="cm-date-nav">
            <a href="{{ $routeFor($date->copy()->subDay()->toDateString()) }}" class="cm-icon-btn" title="Previous day" aria-label="Previous day">
                <i class="bi bi-chevron-left"></i>
            </a>
            <input type="date" name="date" value="{{ $dateString }}" class="form-control" aria-label="Choose date" onchange="this.form.submit()">
            <a href="{{ $routeFor($date->copy()->addDay()->toDateString()) }}" class="cm-icon-btn" title="Next day" aria-label="Next day">
                <i class="bi bi-chevron-right"></i>
            </a>
            @unless($date->isToday())
                <a href="{{ route('admin.clinic.appointments', $clinic) }}" class="btn btn-light btn-sm">Today</a>
            @endunless
        </form>
    </div>

    <div class="cm-stats">
        <div class="cm-stat">
            <span class="cm-stat-icon cm-tone-green"><i class="bi bi-ticket-perforated"></i></span>
            <div>
                <div class="cm-stat-label">Tokens booked</div>
                <div class="cm-stat-value">
                    {{ $stats['occupied'] }}@if($clinic->max_tokens)<span class="cm-stat-suffix"> / {{ $clinic->max_tokens }}</span>@endif
                </div>
            </div>
        </div>
        <div class="cm-stat">
            <span class="cm-stat-icon cm-tone-teal"><i class="bi bi-grid-3x3-gap"></i></span>
            <div>
                <div class="cm-stat-label">Tokens available</div>
                <div class="cm-stat-value">{{ $clinic->max_tokens ? $stats['available'] : '∞' }}</div>
            </div>
        </div>
        <div class="cm-stat">
            <span class="cm-stat-icon cm-tone-blue"><i class="bi bi-person-check"></i></span>
            <div>
                <div class="cm-stat-label">Patients present</div>
                <div class="cm-stat-value">{{ $stats['present'] }}<span class="cm-stat-suffix"> / {{ $stats['occupied'] }}</span></div>
            </div>
        </div>
        <div class="cm-stat">
            <span class="cm-stat-icon cm-tone-amber"><i class="bi bi-megaphone"></i></span>
            <div>
                <div class="cm-stat-label">Now serving</div>
                <div class="cm-stat-value">
                    @if($runningToken > 0)
                        {{ str_pad($runningToken, 2, '0', STR_PAD_LEFT) }}
                    @else
                        <span class="cm-stat-muted">Not started</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Token board --}}
    <div class="cm-card mb-4">
        <div class="cm-card-header">
            <div>
                <h2 class="cm-card-title">Token board</h2>
                <p class="cm-card-subtitle">
                    @if($clinic->max_tokens)
                        {{ $stats['occupied'] }} of {{ $clinic->max_tokens }} tokens booked for {{ $date->format('d M Y') }}.
                    @else
                        No daily limit set for this clinic. Showing booked tokens plus the next free one.
                    @endif
                </p>
            </div>
            <div class="cm-legend" aria-hidden="true">
                <span><i class="cm-token-swatch"></i> Available</span>
                <span><i class="cm-token-swatch is-booked"></i> Booked</span>
                <span><i class="cm-token-swatch is-booked is-present"></i> Present</span>
                <span><i class="cm-token-swatch is-running"></i> Now serving</span>
            </div>
        </div>
        <div class="cm-card-body">
            <div class="cm-token-grid">
                @for($number = 1; $number <= $totalTokens; $number++)
                    @php
                        $booking = $occupied->get($number);
                        $isPresent = $booking && (int) $booking->is_present === 1;
                        $label = 'Token ' . $number . ': ';
                        $label .= $booking
                            ? ($personName($booking->patient, 'lastname') ?: 'Patient') . ($booking->doctor ? ' with Dr. ' . $personName($booking->doctor, 'surname') : '') . ($isPresent ? ' (present)' : '')
                            : 'available';
                    @endphp
                    <div class="cm-token {{ $booking ? 'is-booked' : '' }} {{ $isPresent ? 'is-present' : '' }} {{ $runningToken === $number ? 'is-running' : '' }}"
                         title="{{ $label }}" aria-label="{{ $label }}">
                        {{ str_pad($number, 2, '0', STR_PAD_LEFT) }}
                    </div>
                @endfor
            </div>
        </div>
    </div>

    {{-- Appointments --}}
    <div class="cm-card">
        <div class="cm-card-header">
            <div>
                <h2 class="cm-card-title">Appointments</h2>
                <p class="cm-card-subtitle">
                    {{ $appointments->count() }} {{ $appointments->count() === 1 ? 'appointment' : 'appointments' }} at {{ $clinic->name }} on {{ $date->format('d M Y') }}{{ $stats['cancelled'] ? ', including ' . $stats['cancelled'] . ' cancelled or rejected' : '' }}.
                </p>
            </div>
        </div>

        @if($appointments->isEmpty())
            <div class="cm-empty">
                <span class="cm-empty-icon"><i class="bi bi-calendar-x"></i></span>
                <h3>No appointments on this date</h3>
                <p>Patients haven't booked any doctor at {{ $clinic->name }} for {{ $date->format('d M Y') }} yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table cm-table">
                    <thead>
                        <tr>
                            <th>Token</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Present</th>
                            <th>Requested</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($appointments as $appointment)
                            @php
                                $cancelled = $appointment->chrIsCanceled === 'Y';
                                $rejected = $appointment->chrIsRejected === 'Y';
                                $patientName = $personName($appointment->patient, 'lastname');
                                $doctorName = $personName($appointment->doctor, 'surname');
                            @endphp
                            <tr class="{{ $cancelled || $rejected ? 'cm-row-muted' : '' }}">
                                <td>
                                    @if($appointment->token_no)
                                        <span class="cm-token-pill {{ $runningToken === (int) $appointment->token_no && ! $cancelled && ! $rejected ? 'is-running' : '' }}">
                                            {{ str_pad($appointment->token_no, 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                    @else
                                        <span class="cm-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold" style="color: var(--cm-ink);">{{ $patientName ?: 'Deleted patient' }}</div>
                                    <div class="cm-small cm-muted">{{ $appointment->patient?->phone ?: 'No phone' }}</div>
                                </td>
                                <td class="cm-nowrap">{{ $doctorName ? 'Dr. ' . $doctorName : '—' }}</td>
                                <td class="cm-nowrap">
                                    {{ $formatTime($appointment->startTime) }}@if($appointment->endTime) – {{ $formatTime($appointment->endTime) }}@endif
                                </td>
                                <td>
                                    @if($cancelled)
                                        <span class="cm-badge cm-badge-gray cm-badge-dot">Cancelled</span>
                                    @elseif($rejected)
                                        <span class="cm-badge cm-badge-red cm-badge-dot">Rejected</span>
                                    @elseif($appointment->chrIsAccepted === 'Y')
                                        <span class="cm-badge cm-badge-green cm-badge-dot">Accepted</span>
                                    @else
                                        <span class="cm-badge cm-badge-amber cm-badge-dot">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    @if($appointment->charIsPaid === 'Y')
                                        <span class="cm-badge {{ (int) $appointment->is_freeslot === 1 ? 'cm-badge-blue' : 'cm-badge-green' }}">
                                            {{ (int) $appointment->is_freeslot === 1 ? 'Free slot' : 'Paid' }}
                                        </span>
                                    @else
                                        <span class="cm-badge cm-badge-gray">Unpaid</span>
                                    @endif
                                </td>
                                <td>
                                    @if($cancelled || $rejected || ! $appointment->token_no)
                                        <span class="cm-muted">—</span>
                                    @elseif((int) $appointment->is_present === 1)
                                        <span class="cm-badge cm-badge-green"><i class="bi bi-check2"></i> Present</span>
                                    @else
                                        <span class="cm-badge cm-badge-gray">Not yet</span>
                                    @endif
                                </td>
                                <td class="cm-nowrap cm-muted">{{ optional($appointment->created_at)->format('d M, g:i A') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
