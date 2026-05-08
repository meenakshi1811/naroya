@extends('admin.admin')
@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                Payment Ledger
                @if(!empty($selectedDoctor))
                    - {{ trim(($selectedDoctor->name ?? '') . ' ' . ($selectedDoctor->surname ?? '')) }}
                @endif
            </h3>
        </div>
        <div class="card-body table-responsive">
            <table id="paymentLedgerTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient ID</th>
                        <th>Doctor ID</th>
                        <th>Appointment ID</th>
                        <th>Payment ID</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Transaction Time</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentLogs as $index => $payment)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $payment->patient_id ?? '-' }}</td>
                            <td>{{ $payment->dr_id ?? '-' }}</td>
                            <td>{{ $payment->appointment_id ?? '-' }}</td>
                            <td>{{ $payment->payment_id ?? '-' }}</td>
                            <td>₹{{ number_format((float) ($payment->amount ?? 0), 2) }}</td>
                            <td>{{ ucfirst($payment->varStatus ?? '-') }}</td>
                            <td>{{ !empty($payment->transaction_time) ? \Carbon\Carbon::parse($payment->transaction_time)->format('d M Y, h:i A') : '-' }}</td>
                            <td>{{ $payment->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No ledger records found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(function () {
        $('#paymentLedgerTable').DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            pageLength: 10
        });
    });
</script>
@endsection
