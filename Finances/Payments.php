<?php
// Finance/payments.php
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
    $invoice_id = (int)($_POST['invoice_id'] ?? 0);
    $client     = trim($_POST['client_name'] ?? '');
    $amount     = (float)($_POST['amount'] ?? 0);
    $method     = $_POST['method'] ?? 'bank';
    $reference  = trim($_POST['reference'] ?? '');
    $notes      = trim($_POST['notes'] ?? '');

    if ($amount > 0) {
        $stmt = $conn->prepare("
            INSERT INTO payments (invoice_id, client_name, amount, method, reference, notes, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$invoice_id ?: null, $client ?: null, $amount, $method, $reference, $notes, $me]);

        /* Update invoice */
        if ($invoice_id > 0) {
            $conn->prepare("
                UPDATE invoices
                SET amount_paid = amount_paid + ?,
                    status = CASE
                        WHEN amount_paid + ? >= total THEN 'paid'
                        WHEN amount_paid + ? > 0 THEN 'partial'
                        ELSE status
                    END
                WHERE id = ?
            ")->execute([$amount, $amount, $amount, $invoice_id]);
        }
    }
    header('Location: payments.php');
    exit();
}

/* Delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && in_array($staff_type, ['admin','superadmin'], true)) {
        $conn->prepare("DELETE FROM payments WHERE id = ?")->execute([$id]);
    }
    header('Location: payments.php');
    exit();
}

/* Pre-fill invoice */
$prefill_invoice = null;
if (!empty($_GET['invoice'])) {
    $stmt = $conn->prepare("SELECT * FROM invoices WHERE id = ?");
    $stmt->execute([(int)$_GET['invoice']]);
    $prefill_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* Fetch payments */
$payments = $conn->query("
    SELECT p.*, i.invoice_no
    FROM payments p
    LEFT JOIN invoices i ON i.id = p.invoice_id
    ORDER BY p.paid_at DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

$total_received = (float)$conn->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn();
$this_month     = (float)$conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(paid_at)=MONTH(CURDATE()) AND YEAR(paid_at)=YEAR(CURDATE())")->fetchColumn();

$open_invoices = $conn->query("
    SELECT id, invoice_no, client_name, (total - amount_paid) AS balance
    FROM invoices
    WHERE status NOT IN ('paid','cancelled') AND (total - amount_paid) > 0
    ORDER BY due_date
")->fetchAll(PDO::FETCH_ASSOC);

$can_delete = in_array($staff_type, ['admin','superadmin'], true);

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
<title>Payments — Marlani Admin</title>
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
.stat.blue{border-left-color:#2563eb}
.stat .label{font-size:.75rem;font-weight:700;text-transform:uppercase;color:#a0aec0;letter-spacing:.5px;margin-bottom:.4rem}
.stat .value{font-size:1.5rem;font-weight:800;color:#2d3748}
.card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);overflow:hidden}
.table{width:100%;border-collapse:collapse}
.table thead{background:#fafbff}
.table th{text-align:left;padding:.75rem 1rem;font-size:.72rem;color:#a0aec0;text-transform:uppercase;letter-spacing:.5px;font-weight:700}
.table td{padding:.9rem 1rem;border-bottom:1px solid #eef1f7;font-size:.88rem}
.table tr:last-child td{border-bottom:none}
.badge{display:inline-block;padding:.22rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-cash{background:#ecfdf5;color:#059669}
.badge-bank{background:#eff6ff;color:#2563eb}
.badge-card{background:#fdf2f8;color:#db2777}
.badge-mobile{background:#fef3c7;color:#d97706}
.badge-other{background:#f3f4f6;color:#6b7280}
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
    <h1><i class="fas fa-money-bill-wave"></i> Payments</h1>
    <?php include __DIR__ . '/_nav.php'; ?>

    <div class="head">
        <div></div>
        <button class="btn btn-primary" onclick="document.getElementById('newPay').classList.add('show')">
            <i class="fas fa-plus"></i> Record Payment
        </button>
    </div>

    <div class="cards">
        <div class="stat">
            <div class="label">Total Received</div>
            <div class="value"><?= money($total_received) ?></div>
        </div>
        <div class="stat blue">
            <div class="label">This Month</div>
            <div class="value"><?= money($this_month) ?></div>
        </div>
    </div>

    <div class="card">
        <?php if (empty($payments)): ?>
            <div class="empty"><i class="fas fa-money-bill-wave"></i>No payments recorded yet.</div>
        <?php else: ?>
            <table class="table">
                <thead><tr>
                    <th>Date</th><th>Client</th><th>Invoice</th><th>Amount</th>
                    <th>Method</th><th>Reference</th><th>Actions</th>
                </tr></thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= date('M j, Y H:i', strtotime($p['paid_at'])) ?><br>
                            <span style="font-size:.75rem;color:#a0aec0"><?= time_ago($p['paid_at']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($p['client_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($p['invoice_no'] ?? '—') ?></td>
                        <td style="font-weight:800;color:#16a34a"><?= money($p['amount']) ?></td>
                        <td><span class="badge badge-<?= $p['method'] ?>"><?= ucfirst($p['method']) ?></span></td>
                        <td style="font-size:.82rem;color:#718096"><?= htmlspecialchars($p['reference'] ?? '—') ?></td>
                        <td>
                            <?php if ($can_delete): ?>
                            <form method="POST" onsubmit="return confirm('Delete this payment?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
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

<!-- RECORD PAYMENT MODAL -->
<div class="modal<?= $prefill_invoice ? ' show' : '' ?>" id="newPay">
    <div class="modal-box">
        <h3><i class="fas fa-money-bill-wave"></i> Record Payment</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label>Invoice (optional)</label>
                <select name="invoice_id" id="invSelect">
                    <option value="">— No invoice / general payment —</option>
                    <?php foreach ($open_invoices as $inv): ?>
                        <option value="<?= (int)$inv['id'] ?>"
                                data-client="<?= htmlspecialchars($inv['client_name']) ?>"
                                data-balance="<?= (float)$inv['balance'] ?>"
                            <?= ($prefill_invoice && (int)$prefill_invoice['id'] === (int)$inv['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($inv['invoice_no'].' — '.$inv['client_name'].' ('.$inv['balance'].')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row2">
                <div class="form-group">
                    <label>Client</label>
                    <input type="text" name="client_name" id="clientInput"
                           value="<?= htmlspecialchars($prefill_invoice['client_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="amountInput"
                           value="<?= $prefill_invoice ? number_format((float)$prefill_invoice['total'] - (float)$prefill_invoice['amount_paid'], 2, '.', '') : '' ?>"
                           required>
                </div>
            </div>

            <div class="row2">
                <div class="form-group">
                    <label>Method</label>
                    <select name="method">
                        <option value="cash">Cash</option>
                        <option value="bank" selected>Bank Transfer</option>
                        <option value="card">Card</option>
                        <option value="mobile">Mobile Money</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Reference</label>
                    <input type="text" name="reference" placeholder="e.g. TXN-123456">
                </div>
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" placeholder="Optional notes"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-cancel"
                        onclick="document.getElementById('newPay').classList.remove('show')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Payment</button>
            </div>
        </form>
    </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script>
$("#sidebar-container").load("../sidebar.php");

/* Autofill client & amount when an invoice is chosen */
$('#invSelect').on('change', function() {
    var opt = $(this).find('option:selected');
    $('#clientInput').val(opt.data('client') || '');
    var bal = opt.data('balance');
    if (bal) $('#amountInput').val(parseFloat(bal).toFixed(2));
});

document.querySelectorAll('.modal').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); }));
</script>
</body>
</html>