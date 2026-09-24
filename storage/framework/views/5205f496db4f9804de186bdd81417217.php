

<?php $__env->startSection('title', 'Dashboard Analytics'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid analytics-page">
    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
        <div><h2 class="fw-bold mb-1"><i class="fas fa-chart-line text-primary me-2"></i>Dashboard Analytics</h2><p class="text-muted mb-0">Attendance, payroll, and leave performance overview.</p></div>
        <form id="analyticsFilters" class="d-flex align-items-end gap-2" method="GET"><div><label for="analytics-start" class="form-label mb-1">From</label><input id="analytics-start" name="start" type="date" value="<?php echo e($start->toDateString()); ?>" class="form-control"></div><div><label for="analytics-end" class="form-label mb-1">To</label><input id="analytics-end" name="end" type="date" value="<?php echo e($end->toDateString()); ?>" class="form-control"></div><button class="btn btn-primary"><i class="fas fa-filter me-1"></i>Apply</button></form>
    </div>
    <div class="row g-3 mb-4"><div class="col-md-6"><div class="card dashboard-stat-card"><div class="card-body"><div class="text-muted small text-uppercase">Present Records</div><div id="totalPresent" class="fw-bold fs-3"><?php echo e($analytics['attendance']['total_present']); ?></div></div></div></div><div class="col-md-6"><div class="card dashboard-stat-card"><div class="card-body"><div class="text-muted small text-uppercase">Late Records</div><div id="totalLate" class="fw-bold fs-3 text-warning"><?php echo e($analytics['attendance']['total_late']); ?></div></div></div></div></div>
    <div class="row g-4 mb-4"><div class="col-lg-8"><div class="card"><div class="card-header"><i class="fas fa-chart-area text-primary me-2"></i>Attendance Trend</div><div class="card-body" style="height:330px"><canvas id="attendanceChart"></canvas></div></div></div><div class="col-lg-4"><div class="card"><div class="card-header"><i class="fas fa-chart-pie text-primary me-2"></i>Leave Status</div><div class="card-body" style="height:330px"><canvas id="leaveChart"></canvas></div></div></div></div>
    <div class="card"><div class="card-header"><i class="fas fa-money-check-alt text-success me-2"></i>Payroll Totals for Selected Period</div><div class="card-body"><div class="row text-center"><div class="col-md-4"><div class="text-muted">Gross Pay</div><div id="grossPay" class="fw-bold fs-4">₱<?php echo e(number_format($analytics['payroll']['gross'], 2)); ?></div></div><div class="col-md-4"><div class="text-muted">Deductions</div><div id="deductions" class="fw-bold fs-4 text-danger">₱<?php echo e(number_format($analytics['payroll']['deductions'], 2)); ?></div></div><div class="col-md-4"><div class="text-muted">Net Pay</div><div id="netPay" class="fw-bold fs-4 text-success">₱<?php echo e(number_format($analytics['payroll']['net'], 2)); ?></div></div></div></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const analytics = <?php echo json_encode($analytics, 15, 512) ?>;
    const colors = { text: '#94a3b8', grid: 'rgba(148,163,184,.18)' };
    const chartOptions = { responsive: true, maintainAspectRatio: false, animation: false, scales: { x: { ticks: { color: colors.text }, grid: { color: colors.grid } }, y: { beginAtZero: true, ticks: { color: colors.text }, grid: { color: colors.grid } } }, plugins: { legend: { labels: { color: colors.text } } } };
    const attendanceChart = new Chart(document.getElementById('attendanceChart'), { type: 'line', data: { labels: analytics.attendance.labels, datasets: [{ label: 'Present', data: analytics.attendance.present, borderColor: '#20c997', backgroundColor: 'rgba(32,201,151,.12)', fill: true, tension: .35 }, { label: 'Late', data: analytics.attendance.late, borderColor: '#ffc107', backgroundColor: 'transparent', tension: .35 }, { label: 'Absent', data: analytics.attendance.absent, borderColor: '#dc3545', backgroundColor: 'transparent', tension: .35 }] }, options: chartOptions });
    const leaveChart = new Chart(document.getElementById('leaveChart'), { type: 'doughnut', data: { labels: ['Approved', 'Pending', 'Rejected'], datasets: [{ data: [analytics.leaves.approved, analytics.leaves.pending, analytics.leaves.rejected], backgroundColor: ['#20c997', '#ffc107', '#dc3545'] }] }, options: { responsive: true, maintainAspectRatio: false, animation: false, plugins: { legend: { labels: { color: colors.text } } } } });
    setInterval(async function () { 
        const params = new URLSearchParams(new FormData(document.getElementById('analyticsFilters'))); 
        const response = await fetch('<?php echo e(route('analytics.data')); ?>?' + params.toString(), { headers: { Accept: 'application/json' } }); 
        if (!response.ok) return; 
        const data = await response.json(); 
        attendanceChart.data.labels = data.attendance.labels; 
        attendanceChart.data.datasets[0].data = data.attendance.present; 
        attendanceChart.data.datasets[1].data = data.attendance.late; 
        attendanceChart.data.datasets[2].data = data.attendance.absent; 
        attendanceChart.update('none'); 
        leaveChart.data.datasets[0].data = [data.leaves.approved, data.leaves.pending, data.leaves.rejected]; 
        leaveChart.update('none'); 
        requestAnimationFrame(function() {
            document.getElementById('totalPresent').textContent = data.attendance.total_present; 
            document.getElementById('totalLate').textContent = data.attendance.total_late;
        });
    }, 30000);
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\mtcgs-main_09-06-26\mtcgs-ems\resources\views\analytics\index.blade.php ENDPATH**/ ?>