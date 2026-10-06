<?php
// Finance/finance_reports.php
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

$from = $_GET['from'] ?? date('Y-m-01', strtotime('-5 months'));
$to   = $_GET['to']   ?? date('Y-m-t');

/* Income by month */
$income = $conn->prepare("
    SELECT DATE_FORMAT(paid_at,'%Y-%m') ym, SUM(amount) total
    FROM payments WHERE paid_at BETWEEN ? AND ?
    GROUP BY ym ORDER BY ym
");
$income->execute([$from . ' 00:00:00', $to . ' 23:59:59']);
$income_rows = $income->fetchAll(PDO::FETCH_ASSOC);

/* Expenses by month */
$exp = $conn->prepare("
    SELECT DATE_FORMAT(spent_at,'%Y-%m') ym, SUM(amount) total
    FROM expenses WHERE spent_at BETWEEN ? AND ?
    GROUP BY ym ORDER BY ym
");
$exp->execute([$from, $to]);
$expense_rows = $exp->fetchAll(PDO::FETCH_ASSOC);

/* Salaries by month */
$sal = $conn->prepare("
    SELECT pay_period ym, SUM(net_salary) total
    FROM salaries WHERE pay_date BETWEEN ? AND ? AND status = 'paid'
    GROUP BY pay_period ORDER BY ym
");
$sal->execute([$from, $to]);
$salary_rows = $sal->fetchAll(PDO::FETCH_ASSOC);

/* Merge into one timeline */
$timeline = [];
foreach ($income_rows as $r) $timeline[$r['ym']]['income']  = (float)$r['total'];
foreach ($expense_rows as $r) $timeline[$r['ym']]['expense'] = (float)$r['total'];
foreach ($salary_rows as $r) $timeline[$r['ym']]['salary']  = (float)$r['total'];
ksort($timeline);

$labels      = array_keys($timeline);
$income_data = array_map(fn($k) => $timeline[$k]['income']  ?? 0, $labels);
$expense_data= array_map(fn($k) => $timeline[$k]['expense'] ?? 0, $labels);
$salary_data = array_map(fn($k) => $timeline[$k]['salary']  ?? 0, $labels);

/* Status summary */
$status_summary = $conn->query("
    SELECT status, COUNT(*) c, SUM(total) t
    FROM invoices GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

function money($n) { return 'R ' . number_format((float)$n, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Finance Reports — Marlani Admin</title>
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
.tab{padding:.55rem 1.1rem;background:#fff;border-radius:8px;font-weight:700;font-size:.88rem;color:#5a5c69;text-decoration:none;border:1.5px solid #e3e6f0;display:inline-flex;align-items:center;gap:.4rem}
.tab.active{background:#003986;color:#fff;border-color:#003986}
.filter-row{background:#fff;border-radius:12px;padding:1rem 1.25rem;box-shadow:0 4px 20px rgba(0,0,0,.06);margin-bottom:1.5rem;display:flex;gap:1rem;align-items:end;flex-wrap:wrap}
.filter-row label{display:block;font-size:.75rem;font-weight:700;text-transform:uppercase;color:#a0aec0;margin-bottom:.3rem}
.filter-row input{padding:.55rem .75rem;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:.9rem}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.25rem;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer;border:none;text-decoration:none;font-family:inherit}
.btn-primary{background:#003986;color:#fff}
.btn-primary:hover{background:#002a66}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);padding:1.5rem;margin-bottom:1.5rem}
.card h2{font-size:1rem;color:#2d3748;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.card h2 i{color:#003986}
.chart-wrap{position:relative;height:320px}
.table{width:100%;border-collapse:collapse}
.table thead{background:#fafbff}
.table th{text-align:left;padding:.75rem;font-size:.72rem;color:#a0aec0;text-transform:uppercase;letter-spacing:.5px;font-weight:700}
.table td{padding:.8rem .75rem;border-bottom:1px solid #eef1f7;font-size:.88rem}
.table tr:last-child td{border-bottom:none}
.badge{display:inline-block;padding:.22rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700}
</style>
</head>
<body>

<div id="sidebar-container"></div>

<div class="main">
    <h1><i class="fas fa-chart-bar"></i> Finance Reports</h1>
    <?php include __DIR__ . '/_nav.php'; ?>

    <form class="filter-row" method="GET">
        <div><label>From</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>"></div>
        <div><label>To</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>"></div>
        <button class="btn btn-primary"><i class="fas fa-filter"></i> Apply</button>
    </form>

    <div class="card">
        <h2><i class="fas fa-chart-bar"></i> Income vs Expenses vs Salaries</h2>
        <div class="chart-wrap"><canvas id="reportChart"></canvas></div>
    </div>

    <div class="card">
        <h2><i class="fas fa-file-invoice"></i> Invoice Status Summary</h2>
        <table class="table">
            <thead><tr><th>Status</th><th>Count</th><th>Total Value</th></tr></thead>
            <tbody>
            <?php foreach ($status_summary as $s): ?>
                <tr>
                    <td><span class="badge badge-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span></td>
                    <td><?= (int)$s['c'] ?></td>
                    <td style="font-weight:700"><?= money($s['t']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script>
$("#sidebar-container").load("../sidebar.php");

var ctx = document.getElementById('reportChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels ?: ['No data']) ?>,
        datasets: [
            { label: 'Income',   data: <?= json_encode($income_data ?: [0])  ?>, backgroundColor: '#16a34a' },
            { label: 'Expenses', data: <?= json_encode($expense_data ?: [0]) ?>, backgroundColor: '#dc2626' },
            { label: 'Salaries', data: <?= json_encode($salary_data ?: [0])  ?>, backgroundColor: '#7c3aed' }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { yAxes: [{ ticks: { beginAtZero: true } }] }
    }
});
</script>
</body>
</html>