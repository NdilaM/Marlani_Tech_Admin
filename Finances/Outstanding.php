<?php
// Finance/outstanding.php
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

$rows = $conn->query("
    SELECT id, invoice_no, client_name, issue_date, due_date, total, amount_paid,
           (total - amount_paid) AS balance,
           DATEDIFF(CURDATE(), due_date) AS days_overdue,
           status
    FROM invoices
    WHERE status NOT IN ('paid','cancelled')
      AND (total - amount_paid) > 0
    ORDER BY days_overdue DESC, balance DESC
")->fetchAll(PDO::FETCH_ASSOC);

$total_outstanding = 0;
$total_overdue = 0;
foreach ($rows as $r) {
    $total_outstanding += (float)$r['balance'];
    if ((int)$r['days_overdue'] > 0) $total_overdue += (float)$r['balance'];
}

function money($n) { return 'R ' . number_format((float)$n, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Outstanding — Marlani Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
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
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem;margin-bottom:1.5rem}
.stat{background:#fff;border-radius:12px;padding:1.25rem 1.5rem;box-shadow:0 4px 20px rgba(0,0,0,.06);border-left:4px solid #dc2626}
.stat.green{border-left-color:#16a34a}
.stat.orange{border-left-color:#f59e0b}
.stat .label{font-size:.75rem;font-weight:700;text-transform:uppercase;color:#a0aec0;letter-spacing:.5px;margin-bottom:.4rem}
.stat .value{font-size:1.5rem;font-weight:800;color:#2d3748}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);overflow:hidden}
.table{width:100%;border-collapse:collapse}
.table thead{background:#fafbff}
.table th{text-align:left;padding:.75rem 1rem;font-size:.72rem;color:#a0aec0;text-transform:uppercase;letter-spacing:.5px;font-weight:700}
.table td{padding:.9rem 1rem;border-bottom:1px solid #eef1f7;font-size:.88rem}
.table tr:last-child td{border-bottom:none}
.badge{display:inline-block;padding:.22rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-ok{background:#dcfce7;color:#16a34a}
.badge-due{background:#fef3c7;color:#d97706}
.badge-overdue{background:#fee2e2;color:#dc2626}
.empty{padding:4rem 2rem;text-align:center;color:#a0aec0}
.empty i{font-size:3rem;display:block;margin-bottom:.75rem;color:#d1d3e2}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;border-radius:8px;font-weight:700;font-size:.85rem;cursor:pointer;border:none;text-decoration:none;font-family:inherit}
.btn-primary{background:#003986;color:#fff}
.btn-primary:hover{background:#002a66}
</style>
</head>
<body>

<div id="sidebar-container"></div>

<div class="main">
    <h1><i class="fas fa-exclamation-circle"></i> Outstanding Balances</h1>
    <?php include __DIR__ . '/_nav.php'; ?>

    <div class="cards">
        <div class="stat">
            <div class="label">Total Outstanding</div>
            <div class="value"><?= money($total_outstanding) ?></div>
        </div>
        <div class="stat orange">
            <div class="label">Overdue Amount</div>
            <div class="value"><?= money($total_overdue) ?></div>
        </div>
        <div class="stat green">
            <div class="label">Active Invoices</div>
            <div class="value"><?= count($rows) ?></div>
        </div>
    </div>

    <div class="card">
        <?php if (empty($rows)): ?>
            <div class="empty"><i class="fas fa-check-circle"></i>No outstanding invoices — you're all caught up!</div>
        <?php else: ?>
            <table class="table">
                <thead><tr>
                    <th>Invoice #</th><th>Client</th><th>Issued</th><th>Due</th>
                    <th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $r):
                    $days = (int)$r['days_overdue'];
                    if ($days <= 0)      { $cls = 'badge-ok';     $lbl = 'Current'; }
                    elseif ($days <= 30) { $cls = 'badge-due';    $lbl = $days . ' days overdue'; }
                    else                 { $cls = 'badge-overdue';$lbl = $days . ' days overdue'; }
                ?>
                    <tr>
                        <td style="font-weight:700"><?= htmlspecialchars($r['invoice_no']) ?></td>
                        <td><?= htmlspecialchars($r['client_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($r['issue_date'])) ?></td>
                        <td><?= date('M j, Y', strtotime($r['due_date'])) ?></td>
                        <td><?= money($r['total']) ?></td>
                        <td><?= money($r['amount_paid']) ?></td>
                        <td style="font-weight:800;color:#dc2626"><?= money($r['balance']) ?></td>
                        <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
                        <td>
                            <a class="btn btn-primary" href="payments.php?invoice=<?= (int)$r['id'] ?>">
                                <i class="fas fa-dollar-sign"></i> Pay
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script>$("#sidebar-container").load("../sidebar.php");</script>
</body>
</html>