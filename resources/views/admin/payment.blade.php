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
                <h3 class="card-title">Payment
                </h3>
                @if(isset($selectedDoctor) && $selectedDoctor)
                    <p class="mb-0 mt-2 text-muted">
                        Showing records for Dr. {{ $selectedDoctor->name }} {{ $selectedDoctor->surname }} ({{ $selectedDoctor->email }})
                        - <a href="{{ route('admin.payment-log') }}">View all payments</a>
                    </p>
                @endif
            </div> <!-- /.card-header -->
            <div class="card-body">
                <table id="patientTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Admin</th>
                            <th>Appointment ID</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Transaction Time</th>
                            <th>Action</th>
                            <!-- <th>Response</th> -->
                           
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($paymentLogs) && count($paymentLogs) > 0)
                        @foreach($paymentLogs as $log)
                        <tr>
                            <td>{{ $log->id }}</td>
                         @php
                              if(isset($log->patient_id) && !empty($log->patient_id)){
                            $patientNameData = DB::table('patients')->select('name','lastname')->where('id',$log->patient_id)->first();
                                if(isset($patientNameData) && !empty($patientNameData)){
                                 $patientName = $patientNameData->name .' '.$patientNameData->lastname;
                                }
                            
                            }
                            $doctorName = "-";
                            $admin = "-";
                            if(isset($log->dr_id) && !empty($log->dr_id)){
                              $doctorNameData = DB::table('users')->select('name','surname')->where('id',$log->dr_id)->first();
                              if(isset($doctorNameData) && !empty($doctorNameData)){
                                $doctorName = $doctorNameData->name .' '.$doctorNameData->surname;
                              }
                            }else{
                                $admin = 'Admin';
                            }
                          
                            @endphp
                            <td>{{!empty($patientName) ? $patientName : '-'  }}</td>
                            <td>{{ $doctorName }}</td>
                            <td>{{ $admin }} </td>
                            <td>{{ $log->appointment_id }}</td>
                            <td>{{ $log->amount }}</td>
                            <td>{{ ucfirst($log->varStatus) }}</td>
                            <td>{{ $log->transaction_time }}</td>
                            <td>
                                @if(isset($log->dr_id) && !empty($log->dr_id))
                                <button class="btn btn-danger btn-sm refund-btn" data-payment-id="{{ $log->payment_id }}" {{ $log->varStatus === 'refunded' ? 'disabled' : '' }}>
                                    Refund
                                </button>
                                @else
                                --
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="8" class="text-center">No records found</td>
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
    $(document).ready(function () {
        // Initialize DataTable
        $('#patientTable').DataTable({
            "paging": true, // Enable pagination
            "searching": true, // Enable search
            "ordering": true, // Enable sorting
            "info": true, // Enable info text (e.g., "Showing 1 to 10 of 50 entries")
            "lengthChange": true, // Allow changing the number of rows per page
            "autoWidth": false, // Disable automatic column width calculation
            "columnDefs": [
                {
                    "targets": [0], // Disable sorting for the ID column (optional)
                    "orderable": false
                }
            ]
        });

        // Use event delegation to handle click event for dynamically loaded buttons
        $(document).on('click', '.refund-btn', function () {
            const paymentId = $(this).data('payment-id');
            const button = $(this);
            var appUrl = "{{ env('APP_URL') }}";
            if (!confirm('Are you sure you want to refund this payment?')) {
                return;
            }
            // Make an AJAX POST request to refund the payment
            $.ajax({
                url: appUrl + 'admin/refund', // Adjust the API endpoint as needed
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    payment_id: paymentId,
                    amount: null // Pass amount if partial refund is needed
                },
                headers: {
                    'Authorization': 'Bearer {{ csrf_token() }}' // Adjust for your authentication method
                },
                success: function (response) {
                    alert('Refund successful!');
                    button.prop('disabled', true).text('Refunded');
                },
                error: function (xhr, status, error) {
                    alert('Refund failed: ' + (xhr.responseJSON?.message || error));
                }
            });
        });
    });
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
@endsection
