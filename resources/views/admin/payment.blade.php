@extends('admin.admin')
@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<style>
    .ledger-hero {
        background: linear-gradient(135deg, #0d6efd 0%, #5a8dee 100%);
        color: #fff;
        border-radius: 14px;
        padding: 1.5rem;
    }
    .metric-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 1rem;
        background: #fff;
        box-shadow: 0 6px 18px rgba(16, 24, 40, .06);
        height: 100%;
    }
    .metric-title { color: #6c757d; font-size: .9rem; }
    .metric-value { font-size: 1.5rem; font-weight: 700; color: #1f2d3d; }
    .status-badge { font-size: .78rem; padding: .4rem .65rem; border-radius: 999px; }
    .status-completed { background: #d1fae5; color: #065f46; }
    .status-pending { background: #fff7cd; color: #92400e; }
    .timeline-note {
        border-left: 4px solid #0d6efd;
        background: #f8f9ff;
        padding: 1rem;
        border-radius: 8px;
    }
</style>

<div class="container-fluid">
    <div class="ledger-hero mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap">
            <div>
                <h3 class="mb-2"><i class="fa-solid fa-file-invoice-dollar mr-2"></i>Doctor Monthly Payment Ledger</h3>
                <p class="mb-0">Static prototype: review monthly payment totals, then mark payout status as <strong>Completed</strong> once the manual bank transfer to doctors is done.</p>
            </div>
            <span class="badge badge-light text-primary mt-2 mt-md-0">Manual Payout Workflow</span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="metric-title">Current Month Total</div>
                <div class="metric-value">$12,460.00</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="metric-title">Pending Payout</div>
                <div class="metric-value text-warning">$4,180.00</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="metric-title">Completed This Quarter</div>
                <div class="metric-value text-success">$28,920.00</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="metric-card">
                <div class="metric-title">Months Tracked</div>
                <div class="metric-value">6</div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="card">
                <div class="card-header"><strong>Monthly Payment Trend</strong></div>
                <div class="card-body"><canvas id="monthlyTrendChart" height="110"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header"><strong>Status Distribution</strong></div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="statusDonutChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <strong>Monthly Payout Register</strong>
            <small class="text-muted">Admin updates status after month-end transfer.</small>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover table-bordered mb-0" id="monthlyLedgerTable">
                <thead class="thead-light">
                    <tr>
                        <th>Month</th>
                        <th>Doctors Included</th>
                        <th>Total Amount</th>
                        <th>Transfer Date</th>
                        <th>Status</th>
                        <th>Admin Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>January 2026</td><td>28</td><td>$4,320.00</td><td>2026-02-01</td>
                        <td><span class="status-badge status-completed">Completed</span></td>
                        <td><button class="btn btn-outline-secondary btn-sm" disabled>Locked</button></td>
                    </tr>
                    <tr>
                        <td>February 2026</td><td>30</td><td>$4,870.00</td><td>2026-03-01</td>
                        <td><span class="status-badge status-completed">Completed</span></td>
                        <td><button class="btn btn-outline-secondary btn-sm" disabled>Locked</button></td>
                    </tr>
                    <tr>
                        <td>March 2026</td><td>31</td><td>$5,120.00</td><td>2026-04-01</td>
                        <td><span class="status-badge status-completed">Completed</span></td>
                        <td><button class="btn btn-outline-secondary btn-sm" disabled>Locked</button></td>
                    </tr>
                    <tr>
                        <td>April 2026</td><td>33</td><td>$4,180.00</td><td>Not yet transferred</td>
                        <td><span class="status-badge status-pending">Pending</span></td>
                        <td><button class="btn btn-primary btn-sm mark-complete-btn">Mark as Completed</button></td>
                    </tr>
                    <tr>
                        <td>May 2026</td><td>32</td><td>$3,960.00</td><td>Awaiting month-end</td>
                        <td><span class="status-badge status-pending">Pending</span></td>
                        <td><button class="btn btn-primary btn-sm mark-complete-btn">Mark as Completed</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="timeline-note mt-4">
        <h6 class="mb-2"><i class="fa-solid fa-circle-info mr-2"></i>How admin should use this page</h6>
        <ol class="mb-0 pl-3">
            <li>Check each month’s total payout amount for all doctors.</li>
            <li>After manual bank transfer is completed, click <strong>Mark as Completed</strong>.</li>
            <li>Keep any unpaid month in <strong>Pending</strong> status for clear tracking.</li>
        </ol>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const monthlyTrendCtx = document.getElementById('monthlyTrendChart');
    new Chart(monthlyTrendCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Monthly Total (USD)',
                data: [4320, 4870, 5120, 4180, 3960, 4460],
                backgroundColor: '#0d6efd',
                borderRadius: 6
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });

    const statusDonutCtx = document.getElementById('statusDonutChart');
    new Chart(statusDonutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Completed', 'Pending'],
            datasets: [{ data: [3, 2], backgroundColor: ['#16a34a', '#f59e0b'] }]
        },
        options: { responsive: true, cutout: '60%' }
    });

    document.querySelectorAll('.mark-complete-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const row = this.closest('tr');
            const badge = row.querySelector('.status-badge');
            badge.textContent = 'Completed';
            badge.classList.remove('status-pending');
            badge.classList.add('status-completed');
            this.textContent = 'Completed';
            this.classList.remove('btn-primary');
            this.classList.add('btn-outline-secondary');
            this.disabled = true;
        });
    });
</script>
@endsection
