@extends('admin.admin')
@section('content')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">

<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Payment Ledger</h3>
            <small class="text-muted">Static preview table</small>
        </div>
        <div class="card-body table-responsive">
            <table id="paymentLedgerTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Doctor</th>
                        <th>No. of appointments</th>
                        <th>Appointment fees (INR)</th>
                        <th>Noraya Fees (INR)</th>
                        <th>Refunds</th>
                        <th>Final Payout (INR)</th>
                        <th>Transfer Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Meenakshi Nanta</td>
                        <td>80</td>
                        <td>24000</td>
                        <td>7200</td>
                        <td>3</td>
                        <td>16800</td>
                        <td><button class="btn btn-success btn-sm">Mark as complete</button></td>
                    </tr>
                    <tr>
                        <td>Mudra Vadiya</td>
                        <td>65</td>
                        <td>13000</td>
                        <td>3900</td>
                        <td>0</td>
                        <td>9100</td>
                        <td><button class="btn btn-success btn-sm">Mark as complete</button></td>
                    </tr>
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
