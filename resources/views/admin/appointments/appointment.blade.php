@extends('admin.admin')
@section('content')
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title">Appointment</h2>
            </div> <!-- /.card-header -->

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="date" id="filter_date" class="form-control" placeholder="Select Date">
                    </div>
                    <div class="col-md-3">
                        <input type="text" id="filter_doctor" class="form-control" placeholder="Enter Doctor Name">
                    </div>
                    <div class="col-md-3">
                        <select id="filter_speciality" class="form-select">
                            <option value="">Select Speciality</option>
                            <!-- Populate with specialities -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" id="filter_country" class="form-control" placeholder="Enter Country">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <input type="text" id="filter_state" class="form-control" placeholder="Enter State">
                    </div>
                </div>

                <table class="table table-bordered" id="appointment_table">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 60px">#</th>
                            <th class="text-center">Patient</th>
                            <th class="text-center">Doctor</th>
                            <th class="text-center">Speciality</th>
                            <th class="text-center">Appointment Date</th>
                            <th class="text-center">Appointment Time</th>
                            <th class="text-center">Symptom</th>
                            <th class="text-center">Symptom Detail</th>
                            <th class="text-center">Accepted</th>
                            <th class="text-center">Country</th>
                            <th class="text-center">State</th>
                        </tr>
                    </thead>
                    <tbody>
                    @if(isset($appointmentData) && count($appointmentData) > 0)
                        @foreach($appointmentData as $data)
                            <tr class="align-middle">
                                <td class="text-center">{{ $data->id }}</td>
                                <td class="text-center">{{ $data->patient .' '. $data->lastname }}</td>
                                <td class="text-center">{{ $data->doctor .' '. $data->surname }}</td>
                                <td class="text-center">{{ $data->speciality }}</td>
                                <td class="text-center">{{ \Carbon\Carbon::parse($data->varAppointment)->format('d F Y') }}</td>
                                <td class="text-center">{!! $data->startTime !!} - {!! $data->endTime !!}</td>
                                <td class="text-center">{{ $data->varSympton }}</td>
                                <td class="text-center">{!! $data->varSymptondesc !!}</td>
                                <td class="text-center">{!! ($data->chrIsAccepted == 'Y')? 'Yes' : 'No' !!}</td>
                                <td class="text-center">{!! !empty($data->country)? $data->country : '-' !!}</td>  
                                <td class="text-center">{!! !empty($data->state)? $data->state : '-' !!}</td>                                                                    
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="10" class="text-center">No records found</td>
                        </tr>
                     @endif    
                    </tbody>
                </table>
            </div> <!-- /.card-body -->
        </div> <!-- /.card -->
    </div> <!-- /.col -->
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Load specialities for the dropdown
    function loadSpecialities() {
        $.ajax({
            url: "{{ route('specialities.list') }}",
            method: "GET",
            success: function(data) {
                $('#filter_speciality').append(data);
            }
        });
    }

    loadSpecialities();

    // Filter event handlers
    $('#filter_date, #filter_doctor, #filter_speciality, #filter_country, #filter_state').change(function() {
        const filters = {
            date: $('#filter_date').val(),
            doctor: $('#filter_doctor').val(),
            speciality: $('#filter_speciality').val(),
            country: $('#filter_country').val(),
            state: $('#filter_state').val()
        };

        // Make AJAX request to filter appointments
        $.ajax({
            url: "{{ route('appointments.filter') }}",
            method: "GET",
            data: filters,
            success: function(data) {
                $('#appointment_table tbody').html(data);
            }
        });
    });
});
</script>
@endsection
