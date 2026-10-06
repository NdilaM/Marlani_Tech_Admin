<?php
// Support/create_ticket.php
session_start();
require_once '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

$me         = (int)$_SESSION['staff_id'];
$staff_type = strtolower($_SESSION['staff_type'] ?? 'staff');
$is_admin   = in_array($staff_type, ['admin', 'manager', 'superadmin'], true);
$error      = $_GET['error'] ?? '';

/* Recent tickets by this user (last 5) */
$stmt = $conn->prepare("
    SELECT id, subject, status, priority, created_at
    FROM support_tickets
    WHERE staff_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$me]);
$myTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

function my_status_badge($s) {
    $map = [
        'open'        => ['Open',        '#fef3c7', '#d97706'],
        'in_progress' => ['In Progress', '#dbeafe', '#2563eb'],
        'resolved'    => ['Resolved',    '#dcfce7', '#16a34a'],
        'closed'      => ['Closed',      '#f3f4f6', '#6b7280'],
    ];
    [$label, $bg, $fg] = $map[$s] ?? ['Unknown', '#f3f4f6', '#6b7280'];
    return "<span style=\"display:inline-block;padding:.2rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;background:$bg;color:$fg\">$label</span>";
}

function my_time_ago($dt) {
    $d = time() - strtotime($dt);
    if ($d < 60)     return $d . 's ago';
    if ($d < 3600)   return floor($d/60) . 'm ago';
    if ($d < 86400)  return floor($d/3600) . 'h ago';
    return floor($d/86400) . 'd ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Create Ticket — Marlani Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Nunito,sans-serif;background:#f8f9fc;display:flex;min-height:100vh;color:#5a5c69}
#sidebar-container{display:flex;flex-shrink:0;height:100vh;position:sticky;top:0;align-self:flex-start;z-index:100}
.main{flex:1;padding:2rem;min-width:0}

.head{display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
h1{font-size:1.5rem;color:#2d3748}
h1 i{color:#003986;margin-right:.5rem}

.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.7rem 1.4rem;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer;border:none;text-decoration:none;font-family:inherit;transition:all .15s}
.btn-primary{background:#003986;color:#fff}
.btn-primary:hover{background:#002a66}
.btn-cancel{background:#eaecf4;color:#5a5c69}
.btn-cancel:hover{background:#d8dbe4}

.card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);padding:2rem;max-width:720px}
.form-group{margin-bottom:1.25rem}
.form-group label{display:block;font-size:.85rem;font-weight:700;color:#4a5568;margin-bottom:.4rem}
.form-group label .req{color:#e74a3b}
.form-group input,
.form-group select,
.form-group textarea{
    width:100%;padding:.75rem .9rem;border:1.5px solid #e2e8f0;border-radius:8px;
    font-family:inherit;font-size:.95rem;color:#2d3748;background:#fff;
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus{outline:none;border-color:#003986;box-shadow:0 0 0 3px rgba(0,57,134,.15)}
.form-group textarea{min-height:160px;resize:vertical}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.hint{font-size:.78rem;color:#a0aec0;margin-top:.35rem}

.alert{padding:.9rem 1.1rem;border-radius:8px;font-size:.9rem;font-weight:600;margin-bottom:1.25rem;display:flex;gap:.6rem;align-items:center}
.alert-error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}

.form-actions{display:flex;justify-content:flex-end;gap:.6rem;margin-top:1.75rem;padding-top:1.25rem;border-top:1px solid #eef1f7}

/* Recent tickets table */
.recent-card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);padding:1.5rem;max-width:720px;margin-top:1.5rem}
.recent-card h2{font-size:1rem;color:#2d3748;margin-bottom:1rem;display:flex;align-items:center;gap:.5rem}
.recent-card h2 i{color:#003986}
.recent-table{width:100%;border-collapse:collapse}
.recent-table thead{background:#fafbff}
.recent-table th{text-align:left;padding:.7rem;font-size:.72rem;color:#a0aec0;text-transform:uppercase;letter-spacing:.5px;font-weight:700}
.recent-table td{padding:.8rem .7rem;border-bottom:1px solid #eef1f7;font-size:.88rem}
.recent-table tr:last-child td{border-bottom:none}

@media (max-width:600px){
    .row2{grid-template-columns:1fr}
    .main{padding:1rem}
    .card,.recent-card{padding:1.25rem}
}
</style>
</head>
<body>

<div id="sidebar-container"></div>

<div class="main">
    <div class="head">
        <h1><i class="fas fa-plus-circle"></i> Create Support Ticket</h1>

        <?php if ($is_admin): ?>
            <a class="btn btn-cancel" href="support.php">
                <i class="fas fa-arrow-left"></i> Back to Tickets
            </a>
        <?php endif; ?>
    </div>

    <div class="card">
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                Please fill in all required fields correctly.
            </div>
        <?php endif; ?>

        <form method="POST" action="submit-ticket.php">
            <div class="form-group">
                <label>Subject <span class="req">*</span></label>
                <input type="text" name="subject" required maxlength="255"
                       placeholder="Brief summary of the issue">
            </div>

            <div class="row2">
                <div class="form-group">
                    <label>Category <span class="req">*</span></label>
                    <select name="category" required>
                        <option value="general">General</option>
                        <option value="bug">Bug / Error</option>
                        <option value="hardware">Hardware</option>
                        <option value="software">Software</option>
                        <option value="access">Access / Login</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority <span class="req">*</span></label>
                    <select name="priority" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Description <span class="req">*</span></label>
                <textarea name="description" required
                          placeholder="Describe the issue in detail. Include steps to reproduce if applicable, error messages, and any relevant screenshots or links."></textarea>
                <div class="hint">Be as specific as possible — it helps us resolve your issue faster.</div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Ticket
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($myTickets)): ?>
    <div class="recent-card">
        <h2><i class="fas fa-history"></i> Your Recent Tickets</h2>
        <table class="recent-table">
            <thead>
                <tr>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($myTickets as $t): ?>
                <tr>
                    <td style="font-weight:700;color:#2d3748">
                        <?= htmlspecialchars($t['subject']) ?>
                    </td>
                    <td><?= my_status_badge($t['status']) ?></td>
                    <td style="font-size:.85rem;color:#5a5c69">
                        <?= ucfirst($t['priority']) ?>
                    </td>
                    <td style="font-size:.8rem;color:#a0aec0">
                        <?= my_time_ago($t['created_at']) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if (isset($_GET['success'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var t = document.createElement('div');
    t.textContent = '✔ Ticket submitted successfully';
    t.style.cssText = 'position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);background:#16a34a;color:#fff;padding:.8rem 1.6rem;border-radius:30px;font-weight:700;font-family:Nunito,sans-serif;box-shadow:0 8px 24px rgba(0,0,0,.15);z-index:9999';
    document.body.appendChild(t);
    setTimeout(function(){ t.remove(); }, 3500);
});
</script>
<?php endif; ?>

<script src="../vendor/jquery/jquery.min.js"></script>
<script>
$("#sidebar-container").load("../sidebar.php");
</script>
</body>
</html>