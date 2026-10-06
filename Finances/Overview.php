<?php
// Finance/Overview.php — Overview dashboard
session_start();
require_once '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

$staff_type = strtolower($_SESSION['staff_type'] ?? 'staff');
if (!in_array($staff_type, ['admin','manager','superadmin'], true)) {
    header('Location: ../index.php');
    exit();
}

/* Totals */
$total_invoiced   = (float)$conn->query("SELECT COALESCE(SUM(total),0) FROM invoices WHERE status <> 'cancelled'")->fetchColumn();
$total_paid       = (float)$conn->query("SELECT COALESCE(SUM(amount_paid),0) FROM invoices WHERE status <> 'cancelled'")->fetchColumn();
$total_outstanding= $total_invoiced - $total_paid;
$total_expenses   = (float)$conn->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();
$total_salaries   = (float)$conn->query("SELECT COALESCE(SUM(net_salary),0) FROM salaries WHERE status = 'paid'")->fetchColumn();

$net_position = $total_paid - $total_expenses - $total_salaries;

/* Recent activity */
$recent_invoices = $conn->query("
    SELECT id, invoice_no, client_name, total, status, due_date
    FROM invoices ORDER BY created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$recent_payments = $conn->query("
    SELECT id, client_name, amount, method, paid_at
    FROM payments ORDER BY paid_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* Monthly income (last 6 months) */
$monthly = $conn->query("
    SELECT DATE_FORMAT(paid_at,'%Y-%m') AS ym, SUM(amount) AS total
    FROM payments
    WHERE paid_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY ym ORDER BY ym
")->fetchAll(PDO::FETCH_ASSOC);
$chart_labels = array_column($monthly, 'ym');
$chart_data   = array_map('floatval', array_column($monthly, 'total'));

function money($n) { return 'R ' . number_format((float)$n, 2); }
function time_ago($dt) {
    $d = time() - strtotime($dt);
    if ($d < 60) return $d.'s ago';
    if ($d < 3600) return floor($d/60).'m ago';
    if ($d < 86400) return floor($d/3600).'h ago';
    return floor($d/86400).'d ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Finance Overview — Marlani Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<script src="../vendor/chart.js/Chart.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Nunito,sans-serif;background:#f8f9fc;display:flex;min-height:100vh;color:#5a5c69}
#sidebar-container{display:flex;flex-shrink:0;height:100vh;position:sticky;top:0;align-self:flex-start;z-index:100}
.main{flex:1;padding:2rem;min-width:0}
h1{font-size:1.5rem;color:#2d3748;margin-bottom:1.5rem}
h1 i{color:#003986;margin-right:.5rem}

.tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.tab{padding:.55rem 1.1rem;background:#fff;border-radius:8px;font-weight:700;font-size:.88rem;color:#5a5c69;text-decoration:none;border:1.5px solid #e3e6f0;display:inline-flex;align-items:center;gap:.4rem;transition:all .15s}
.tab:hover{border-color:#003986;color:#003986}
.tab.active{background:#003986;color:#fff;border-color:#003986}

.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem;margin-bottom:1.5rem}
.stat{background:#fff;border-radius:12px;padding:1.25rem 1.5rem;box-shadow:0 4px 20px rgba(0,0,0,.06);border-left:4px solid #4e73df}
.stat.green{border-left-color:#16a34a}
.stat.red{border-left-color:#dc2626}
.stat.orange{border-left-color:#f59e0b}
.stat.purple{border-left-color:#7c3aed}
.stat .label{font-size:.75rem;font-weight:700;text-transform:uppercase;color:#a0aec0;letter-spacing:.5px;margin-bottom:.4rem}
.stat .value{font-size:1.5rem;font-weight:800;color:#2d3748}
.stat .sub{font-size:.78rem;color:#a0aec0;margin-top:.25rem}

.card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);padding:1.5rem;margin-bottom:1.5rem}
.card h2{font-size:1rem;color:#2d3748;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.card h2 i{color:#003986}

.table{width:100%;border-collapse:collapse}
.table thead{background:#fafbff}
.table th{text-align:left;padding:.75rem;font-size:.72rem;color:#a0aec0;text-transform:uppercase;letter-spacing:.5px;font-weight:700}
.table td{padding:.8rem .75rem;border-bottom:1px solid #eef1f7;font-size:.88rem}
.table tr:last-child td{border-bottom:none}
.table tr:hover{background:#fafbff}

.badge{display:inline-block;padding:.22rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-paid{background:#dcfce7;color:#16a34a}
.badge-sent{background:#dbeafe;color:#2563eb}
.badge-draft{background:#f3f4f6;color:#6b7280}
.badge-partial{background:#fef3c7;color:#d97706}
.badge-overdue{background:#fee2e2;color:#dc2626}
.badge-cancelled{background:#f3f4f6;color:#6b7280}
.badge-cash{background:#ecfdf5;color:#059669}
.badge-bank{background:#eff6ff;color:#2563eb}
.badge-card{background:#fdf2f8;color:#db2777}
.badge-mobile{background:#fef3c7;color:#d97706}
.badge-other{background:#f3f4f6;color:#6b7280}

.chart-wrap{position:relative;height:280px}

@media (max-width:600px){ .main{padding:1rem} }
</style>
</head>
<body>

<div id="sidebar-container"></div>

<div class="main">
    <h1><i class="fas fa-chart-line"></i> Finance Overview</h1>

    <?php include __DIR__ . '/_nav.php'; ?>

    <div class="cards">
        <div class="stat">
            <div class="label">Total Invoiced</div>
            <div class="value"><?= money($total_invoiced) ?></div>
        </div>
        <div class="stat green">
            <div class="label">Total Received</div>
            <div class="value"><?= money($total_paid) ?></div>
        </div>
        <div class="stat red">
            <div class="label">Outstanding</div>
            <div class="value"><?= money($total_outstanding) ?></div>
        </div>
        <div class="stat orange">
            <div class="label">Expenses</div>
            <div class="value"><?= money($total_expenses) ?></div>
        </div>
        <div class="stat purple">
            <div class="label">Salaries Paid</div>
            <div class="value"><?= money($total_salaries) ?></div>
        </div>
        <div class="stat <?= $net_position >= 0 ? 'green' : 'red' ?>">
            <div class="label">Net Position</div>
            <div class="value"><?= money($net_position) ?></div>
            <div class="sub">Received − Expenses − Salaries</div>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-chart-area"></i> Income — Last 6 Months</h2>
        <div class="chart-wrap"><canvas id="incomeChart"></canvas></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
        <div class="card">
            <h2><i class="fas fa-file-invoice"></i> Recent Invoices</h2>
            <?php if (empty($recent_invoices)): ?>
                <p style="color:#a0aec0;font-size:.9rem">No invoices yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Invoice</th><th>Client</th><th>Total</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_invoices as $inv): ?>
                        <tr>
                            <td style="font-weight:700"><?= htmlspecialchars($inv['invoice_no']) ?></td>
                            <td><?= htmlspecialchars($inv['client_name']) ?></td>
                            <td><?= money($inv['total']) ?></td>
                            <td><span class="badge badge-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2><i class="fas fa-money-bill-wave"></i> Recent Payments</h2>
            <?php if (empty($recent_payments)): ?>
                <p style="color:#a0aec0;font-size:.9rem">No payments yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead><tr><th>Client</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['client_name'] ?? 'N/A') ?></td>
                            <td style="font-weight:700;color:#16a34a"><?= money($p['amount']) ?></td>
                            <td><span class="badge badge-<?= $p['method'] ?>"><?= ucfirst($p['method']) ?></span></td>
                            <td><?= time_ago($p['paid_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script>
$("#sidebar-container").load("../sidebar.php");

var ctx = document.getElementById('incomeChart');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chart_labels ?: ['No data']) ?>,
            datasets: [{
                label: 'Income',
                data: <?= json_encode($chart_data ?: [0]) ?>,
                borderColor: '#003986',
                backgroundColor: 'rgba(0,57,134,.1)',
                fill: true,
                tension: .3,
                pointRadius: 4,
                pointBackgroundColor: '#003986'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: { display: false },
            scales: {
                yAxes: [{ ticks: { beginAtZero: true } }]
            }
        }
    });
}
</script>
</body>
</html>