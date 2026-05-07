@extends('admin.admin')
@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Payment Log</h3>
        </div>
        <div class="card-body table-responsive">
            <table id="paymentLogTable" class="table table-bordered table-striped">
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
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentLogs as $index => $payment)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ trim(($payment->patient_name ?? '') . ' ' . ($payment->patient_surname ?? '')) ?: '-' }}</td>
                            <td>{{ trim(($payment->doctor_name ?? '') . ' ' . ($payment->doctor_surname ?? '')) ?: '-' }}</td>
                            <td>{{ auth()->user()->name ?? 'Admin' }}</td>
                            <td>{{ $payment->appointment_id }}</td>
                            <td>₹{{ number_format((float) ($payment->amount ?? 0), 2) }}</td>
                            <td>{{ ucfirst($payment->status ?? '-') }}</td>
                            <td>{{ optional($payment->created_at)->format('d M Y, h:i A') ?? '-' }}</td>
                            <td>
                                @if(($payment->status ?? '') !== 'refunded' && !empty($payment->transaction_id))
                                    <button
                                        class="btn btn-danger btn-sm refund-btn"
                                        data-payment-id="{{ $payment->id }}"
                                        data-transaction-id="{{ $payment->transaction_id }}">
                                        Refund
                                    </button>
                                @else
                                    <span class="badge badge-secondary">N/A</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="refundModal" tabindex="-1" role="dialog" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="refundModalLabel">Confirm Refund</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to refund this payment?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
                <button type="button" class="btn btn-danger" id="confirmRefundBtn">Yes, Refund</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(function () {
        $('#paymentLogTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            pageLength: 10
        });

        let selectedPaymentId = null;
        let selectedTransactionId = null;

        $(document).on('click', '.refund-btn', function () {
            selectedPaymentId = $(this).data('payment-id');
            selectedTransactionId = $(this).data('transaction-id');
            $('#refundModal').modal('show');
        });

        $('#confirmRefundBtn').on('click', function () {
            if (!selectedTransactionId || !selectedPaymentId) {
                return;
            }

            $(this).prop('disabled', true).text('Processing...');

            $.ajax({
                url: '{{ url('/admin/refund') }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    payment_id: selectedPaymentId,
                    payment_method_id: selectedTransactionId,
                },
                success: function (response) {
                    $('#refundModal').modal('hide');
                    alert(response.message || 'Refund successful.');
                    window.location.reload();
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'Refund failed. Please try again.';
                    alert(message);
                },
                complete: function () {
                    $('#confirmRefundBtn').prop('disabled', false).text('Yes, Refund');
                }
            });
        });
    });
</script>
@endsection
