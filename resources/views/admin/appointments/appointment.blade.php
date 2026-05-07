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
                    <div class="col-md-3">
                        <input type="text" id="filter_search" class="form-control" placeholder="Search appointments">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="appointment_table">
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
                            @include('admin.appointments.appointment_table')
                        </tbody>
                    </table>
                </div>

                <div id="appointment_pagination" class="mt-3">
                    {{ $appointmentData->links('pagination::bootstrap-5') }}
                </div>
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

    function getFilters() {
        return {
            date: $('#filter_date').val(),
            doctor: $('#filter_doctor').val(),
            speciality: $('#filter_speciality').val(),
            country: $('#filter_country').val(),
            state: $('#filter_state').val(),
            search: $('#filter_search').val()
        };
    }

    function loadAppointments(url = "{{ route('appointments.filter') }}") {
        $.ajax({
            url: url,
            method: "GET",
            data: getFilters(),
            success: function(data) {
                $('#appointment_table tbody').html(data.rows);
                $('#appointment_pagination').html(data.pagination);
            }
        });
    }

    let searchTimer;

    $('#filter_date, #filter_speciality').change(function() {
        loadAppointments();
    });

    $('#filter_doctor, #filter_country, #filter_state, #filter_search').on('keyup change', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            loadAppointments();
        }, 400);
    });

    $(document).on('click', '#appointment_pagination a', function(e) {
        e.preventDefault();
        loadAppointments($(this).attr('href'));
    });
});
</script>
@endsection
