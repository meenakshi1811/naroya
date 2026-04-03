@extends('admin.admin')
@section('content')
<!-- Bootstrap CSS -->
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- jQuery and Bootstrap JS -->
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Patients
                </h3>
            </div> <!-- /.card-header -->
            <div class="card-body">
                <table id="patientTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 10px">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Country</th>
                            <th>Profile</th>
                             <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($patients) && count($patients) > 0)
                        @foreach($patients as $key=>$data)
                        <tr class="align-middle">

                            <td>{{ $data->id }}</td>
                           <td>{{ $data->name .' '. $data->lastname }}</td>
                            <td>{{ $data->email }}</td>
                            @if(!empty($data->country))
                            @php
                            $countryName = DB::table('country_master')->select('countryname')->where('id',$data->country)->first();
                            $countryName = $countryName->countryname;
                            @endphp
                            @endif
                            <td>{!! !empty($data->country)? $countryName : '-' !!}</td>
                            @if(!empty($data->varProfile))
                            @php
                            $profileImage = config('app.url').'api/patientprofile/'.$data->varProfile;
                            @endphp

                            <td class="text-center"><img src="{{ $profileImage }}" alt="{{ $data->name }}" height="100px" width="100px" /></td>
                            @else
                            <td>N/A</td>
                            @endif
                            <td>
                                <button type="button" class="btn btn-danger" onclick="confirmDeletePatient({{ $data->id }})">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="4" class="text-center">No records found</td>
                        </tr>
                        @endif

                    </tbody>
                </table>
            </div> <!-- /.card-body -->
            <!-- <div class="card-footer clearfix">
                                    <ul class="pagination pagination-sm m-0 float-end">
                                        <li class="page-item"> <a class="page-link" href="#">&laquo;</a> </li>
                                        <li class="page-item"> <a class="page-link" href="#">1</a> </li>
                                        <li class="page-item"> <a class="page-link" href="#">2</a> </li>
                                        <li class="page-item"> <a class="page-link" href="#">3</a> </li>
                                        <li class="page-item"> <a class="page-link" href="#">&raquo;</a> </li>
                                    </ul>
                                </div> -->
        </div> <!-- /.card -->

    </div> <!-- /.col -->

</div>
<script type="text/javascript">
function confirmDeletePatient(patientId) {
        if (confirm('Are you sure you want to delete this patient record?')) {
            $.ajax({
                url: "{{ url('admin/delete-patient') }}/" + patientId,
                type: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        alert('Patient deleted successfully');
                        location.reload();
                    } else {
                        alert('Failed to delete patient: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('An error occurred while deleting the patient.');
                }
            });
        }
    }
    $(document).ready(function() {
        // Initialize DataTable
        $('#patientTable').DataTable({
            "paging": true, // Enable pagination
            "searching": true, // Enable search
            "ordering": true, // Enable sorting
            "info": true, // Enable info text (e.g., "Showing 1 to 10 of 50 entries")
            "lengthChange": true, // Allow changing the number of rows per page
            "autoWidth": false, // Disable automatic column width calculation
            "columnDefs": [{
                "targets": [0], // Disable sorting for the ID column (optional)
                "orderable": false
            }]
        });
    });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
@endsection