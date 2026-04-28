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
        <div class="card mb-4 pending-doctors-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Doctors</h3>
                <small class="text-muted">Scroll horizontally to view all columns</small>
            </div>
            <div class="card-body">
                <table id="doctorsTable" class="table table-bordered table-hover pending-doctors-table">
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
                        @php
                            $modalData = [];
                        @endphp
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
                                <img src="{{ config('app.url').'api/docterprofile/'.$data->varProfile }}" alt="{{ $data->name }}" width="64" height="64" class="doctor-avatar" />
                                @else
                                <span class="text-muted">N/A</span>
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
                            $modalData = [
                                'id' => $data->id,
                                'name' => $data->name,
                                'email' => $data->email,
                                'surname' => $data->surname,
                                'category' => optional($data->categoryRel)->title ?? '-',
                                'country' => optional($data->countryRel)->countryname ?? '-',
                                'gmc_registration_no' => $data->gmc_registration_no,
                                'indemnity_insurance_provider' => $data->indemnity_insurance_provider,
                                'policy_no' => $data->policy_no,
                                'india_registration_no' => $data->india_registration_no,
                                'dha_reg' => $data->dha_reg,
                                'reg_no' => $data->reg_no,
                                'chrSmartcard' => $data->chrSmartcard,
                                'varSpeciality' => $data->varSpeciality,
                                'varExperience' => $data->varExperience,
                                'varPostGraduation' => $data->varPostGraduation,
                                'varPostGraduationYear' => $data->varPostGraduationYear,
                                'varGraduation' => $data->varGraduation,
                                'varGraduationYear' => $data->varGraduationYear,
                                'chrApproval' => $data->chrApproval,
                            ];
                            @endphp
                            <td class="text-center">{{ $issetup }}</td>
                            <td>{{ number_format($data->total_payment, 2) }}</td>
                            <td id="remainingPayment_{{ $data->id }}">{{ ($data->recent_payment_amount >= 0) ? number_format($data->recent_payment_amount, 2) : '+'.number_format($data->recent_payment_amount, 2)}}</td>
                            {{--<td>
                                <button class="btn btn-warning" onclick="openUpdatePaymentModal({{ $data->id }}, {{ $data->remaining_payment }})">Update Payment</button>
                            </td> --}}
                          <td>
                                <div class="action-buttons">
                                    <button
                                        type="button"
                                        class="btn btn-outline-info"
                                        data-toggle="modal"
                                        data-target="#userModal"
                                        onclick='openModal(@json($modalData))'
                                    >View Details</button>
                                    <button type="button" class="btn btn-outline-danger" onclick="deleteDoctor({{ $data->id }})">Delete</button>
                                </div>
                            </td>
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
</div> <!-- /.row -->
<!-- Modal Structure -->
<div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">Doctor Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="modal-details-grid">
                    <div><div class="detail-label">Name</div><div class="detail-value" id="modalName">-</div></div>
                    <div><div class="detail-label">Email</div><div class="detail-value" id="modalEmail">-</div></div>
                    <div><div class="detail-label">Surname</div><div class="detail-value" id="modalSurname">-</div></div>
                    <div><div class="detail-label">Category</div><div class="detail-value" id="modalCategory">-</div></div>
                    <div><div class="detail-label">Country</div><div class="detail-value" id="modalCountry">-</div></div>
                    <div><div class="detail-label">GMC Registration No</div><div class="detail-value" id="modalGMCRegistrationNo">-</div></div>
                    <div><div class="detail-label">Indemnity Insurance Provider</div><div class="detail-value" id="modalIndemnityInsuranceProvider">-</div></div>
                    <div><div class="detail-label">Policy No</div><div class="detail-value" id="modalPolicyNo">-</div></div>
                    <div><div class="detail-label">India Registration No</div><div class="detail-value" id="modalIndiaRegistrationNo">-</div></div>
                    <div><div class="detail-label">DHA Reg</div><div class="detail-value" id="modalDhaReg">-</div></div>
                    <div><div class="detail-label">Reg No</div><div class="detail-value" id="modalRegNo">-</div></div>
                    <div><div class="detail-label">Smartcard</div><div class="detail-value" id="modalChrSmartcard">-</div></div>
                    <div><div class="detail-label">Speciality</div><div class="detail-value" id="modalSpeciality">-</div></div>
                    <div><div class="detail-label">Experience</div><div class="detail-value" id="modalExperience">-</div></div>
                    <div><div class="detail-label">Post Graduation</div><div class="detail-value" id="modalPostGraduation">-</div></div>
                    <div><div class="detail-label">Post Graduation Year</div><div class="detail-value" id="modalPostGraduationYear">-</div></div>
                    <div><div class="detail-label">Graduation</div><div class="detail-value" id="modalGraduation">-</div></div>
                    <div><div class="detail-label">Graduation Year</div><div class="detail-value" id="modalGraduationYear">-</div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="chrapproval" onclick="Aprroval()">Approve Doctor</button>
                <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
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
            "responsive": false,
            "scrollX": true,
            "columnDefs": [
                {
                    "targets": [9],
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
