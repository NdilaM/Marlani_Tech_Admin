<?php
// Finance/invoices.php
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

$me = (int)$_SESSION['staff_id'];

/* Handle create */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $invoice_no  = trim($_POST['invoice_no'] ?? '');
    $client_name = trim($_POST['client_name'] ?? '');
    $issue_date  = $_POST['issue_date'] ?? date('Y-m-d');
    $due_date    = $_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days'));
    $subtotal    = (float)($_POST['subtotal'] ?? 0);
    $tax         = (float)($_POST['tax'] ?? 0);
    $total       = $subtotal + $tax;
    $notes       = trim($_POST['notes'] ?? '');

    if ($invoice_no === '') {
        $invoice_no = 'INV-' . date('Ymd') . '-' . rand(100, 999);
    }

    if ($client_name !== '') {
        $stmt = $conn->prepare("
            INSERT INTO invoices (invoice_no, client_name, issue_date, due_date, subtotal, tax, total, notes, created_by, status)
            VALUES (?,?,?,?,?,?,?,?,?, 'sent')
        ");
        $stmt->execute([$invoice_no, $client_name, $issue_date, $due_date, $subtotal, $tax, $total, $notes, $me]);
    }
    header('Location: invoices.php');
    exit();
}

/* Handle delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && in_array($staff_type, ['admin','superadmin'], true)) {
        $conn->prepare("DELETE FROM invoices WHERE id = ?")->execute([$id]);
    }
    header('Location: invoices.php');
    exit();
}

/* Filters */
$filter_status = $_GET['status'] ?? 'all';
$where = [];
$params = [];
if ($filter_status !== 'all' && in_array($filter_status, ['draft','sent','paid','partial','overdue','cancelled'], true)) {
    $where[] = "status = :st";
    $params[':st'] = $filter_status;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$invoices = $conn->prepare("SELECT * FROM invoices $whereSQL ORDER BY issue_date DESC");
$invoices->execute($params);
$invoices = $invoices->fetchAll(PDO::FETCH_ASSOC);

$counts = ['all'=>0,'draft'=>0,'sent'=>0,'paid'=>0,'partial'=>0,'overdue'=>0,'cancelled'=>0];
foreach ($conn->query("SELECT status, COUNT(*) c FROM invoices GROUP BY status") as $r) {
    $counts[$r['status']] = (int)$r['c'];
    $counts['all'] += (int)$r['c'];
}
$can_delete = in_array($staff_type, ['admin','superadmin'], true);

function money($n) { return 'R ' . number_format((float)$n, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoices — Marlani Admin</title>
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
.head{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.tab{padding:.55rem 1.1rem;background:#fff;border-radius:8px;font-weight:700;font-size:.88rem;color:#5a5c69;text-decoration:none;border:1.5px solid #e3e6f0;display:inline-flex;align-items:center;gap:.4rem;transition:all .15s}
.tab:hover{border-color:#003986;color:#003986}
.tab.active{background:#003986;color:#fff;border-color:#003986}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.25rem;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer;border:none;text-decoration:none;font-family:inherit;transition:all .15s}
.btn-primary{background:#003986;color:#fff}
.btn-primary:hover{background:#002a66}
.btn-cancel{background:#eaecf4;color:#5a5c69}
.btn-danger{background:#e74a3b;color:#fff}
.btn-sm{padding:.35rem .7rem;font-size:.8rem;border-radius:6px}
.filters{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.pill{padding:.5rem 1rem;background:#fff;border-radius:20px;font-weight:700;font-size:.85rem;color:#5a5c69;text-decoration:none;border:1.5px solid #e3e6f0;display:inline-flex;align-items:center;gap:.4rem}
.pill.active{background:#003986;color:#fff;border-color:#003986}
.pill .num{background:rgba(0,0,0,.08);border-radius:12px;padding:.05rem .5rem;font-size:.75rem}
.pill.active .num{background:rgba(255,255,255,.2)}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);overflow:hidden}
.table{width:100%;border-collapse:collapse}
.table thead{background:#fafbff}
.table th{text-align:left;padding:.75rem 1rem;font-size:.72rem;color:#a0aec0;text-transform:uppercase;letter-spacing:.5px;font-weight:700}
.table td{padding:.9rem 1rem;border-bottom:1px solid #eef1f7;font-size:.88rem}
.table tr:last-child td{border-bottom:none}
.table tr:hover{background:#fafbff}
.badge{display:inline-block;padding:.22rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-paid{background:#dcfce7;color:#16a34a}
.badge-sent{background:#dbeafe;color:#2563eb}
.badge-draft{background:#f3f4f6;color:#6b7280}
.badge-partial{background:#fef3c7;color:#d97706}
.badge-overdue{background:#fee2e2;color:#dc2626}
.badge-cancelled{background:#f3f4f6;color:#6b7280}
.empty{padding:4rem 2rem;text-align:center;color:#a0aec0}
.empty i{font-size:3rem;display:block;margin-bottom:.75rem;color:#d1d3e2}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:999;padding:1rem}
.modal.show{display:flex}
.modal-box{background:#fff;border-radius:12px;padding:1.75rem;width:100%;max-width:520px;max-height:90vh;overflow-y:auto}
.modal-box h3{margin-bottom:1.25rem;color:#2d3748;font-size:1.15rem}
.form-group{margin-bottom:1rem}
.form-group label{display:block;font-size:.85rem;font-weight:700;color:#4a5568;margin-bottom:.35rem}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:.7rem .9rem;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:.95rem;color:#2d3748}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#003986;box-shadow:0 0 0 3px rgba(0,57,134,.15)}
.form-group textarea{min-height:80px;resize:vertical}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.modal-actions{display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.25rem}
</style>
</head>
<body>

<div id="sidebar-container"></div>

<div class="main">
    <h1><i class="fas fa-file-invoice"></i> Invoices</h1>
    <?php include __DIR__ . '/_nav.php'; ?>

    <div class="head">
        <div></div>
        <button class="btn btn-primary" onclick="document.getElementById('newInv').classList.add('show')">
            <i class="fas fa-plus"></i> New Invoice
        </button>
    </div>

    <div class="filters">
        <?php foreach (['all'=>'All','draft'=>'Draft','sent'=>'Sent','paid'=>'Paid','partial'=>'Partial','overdue'=>'Overdue','cancelled'=>'Cancelled'] as $k=>$lbl): ?>
            <a class="pill<?= $filter_status===$k?' active':'' ?>" href="?status=<?= $k ?>">
                <?= $lbl ?> <span class="num"><?= $counts[$k] ?? 0 ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <?php if (empty($invoices)): ?>
            <div class="empty"><i class="fas fa-file-invoice"></i>No invoices found.</div>
        <?php else: ?>
            <table class="table">
                <thead><tr>
                    <th>Invoice #</th><th>Client</th><th>Issued</th><th>Due</th>
                    <th>Total</th><th>Paid</th><th>Status</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($invoices as $inv):
                    $balance = (float)$inv['total'] - (float)$inv['amount_paid'];
                ?>
                    <tr>
                        <td style="font-weight:700"><?= htmlspecialchars($inv['invoice_no']) ?></td>
                        <td><?= htmlspecialchars($inv['client_name']) ?></td>
                        <td><?= date('M j, Y', strtotime($inv['issue_date'])) ?></td>
                        <td><?= date('M j, Y', strtotime($inv['due_date'])) ?></td>
                        <td style="font-weight:700"><?= money($inv['total']) ?></td>
                        <td><?= money($inv['amount_paid']) ?></td>
                        <td><span class="badge badge-<?= $inv['status'] ?>"><?= ucfirst($inv['status']) ?></span></td>
                        <td style="display:flex;gap:.35rem">
                            <a class="btn btn-cancel btn-sm" href="payments.php?invoice=<?= (int)$inv['id'] ?>" title="Record payment">
                                <i class="fas fa-dollar-sign"></i>
                            </a>
                            <?php if ($can_delete): ?>
                            <form method="POST" onsubmit="return confirm('Delete this invoice?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
                                <button class="btn btn-danger btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- NEW INVOICE MODAL -->
<div class="modal" id="newInv">
    <div class="modal-box">
        <h3><i class="fas fa-file-invoice"></i> New Invoice</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="row2">
                <div class="form-group">
                    <label>Invoice #</label>
                    <input type="text" name="invoice_no" placeholder="Auto if empty">
                </div>
                <div class="form-group">
                    <label>Client</label>
                    <input type="text" name="client_name" required>
                </div>
            </div>
            <div class="row2">
                <div class="form-group">
                    <label>Issue Date</label>
                    <input type="date" name="issue_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Due Date</label>
                    <input type="date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
            </div>
            <div class="row2">
                <div class="form-group">
                    <label>Subtotal</label>
                    <input type="number" step="0.01" min="0" name="subtotal" required>
                </div>
                <div class="form-group">
                    <label>Tax</label>
                    <input type="number" step="0.01" min="0" name="tax" value="0">
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" placeholder="Optional notes"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-cancel"
                        onclick="document.getElementById('newInv').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Invoice</button>
            </div>
        </form>
    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script>
$("#sidebar-container").load("../sidebar.php");
document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); }));
</script>
</body>
</html>