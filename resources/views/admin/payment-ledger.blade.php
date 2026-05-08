@extends('admin.admin')
@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h4 class="card-title mb-0">Monthly Doctor Payout Overview</h4>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>No. of Appointments</th>
                                <th>Transaction Entries</th>
                                <th>Gross Amount (INR)</th>
                                <th>Commission %</th>
                                <th>Commission Amount (INR)</th>
                                <th>Refunds</th>
                                <th>Refund Amount (INR)</th>
                                <th>Final Payout (INR)</th>
                                <th>Completed Transfers</th>
                                <th>Transfer Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthlySummaries as $summary)
                                <tr>
                                    <td><span class="badge badge-primary">{{ $summary['month_label'] }}</span></td>
                                    <td>{{ $summary['appointment_count'] }}</td>
                                    <td>{{ $summary['transaction_count'] }}</td>
                                    <td>₹{{ number_format($summary['gross_amount'], 2) }}</td>
                                    <td>{{ rtrim(rtrim(number_format($summary['commission_percentage'], 2), '0'), '.') }}%</td>
                                    <td>₹{{ number_format($summary['commission_amount'], 2) }}</td>
                                    <td>{{ $summary['refund_count'] }}</td>
                                    <td>₹{{ number_format($summary['refund_amount'], 2) }}</td>
                                    <td><strong>₹{{ number_format($summary['final_payout'], 2) }}</strong></td>
                                    <td>{{ $summary['completed_transfers'] }}</td>
                                    <td>
                                        <form action="{{ route('admin.payment-ledger.mark-monthly-paid') }}" method="POST" onsubmit="return confirm('Mark all entries for {{ $summary['month_label'] }} as paid?');">
                                            @csrf
                                            <input type="hidden" name="month_key" value="{{ $summary['month_key'] }}">
                                            @if(!empty($selectedDoctor))
                                                <input type="hidden" name="doctor_id" value="{{ $selectedDoctor->id }}">
                                            @endif
                                            <button type="submit" class="btn btn-sm btn-success">Mark as Paid</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center">No monthly payout records found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

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
                        <th>Payout Month</th>
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
                            <td>
                                @if(!empty($payment->transaction_time))
                                    <span class="badge badge-secondary">{{ \Carbon\Carbon::parse($payment->transaction_time)->format('F Y') }}</span>
                                @else
                                    <span class="badge badge-light">Unknown</span>
                                @endif
                            </td>
                            <td>{{ ucfirst($payment->varStatus ?? '-') }}</td>
                            <td>{{ !empty($payment->transaction_time) ? \Carbon\Carbon::parse($payment->transaction_time)->format('d M Y, h:i A') : '-' }}</td>
                            <td>{{ $payment->description ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">No ledger records found</td>
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
