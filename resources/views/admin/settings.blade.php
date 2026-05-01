@extends('admin.admin') <!-- Extend your admin layout -->

@section('content')
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif


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
    <!-- Breadcrumb -->
    
    <div class="card card-primary card-outline mb-4">
    <div class="card">       
        <div class="card-body">
            <form action="{{ url('/admin/settings/update') }}" method="POST">
                @csrf <!-- CSRF token for form security -->
                
                <div class="form-group">
                    <label for="time_duration" class="form-label">Time Duration</label>
                    <input type="text" class="form-control" id="time_duration" name="time_duration" value="{{ $settings['time_duration'] ?? '' }}" placeholder="Enter Time Duaration in Minutes" required>
                </div>
                <div class="form-group">
                    <label for="time_duration" class="form-label">Commision Percentage</label>
                    <input type="text" class="form-control" id="percentage" name="percentage" value="{{ $settings['percentage'] ?? '' }}" placeholder="Enter Percentage" required>
                </div>
                <div class="form-group">
                    <label for="reset_book_date" class="form-label">Reset Book Count Date & Time</label>
                    <input type="datetime-local" class="form-control" id="reset_book_date" name="reset_book_date"
                        value="{{ isset($settings['reset_book_date']) ? \Carbon\Carbon::parse($settings['reset_book_date'])->format('Y-m-d\TH:i') : '' }}"
                        required>
                </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
        
    </div>
</div>
</div>
@endsection
