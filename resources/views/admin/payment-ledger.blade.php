@extends('admin.admin')
@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
<style>
    #markPaidModal .modal-content {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 14px 40px rgba(0, 0, 0, 0.18);
    }

    #markPaidModal .modal-header,
    #markPaidModal .modal-footer {
        border: 0;
    }
</style>

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
                                <th>Doctor</th>
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
                                    <td><span class="badge badge-warning text-dark">{{ $summary['month_label'] }}</span></td>
                                    <td>{{ $summary['doctor_name'] }}</td>
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
                                        <form action="{{ route('admin.payment-ledger.mark-monthly-paid') }}" method="POST" class="mark-paid-form">
                                            @csrf
                                            <input type="hidden" name="month_key" value="{{ $summary['month_key'] }}">
                                            <input type="hidden" name="doctor_id" value="{{ $summary['doctor_id'] }}">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-success mark-paid-btn"
                                                data-month-label="{{ $summary['month_label'] }}">
                                                Mark as Paid
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center">No monthly payout records found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="markPaidModal" tabindex="-1" role="dialog" aria-labelledby="markPaidModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="markPaidModalLabel">Confirm Monthly Payout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="markPaidModalBody">
                Are you sure you want to mark this month as paid?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                <button type="button" class="btn btn-success" id="confirmMarkPaidBtn">Yes, Mark as Paid</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(function () {
        let selectedForm = null;
        const markPaidModalElement = document.getElementById('markPaidModal');
        const markPaidModal = new bootstrap.Modal(markPaidModalElement);

        markPaidModalElement.addEventListener('hidden.bs.modal', function () {
            selectedForm = null;
            $('#confirmMarkPaidBtn').prop('disabled', false).text('Yes, Mark as Paid');
        });

        $(document).on('click', '.mark-paid-btn', function () {
            selectedForm = $(this).closest('form');
            const monthLabel = $(this).data('month-label');
            $('#markPaidModalBody').text(`Are you sure you want to mark ${monthLabel} payout as paid?`);
            markPaidModal.show();
        });

        $('#confirmMarkPaidBtn').on('click', function () {
            if (!selectedForm) {
                return;
            }

            $(this).prop('disabled', true).text('Processing...');
            selectedForm.submit();
        });
    });
</script>
@endsection
