@extends('admin.admin')
@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Doctor Activity</h3>
            <p class="text-muted mb-0">Audit trail for profile updates and consultation fee changes.</p>
        </div>
        <a href="{{ route('admin.doctor') }}" class="btn btn-outline-secondary">Back to Doctors</a>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="mb-1">Dr. {{ $doctor->name }} {{ $doctor->surname }}</h5>
                <small class="text-muted">Doctor ID: {{ $doctor->id }}</small>
            </div>
            <span class="badge badge-info px-3 py-2">{{ $activities->count() }} Activities</span>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white"><strong>Recent Activity</strong></div>
        <div class="card-body">
            @if($activities->isEmpty())
                <div class="text-center text-muted py-4">No activity found for this doctor.</div>
            @else
                <div class="timeline-list">
                    @foreach($activities as $activity)
                        <div class="timeline-item mb-3 p-3 border rounded bg-light">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="font-weight-bold text-primary">{{ ucwords(str_replace('_', ' ', $activity->activity_type)) }}</span>
                                <small class="text-muted">{{ $activity->created_at->format('d M Y, h:i A') }}</small>
                            </div>
                            <div class="mb-2">{{ $activity->description }}</div>
                            @if(!empty($activity->meta['old_fee']) || !empty($activity->meta['new_fee']))
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge badge-pill badge-secondary">Old Fee: {{ $activity->meta['old_fee'] ?? '-' }}</span>
                                    <span class="badge badge-pill badge-success">New Fee: {{ $activity->meta['new_fee'] ?? '-' }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
