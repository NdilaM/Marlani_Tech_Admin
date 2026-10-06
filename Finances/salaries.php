<?php
// Finance/salaries.php
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

/* Create */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $staff_id    = (int)($_POST['staff_id'] ?? 0);
    $base        = (float)($_POST['base_salary'] ?? 0);
    $allowance   = (float)($_POST['allowance'] ?? 0);
    $deduction   = (float)($_POST['deduction'] ?? 0);
    $net         = $base + $allowance - $deduction;
    $period      = trim($_POST['pay_period'] ?? date('Y-m'));
    $pay_date    = $_POST['pay_date'] ?? date('Y-m-d');
    $notes       = trim($_POST['notes'] ?? '');

    if ($staff_id > 0 && $base > 0) {
        $stmt = $conn->prepare("
            INSERT INTO salaries (staff_id, base_salary, allowance, deduction, net_salary, pay_period, pay_date, notes)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $stmt->execute([$staff_id, $base, $allowance, $deduction, $net, $period, $pay_date, $notes]);
    }
    header('Location: salaries.php');
    exit();
}

/* Update status */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    $id = (int)($_POST['id'] ?? 0);
    $st = $_POST['status'] ?? 'pending';
    if ($id && in_array($st, ['pending','paid','cancelled'], true)) {
        $conn->prepare("UPDATE salaries SET status = ? WHERE id = ?")->execute([$st, $id]);
    }
    header('Location: salaries.php');
    exit();
}

/* Delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && in_array($staff_type, ['admin','superadmin'], true)) {
        $conn->prepare("DELETE FROM salaries WHERE id = ?")->execute([$id]);
    }
    header('Location: salaries.php');
    exit();
}

/* Stats */
$total_paid    = (float)$conn->query("SELECT COALESCE(SUM(net_salary),0) FROM salaries WHERE status='paid'")->fetchColumn();
$total_pending = (float)$conn->query("SELECT COALESCE(SUM(net_salary),0) FROM salaries WHERE status='pending'")->fetchColumn();
$this_month    = (float)$conn->query("SELECT COALESCE(SUM(net_salary),0) FROM salaries WHERE pay_period = DATE_FORMAT(CURDATE(),'%Y-%m')")->fetchColumn();

/* Staff list */
$staff = $conn->query("SELECT id, first_name, last_name, staff_type FROM staff ORDER BY first_name")
              ->fetchAll(PDO::FETCH_ASSOC);

/* Salaries */
$rows = $conn->query("
    SELECT s.*, st.first_name, st.last_name, st.staff_type
    FROM salaries s
    JOIN staff st ON st.id = s.staff_id
    ORDER BY s.pay_date DESC, s.id DESC
    LIMIT 200
")->fetchAll(PDO::FETCH_ASSOC);

$can_delete = in_array($staff_type, ['admin','superadmin'], true);

function money($n) { return 'R ' . number_format((float)$n, 2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Staff Salaries — Marlani Admin</title>
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
.tab{padding:.55rem 1.1rem;background:#fff;border-radius:8px;font-weight:700;font-size:.88rem;color:#5a5c69;text-decoration:none;border:1.5px solid #e3e6f0;display:inline-flex;align-items:center;gap:.4rem}
.tab.active{background:#003986;color:#fff;border-color:#003986}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.25rem;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer;border:none;text-decoration:none;font-family:inherit}
.btn-primary{background:#003986;color:#fff}
.btn-primary:hover{background:#002a66}
.btn-cancel{background:#eaecf4;color:#5a5c69}
.btn-danger{background:#e74a3b;color:#fff}
.btn-sm{padding:.35rem .7rem;font-size:.8rem;border-radius:6px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem;margin-bottom:1.5rem}
.stat{background:#fff;border-radius:12px;padding:1.25rem 1.5rem;box-shadow:0 4px 20px rgba(0,0,0,.06);border-left:4px solid #16a34a}
.stat.orange{border-left-color:#f59e0b}
.stat.purple{border-left-color:#7c3aed}
.stat .label{font-size:.75rem;font-weight:700;text-transform:uppercase;color:#a0aec0;letter-spacing:.5px;margin-bottom:.4rem}
.stat .value{font-size:1.5rem;font-weight:800;color:#2d3748}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);overflow:hidden}
.table{width:100%;border-collapse:collapse}
.table thead{background:#fafbff}
.table th{text-align:left;padding:.75rem 1rem;font-size:.72rem;color:#a0aec0;text-transform:uppercase;letter-spacing:.5px;font-weight:700}
.table td{padding:.9rem 1rem;border-bottom:1px solid #eef1f7;font-size:.88rem}
.table tr:last-child td{border-bottom:none}
.badge{display:inline-block;padding:.22rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-paid{background:#dcfce7;color:#16a34a}
.badge-pending{background:#fef3c7;color:#d97706}
.badge-cancelled{background:#f3f4f6;color:#6b7280}
.empty{padding:4rem 2rem;text-align:center;color:#a0aec0}
.empty i{font-size:3rem;display:block;margin-bottom:.75rem;color:#d1d3e2}
.modal{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:999;padding:1rem}
.modal.show{display:flex}
.modal-box{background:#fff;border-radius:12px;padding:1.75rem;width:100%;max-width:560px;max-height:90vh;overflow-y:auto}
.modal-box h3{margin-bottom:1.25rem;color:#2d3748;font-size:1.15rem}
.form-group{margin-bottom:1rem}
.form-group label{display:block;font-size:.85rem;font-weight:700;color:#4a5568;margin-bottom:.35rem}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:.7rem .9rem;border:1.5px solid #e2e8f0;border-radius:8px;font-family:inherit;font-size:.95rem;color:#2d3748}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#003986;box-shadow:0 0 0 3px rgba(0,57,134,.15)}
.form-group textarea{min-height:70px;resize:vertical}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem}
.modal-actions{display:flex;justify-content:flex-end;gap:.5rem;margin-top:1.25rem}
.net-preview{background:#f0f4ff;border-radius:8px;padding:.85rem 1rem;font-weight:700;color:#003986;text-align:center;margin-top:.5rem}
select.mini{padding:.35rem .5rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.8rem;font-family:inherit;color:#2d3748;background:#fff;cursor:pointer}
select.mini:focus{outline:none;border-color:#003986}
</style>
</head>
<body>

<div id="sidebar-container"></div>

<div class="main">
    <h1><i class="fas fa-user-tie"></i> Staff Salaries</h1>
    <?php include __DIR__ . '/_nav.php'; ?>

    <div class="head">
        <div></div>
        <button class="btn btn-primary" onclick="document.getElementById('newSal').classList.add('show')">
            <i class="fas fa-plus"></i> Add Salary Record
        </button>
    </div>

    <div class="cards">
        <div class="stat">
            <div class="label">Total Paid</div>
            <div class="value"><?= money($total_paid) ?></div>
        </div>
        <div class="stat orange">
            <div class="label">Pending Payouts</div>
            <div class="value"><?= money($total_pending) ?></div>
        </div>
        <div class="stat purple">
            <div class="label">This Month</div>
            <div class="value"><?= money($this_month) ?></div>
        </div>
    </div>

    <div class="card">
        <?php if (empty($rows)): ?>
            <div class="empty"><i class="fas fa-user-tie"></i>No salary records yet.</div>
        <?php else: ?>
            <table class="table">
                <thead><tr>
                    <th>Employee</th><th>Period</th><th>Base</th><th>Allow.</th><th>Deduct.</th>
                    <th>Net</th><th>Pay Date</th><th>Status</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td style="font-weight:700">
                            <?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?>
                            <div style="font-weight:400;font-size:.75rem;color:#a0aec0"><?= htmlspecialchars(ucfirst($r['staff_type'])) ?></div>
                        </td>
                        <td><?= htmlspecialchars($r['pay_period']) ?></td>
                        <td><?= money($r['base_salary']) ?></td>
                        <td style="color:#16a34a">+<?= money($r['allowance']) ?></td>
                        <td style="color:#dc2626">−<?= money($r['deduction']) ?></td>
                        <td style="font-weight:800"><?= money($r['net_salary']) ?></td>
                        <td><?= date('M j, Y', strtotime($r['pay_date'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <select name="status" class="mini" onchange="this.form.submit()">
                                    <option value="pending"   <?= $r['status']==='pending'?'selected':'' ?>>Pending</option>
                                    <option value="paid"      <?= $r['status']==='paid'?'selected':'' ?>>Paid</option>
                                    <option value="cancelled" <?= $r['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                                </select>
                            </form>
                        </td>
                        <td>
                            <?php if ($can_delete): ?>
                            <form method="POST" onsubmit="return confirm('Delete this salary record?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
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

<!-- NEW SALARY MODAL -->
<div class="modal" id="newSal">
    <div class="modal-box">
        <h3><i class="fas fa-user-tie"></i> Add Salary Record</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label>Employee</label>
                <select name="staff_id" required>
                    <option value="">— Select —</option>
                    <?php foreach ($staff as $s): ?>
                        <option value="<?= (int)$s['id'] ?>">
                            <?= htmlspecialchars($s['first_name'].' '.$s['last_name'].' ('.ucfirst($s['staff_type']).')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row2">
                <div class="form-group">
                    <label>Pay Period</label>
                    <input type="text" name="pay_period" value="<?= date('Y-m') ?>" placeholder="YYYY-MM" required>
                </div>
                <div class="form-group">
                    <label>Pay Date</label>
                    <input type="date" name="pay_date" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="row3">
                <div class="form-group">
                    <label>Base Salary</label>
                    <input type="number" step="0.01" min="0" name="base_salary" id="base" required oninput="calcNet()">
                </div>
                <div class="form-group">
                    <label>Allowance</label>
                    <input type="number" step="0.01" min="0" name="allowance" id="allow" value="0" oninput="calcNet()">
                </div>
                <div class="form-group">
                    <label>Deduction</label>
                    <input type="number" step="0.01" min="0" name="deduction" id="deduct" value="0" oninput="calcNet()">
                </div>
            </div>

            <div class="net-preview">Net Salary: <span id="netPreview">$0.00</span></div>

            <div class="form-group" style="margin-top:1rem">
                <label>Notes</label>
                <textarea name="notes" placeholder="Optional"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-cancel"
                        onclick="document.getElementById('newSal').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script>
$("#sidebar-container").load("../sidebar.php");

function calcNet() {
    var b = parseFloat($('#base').val()) || 0;
    var a = parseFloat($('#allow').val()) || 0;
    var d = parseFloat($('#deduct').val()) || 0;
    $('#netPreview').text('$' + (b + a - d).toFixed(2));
}
calcNet();

document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); }));
</script>
</body>
</html>