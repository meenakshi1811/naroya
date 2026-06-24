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

    .bank-detail-btn {
        min-width: 115px;
        font-weight: 600;
    }

    .bank-detail-modal-content {
        border: none;
        border-radius: 12px;
        box-shadow: 0 15px 45px rgba(28, 39, 60, 0.14);
    }

    .bank-detail-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .bank-detail-grid > div {
        background: #f8f9fb;
        border: 1px solid #e7ebf2;
        border-radius: 10px;
        padding: 10px 12px;
    }

    @media (min-width: 768px) {
        .bank-detail-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
</style>

<div class="container-fluid">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-12">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <h4 class="card-title mb-0">Monthly Doctor Payout Overview</h4>
                        <form method="GET" action="{{ isset($selectedDoctor) ? route('admin.payment-ledger.doctor', $selectedDoctor->id) : route('admin.payment-ledger') }}" class="d-flex gap-2 align-items-center">
                            <select name="year" class="form-control form-control-sm" onchange="this.form.submit()">
                                @foreach($yearOptions as $yearOption)
                                    <option value="{{ $yearOption }}" {{ (int) $selectedYear === (int) $yearOption ? 'selected' : '' }}>{{ $yearOption }}</option>
                                @endforeach
                            </select>
                            <select name="month" class="form-control form-control-sm" onchange="this.form.submit()">
                                @foreach($monthOptions as $monthNumber => $monthLabel)
                                    <option value="{{ $monthNumber }}" {{ (int) $selectedMonth === (int) $monthNumber ? 'selected' : '' }}>{{ $monthLabel }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Profile</th>
                                <th>Doctor</th>
                                <th>Email</th>
                                <th>Bank Details</th>
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
                                @php
                                    $bankModalData = [
                                        'bank_name' => $summary['bank_name'],
                                        'account_holder_name' => $summary['account_holder_name'],
                                        'account_type' => $summary['account_type'],
                                        'account_number' => $summary['account_number'],
                                        'ifsc_code' => $summary['ifsc_code'],
                                    ];
                                @endphp
                                <tr>
                                    <td><span class="badge badge-warning text-dark">{{ $summary['month_label'] }}</span></td>
                                    <td>
                                        @if(!empty($summary['doctor_profile']))
                                            <img src="{{ config('app.url').'api/docterprofile/'.$summary['doctor_profile'] }}" alt="{{ $summary['doctor_name'] }}" width="64" height="64" class="doctor-avatar" />
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $summary['doctor_name'] }}</td>
                                    <td>{{ $summary['doctor_email'] }}</td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-sm bank-detail-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#bankDetailModal"
                                            onclick='openBankModal(@json($bankModalData))'
                                        >View Details</button>
                                    </td>
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
                                            @if((int) ($summary['monthly_payout'] ?? 0) === 1)
                                                <button type="button" class="btn btn-sm btn-secondary" disabled>
                                                    Paid
                                                </button>
                                            @else
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-success mark-paid-btn"
                                                    data-month-label="{{ $summary['month_label'] }}">
                                                    Mark as Paid
                                                </button>
                                            @endif
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="text-center">No monthly payout records found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="bankDetailModal" tabindex="-1" role="dialog" aria-labelledby="bankDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content bank-detail-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bankDetailModalLabel">Doctor Bank Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="bank-detail-grid">
                    <div><div class="detail-label">Bank Name</div><div class="detail-value" id="bankModalBankName">-</div></div>
                    <div><div class="detail-label">Account Holder Name</div><div class="detail-value" id="bankModalAccountHolderName">-</div></div>
                    <div><div class="detail-label">Account Type</div><div class="detail-value" id="bankModalAccountType">-</div></div>
                    <div><div class="detail-label">Account Number</div><div class="detail-value" id="bankModalAccountNumber">-</div></div>
                    <div><div class="detail-label">IFSC Code</div><div class="detail-value" id="bankModalIfscCode">-</div></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
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
    function openBankModal(data) {
        document.getElementById('bankModalBankName').innerText = data.bank_name || '-';
        document.getElementById('bankModalAccountHolderName').innerText = data.account_holder_name || '-';
        document.getElementById('bankModalAccountType').innerText = data.account_type || '-';
        document.getElementById('bankModalAccountNumber').innerText = data.account_number || '-';
        document.getElementById('bankModalIfscCode').innerText = data.ifsc_code || '-';
    }

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
