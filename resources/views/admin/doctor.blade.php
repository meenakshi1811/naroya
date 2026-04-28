@extends('admin.admin')
@section('content')
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif
<!-- Bootstrap CSS -->
<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<!-- DataTables CSS -->
<link href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- jQuery and Bootstrap JS -->
<div class="row">
    <div class="col-md-12">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Doctors</h3>
            </div>
            <div class="card-body">
                <table id="doctorsTable" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Category</th>
                            <th>Country</th>
                            <th>Profile</th>
                            <th>Bank Setup</th>
                            <th>Total Payment</th>
                            <th>Recent Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($doctors) && count($doctors) > 0)
                        @foreach($doctors as $key=>$data)
                        <tr class="align-middle">
                            <td>{{ $data->id }}</td>
                            <td>{{ $data->name . ' '. $data->surname }}</td>
                            <td>{{ $data->email }}</td>
                            <td>{{ $data->categoryRel->title ?? '-' }}</td>
                            <td>{{ $data->countryRel->countryname ?? '-' }}</td>
                            <td>
                                @if(!empty($data->varProfile))
                                <img src="{{ config('app.url').'api/docterprofile/'.$data->varProfile }}" alt="{{ $data->name }}" height="100px" width="100px" />
                                @else
                                N/A
                                @endif
                            </td>
                            @php
                            if($data->isPaymentFlowRegistered == 1){
                                $issetup = 'Yes';
                            }else{
                                $issetup = 'No';
                            }
                             if($data->total_payment < 0){
                                $data->total_payment = 0.00;
                            }
                            if ($data->remaining_payment < 0) {
                                $remainingPaymentss = abs($data->remaining_payment);
                                $remainingPayment = '+' . $remainingPaymentss;  
                            }
                            @endphp
                            <td class="text-center">{{ $issetup }}</td>
                            <td>{{ number_format($data->total_payment, 2) }}</td>
                            <td id="remainingPayment_{{ $data->id }}">{{ ($data->recent_payment_amount >= 0) ? number_format($data->recent_payment_amount, 2) : '+'.number_format($data->recent_payment_amount, 2)}}</td>
                            {{--<td>
                                <button class="btn btn-warning" onclick="openUpdatePaymentModal({{ $data->id }}, {{ $data->remaining_payment }})">Update Payment</button>
                            </td> --}}
                          <td class="d-flex gap-2">
                                <button
                                    type="button"
                                    class="btn btn-info"
                                    data-toggle="modal"
                                    data-target="#userModal"
                                    onclick='openModal(@json([
                                        "id" => $data->id,
                                        "name" => $data->name,
                                        "email" => $data->email,
                                        "surname" => $data->surname,
                                        "category" => $data->categoryRel->title ?? "-",
                                        "country" => $data->countryRel->countryname ?? "-",
                                        "gmc_registration_no" => $data->gmc_registration_no,
                                        "indemnity_insurance_provider" => $data->indemnity_insurance_provider,
                                        "policy_no" => $data->policy_no,
                                        "india_registration_no" => $data->india_registration_no,
                                        "dha_reg" => $data->dha_reg,
                                        "reg_no" => $data->reg_no,
                                        "chrSmartcard" => $data->chrSmartcard,
                                        "varSpeciality" => $data->varSpeciality,
                                        "varExperience" => $data->varExperience,
                                        "varPostGraduation" => $data->varPostGraduation,
                                        "varPostGraduationYear" => $data->varPostGraduationYear,
                                        "varGraduation" => $data->varGraduation,
                                        "varGraduationYear" => $data->varGraduationYear,
                                        "chrApproval" => $data->chrApproval,
                                    ]))'
                                >View Details</button>
                                <button type="button" class="btn btn-danger" onclick="deleteDoctor({{ $data->id }})">Delete</button>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td colspan="7" class="text-center">No records found</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div> <!-- /.card-body -->
        </div> <!-- /.card -->
    </div> <!-- /.col -->
</div> <!-- /.row -->
<!-- Modal Structure -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">User Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div>
                    <strong>Name:</strong> <span id="modalName"></span>
                </div>
                <div>
                    <strong>Email:</strong> <span id="modalEmail"></span>
                </div>
                <div>
                    <strong>Surname:</strong> <span id="modalSurname"></span>
                </div>
                <div>
                    <strong>Category:</strong> <span id="modalCategory"></span>
                </div>
                <div>
                    <strong>Country:</strong> <span id="modalCountry"></span>
                </div>
                <div>
                    <strong>GMC Registration No:</strong> <span id="modalGMCRegistrationNo"></span>
                </div>
                <div>
                    <strong>Indemnity Insurance Provider:</strong> <span id="modalIndemnityInsuranceProvider"></span>
                </div>
                <div>
                    <strong>Policy No:</strong> <span id="modalPolicyNo"></span>
                </div>
                <div>
                    <strong>India Registration No:</strong> <span id="modalIndiaRegistrationNo"></span>
                </div>
                <div>
                    <strong>DHA Reg:</strong> <span id="modalDhaReg"></span>
                </div>
                <div>
                    <strong>Reg No:</strong> <span id="modalRegNo"></span>
                </div>
                <div>
                    <strong>Smartcard:</strong> <span id="modalChrSmartcard"></span>
                </div>
                <div>
                    <strong>Speciality:</strong> <span id="modalSpeciality"></span>
                </div>
                <div>
                    <strong>Experience:</strong> <span id="modalExperience"></span>
                </div>
                <div>
                    <strong>Post Graduation:</strong> <span id="modalPostGraduation"></span>
                </div>
                <div>
                    <strong>Post Graduation Year:</strong> <span id="modalPostGraduationYear"></span>
                </div>
                <div>
                    <strong>Graduation:</strong> <span id="modalGraduation"></span>
                </div>
                <div>
                    <strong>Graduation Year:</strong> <span id="modalGraduationYear"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="chrapproval" onclick="Aprroval()">Approved</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Payment Modal -->
<div class="modal fade" id="updatePaymentModal" tabindex="-1" role="dialog" aria-labelledby="updatePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updatePaymentModalLabel">Update Payment</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="doctor_id">
                <div class="form-group">
                    <label for="paymentAmount">Enter Payment Amount</label>
                    <input type="number" class="form-control" id="paymentAmount" placeholder="Enter amount">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="updatePaymentBtn">Update Payment</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var id;
  
    function openModal(data) {
        id = data.id;
        document.getElementById('modalName').innerText = data.name || '-';
        document.getElementById('modalEmail').innerText = data.email || '-';
        document.getElementById('modalSurname').innerText = data.surname || '-';
        document.getElementById('modalCategory').innerText = data.category || '-';
        document.getElementById('modalCountry').innerText = data.country || '-';
        document.getElementById('modalGMCRegistrationNo').innerText = data.gmc_registration_no || '-';
        document.getElementById('modalIndemnityInsuranceProvider').innerText = data.indemnity_insurance_provider || '-';
        document.getElementById('modalPolicyNo').innerText = data.policy_no || '-';
        document.getElementById('modalIndiaRegistrationNo').innerText = data.india_registration_no || '-';
        document.getElementById('modalDhaReg').innerText = data.dha_reg || '-';
        document.getElementById('modalRegNo').innerText = data.reg_no || '-';
        document.getElementById('modalChrSmartcard').innerText = data.chrSmartcard || '-';
        document.getElementById('modalSpeciality').innerText = data.varSpeciality || '-';
        document.getElementById('modalExperience').innerText = data.varExperience || '-';
        document.getElementById('modalPostGraduation').innerText = data.varPostGraduation || '-';
        document.getElementById('modalPostGraduationYear').innerText = data.varPostGraduationYear || '-';
        document.getElementById('modalGraduation').innerText = data.varGraduation || '-';
        document.getElementById('modalGraduationYear').innerText = data.varGraduationYear || '-';
        if (data.chrApproval && data.chrApproval == 'Y') {
            document.getElementById('chrapproval').style.display = "none";
        } else {
            document.getElementById('chrapproval').style.display = "block";
        }
    }

    function Aprroval() {
        var approval = 'Y';
        var appUrl = "{{ env('APP_URL') }}";
        $.ajax({
            url: appUrl + 'admin/doctor',
            type: 'POST',
            data: {
                "_token": "{{ csrf_token() }}",
                id: id,
                approval: approval
            },
            success: function(response) {
                var modal = document.getElementById('userModal');
                if (modal) {
                    modal.classList.remove('show'); // Hide modal
                    modal.style.display = 'none'; // Set display to none
                    document.body.classList.remove('modal-open'); // Remove open class
                    var modalBackdrop = document.querySelector('.modal-backdrop'); // Find backdrop
                    if (modalBackdrop) {
                        modalBackdrop.remove(); // Remove backdrop
                    }
                }
                // Reload the page
                location.reload(); // Reload the page
            }
        });
    }
     function deleteDoctor(id) {
    if (confirm('Are you sure you want to delete this doctor and their Stripe account?')) {
        $.ajax({
            url: '{{ url("admin/doctor/delete") }}/' + id,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                alert(response.message);
                location.reload();
            },
            error: function(xhr) {
                alert('Failed to delete: ' + xhr.responseJSON.message);
            }
        });
    }
}
</script>
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize DataTable
        $('#doctorsTable').DataTable({
            "paging": true,         // Enable pagination
            "searching": true,      // Enable search
            "ordering": true,       // Enable sorting
            "info": true,           // Enable info text (e.g., "Showing 1 to 10 of 50 entries")
            "lengthChange": true,   // Allow changing the number of rows per page
            "autoWidth": false,     // Disable automatic column width calculation
            "columnDefs": [
                {
                    "targets": [0],  // Disable sorting for the ID column (optional)
                    "orderable": false
                }
            ]
        });
    });

    function openUpdatePaymentModal(doctorId, remainingPayment) {
    $('#doctor_id').val(doctorId);  // Set doctor ID
    $('#paymentAmount').val('');  // Clear the payment amount field
    $('#updatePaymentModal').modal('show'); // Open the modal
}

// Update Payment using AJAX
$('#updatePaymentBtn').on('click', function() {
    var doctorId = $('#doctor_id').val();
    var appUrl = "{{ env('APP_URL') }}";
    var paymentAmount = parseFloat($('#paymentAmount').val());

    if (!paymentAmount || paymentAmount <= 0) {
        alert('Please enter a valid payment amount');
        return;
    }

    // Send AJAX request to update remaining payment
    $.ajax({
        url: appUrl +'admin/update-payment',  // Your update payment route
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            doctor_id: doctorId,
            payment_amount: paymentAmount
        },
        success: function(response) {
            if (response.success) {
                // Update the remaining payment in the table
                var updatedRemainingPayment = response.updated_remaining_payment;
                if (updatedRemainingPayment < 0) {
                    updatedRemainingPayment = Math.abs(updatedRemainingPayment);
                }
                $('#remainingPayment_' + doctorId).text(updatedRemainingPayment.toFixed(2));

                // Optionally, add a "+" sign if the payment is more than the remaining
                if (updatedRemainingPayment < 0) {
                    $('#remainingPayment_' + doctorId).prepend('+');
                }

                // Close the modal
                $('#updatePaymentModal').modal('hide');
            } else {
                alert('Error updating payment');
                 // Close the modal
                 $('#updatePaymentModal').modal('hide');
            }
        }
       
    });
});

</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
@endsection
