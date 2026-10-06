<?php
// Support/support.php — View & manage tickets (admins/managers only)
session_start();
require_once '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

$me         = (int)$_SESSION['staff_id'];
$staff_type = strtolower($_SESSION['staff_type'] ?? 'staff');
$is_admin   = in_array($staff_type, ['admin', 'manager', 'superadmin'], true);

/* ---- Only admins/managers may view the ticket list ---- */
if (!$is_admin) {
    header('Location: create_ticket.php');
    exit();
}

/* ============================================================
   HANDLE INLINE ACTIONS (assign / status change / delete)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $action    = $_POST['action'] ?? '';

    if ($ticket_id > 0) {
        if ($action === 'assign') {
            $assignee = (int)($_POST['assigned_to'] ?? 0);
            $stmt = $conn->prepare("UPDATE support_tickets SET assigned_to = ? WHERE id = ?");
            $stmt->execute([$assignee > 0 ? $assignee : null, $ticket_id]);

        } elseif ($action === 'status') {
            $new_status = $_POST['status'] ?? 'open';
            if (in_array($new_status, ['open','in_progress','resolved','closed'], true)) {
                $resolved_at = ($new_status === 'resolved' || $new_status === 'closed')
                               ? date('Y-m-d H:i:s') : null;
                $stmt = $conn->prepare("UPDATE support_tickets SET status = ?, resolved_at = ? WHERE id = ?");
                $stmt->execute([$new_status, $resolved_at, $ticket_id]);
            }

        } elseif ($action === 'delete') {
            if (in_array($staff_type, ['admin','superadmin'], true)) {
                $stmt = $conn->prepare("DELETE FROM support_tickets WHERE id = ?");
                $stmt->execute([$ticket_id]);
            }
        }
    }
    header('Location: support.php');
    exit();
}

/* ============================================================
   FILTERS
   ============================================================ */
$filter_status   = $_GET['status']   ?? 'all';
$filter_assignee = (int)($_GET['assignee'] ?? 0);

$where  = [];
$params = [];

if ($filter_status !== 'all' && in_array($filter_status, ['open','in_progress','resolved','closed'], true)) {
    $where[] = "t.status = :st";
    $params[':st'] = $filter_status;
}

if ($filter_assignee > 0) {
    $where[] = "t.assigned_to = :as";
    $params[':as'] = $filter_assignee;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ============================================================
   FETCH TICKETS
   ============================================================ */
$sql = "
    SELECT
        t.*,
        r.first_name AS reporter_first, r.last_name AS reporter_last, r.email AS reporter_email,
        a.first_name AS assignee_first, a.last_name AS assignee_last
    FROM support_tickets t
    LEFT JOIN staff r ON r.id = t.staff_id
    LEFT JOIN staff a ON a.id = t.assigned_to
    $whereSQL
    ORDER BY
        FIELD(t.status,'open','in_progress','resolved','closed'),
        FIELD(t.priority,'urgent','high','medium','low'),
        t.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ============================================================
   HELPERS
   ============================================================ */
function time_ago($dt) {
    $d = time() - strtotime($dt);
    if ($d < 60)     return $d . 's ago';
    if ($d < 3600)   return floor($d/60) . 'm ago';
    if ($d < 86400)  return floor($d/3600) . 'h ago';
    if ($d < 604800) return floor($d/86400) . 'd ago';
    return date('M j, Y', strtotime($dt));
}

function status_badge($s) {
    $map = [
        'open'        => ['Open',        '#fef3c7', '#d97706'],
        'in_progress' => ['In Progress', '#dbeafe', '#2563eb'],
        'resolved'    => ['Resolved',    '#dcfce7', '#16a34a'],
        'closed'      => ['Closed',      '#f3f4f6', '#6b7280'],
    ];
    [$label, $bg, $fg] = $map[$s] ?? ['Unknown', '#f3f4f6', '#6b7280'];
    return "<span style=\"display:inline-block;padding:.25rem .65rem;border-radius:20px;font-size:.75rem;font-weight:700;background:$bg;color:$fg\">$label</span>";
}

function priority_badge($p) {
    $map = [
        'urgent' => ['Urgent', '#fee2e2', '#dc2626'],
        'high'   => ['High',   '#ffedd5', '#ea580c'],
        'medium' => ['Medium', '#fef3c7', '#d97706'],
        'low'    => ['Low',    '#e0f2fe', '#0284c7'],
    ];
    [$label, $bg, $fg] = $map[$p] ?? ['Medium', '#fef3c7', '#d97706'];
    return "<span style=\"display:inline-block;padding:.2rem .55rem;border-radius:20px;font-size:.7rem;font-weight:700;background:$bg;color:$fg\">$label</span>";
}

/* Staff list for the assignee dropdown */
$allStaff = $conn->query("SELECT id, first_name, last_name FROM staff ORDER BY first_name")
                 ->fetchAll(PDO::FETCH_ASSOC);

/* Counts for the filter pills */
$counts = ['all' => 0, 'open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
foreach ($conn->query("SELECT status, COUNT(*) c FROM support_tickets GROUP BY status") as $row) {
    $counts[$row['status']] = (int)$row['c'];
    $counts['all'] += (int)$row['c'];
}

$can_delete = in_array($staff_type, ['admin','superadmin'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Support Tickets — Marlani Admin</title>
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

.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.25rem;border-radius:8px;font-weight:700;font-size:.9rem;cursor:pointer;border:none;text-decoration:none;font-family:inherit;transition:all .15s}
.btn-primary{background:#003986;color:#fff}
.btn-primary:hover{background:#002a66}
.btn-cancel{background:#eaecf4;color:#5a5c69}
.btn-cancel:hover{background:#d8dbe4}
.btn-sm{padding:.35rem .7rem;font-size:.8rem;border-radius:6px}
.btn-danger{background:#e74a3b;color:#fff}
.btn-danger:hover{background:#c0392b}

/* FILTERS */
.filters{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.pill{padding:.5rem 1rem;background:#fff;border-radius:20px;font-weight:700;font-size:.85rem;color:#5a5c69;text-decoration:none;border:1.5px solid #e3e6f0;display:inline-flex;align-items:center;gap:.4rem;transition:all .15s}
.pill:hover{border-color:#003986;color:#003986}
.pill.active{background:#003986;color:#fff;border-color:#003986}
.pill .num{background:rgba(0,0,0,.08);border-radius:12px;padding:.05rem .5rem;font-size:.75rem}
.pill.active .num{background:rgba(255,255,255,.2)}

/* CARD */
.card{background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.06);overflow:hidden}
.table{width:100%;border-collapse:collapse}
.table thead{background:#fafbff;border-bottom:1px solid #eef1f7}
.table th{padding:.9rem 1rem;text-align:left;font-size:.75rem;text-transform:uppercase;color:#a0aec0;font-weight:700;letter-spacing:.5px}
.table td{padding:1rem;border-bottom:1px solid #eef1f7;font-size:.9rem;vertical-align:middle}
.table tr:last-child td{border-bottom:none}
.table tr:hover{background:#fafbff}
.subject{font-weight:700;color:#2d3748;display:block;margin-bottom:.15rem}
.desc{color:#718096;font-size:.82rem;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical}
.reporter{display:flex;align-items:center;gap:.6rem}
.avatar{width:32px;height:32px;border-radius:50%;background:#003986;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0}
.reporter .name{font-weight:700;color:#2d3748;font-size:.85rem}
.reporter .email{font-size:.75rem;color:#a0aec0}
.assignee{display:flex;align-items:center;gap:.5rem}
.assignee .avatar{background:#16a34a}
.muted{color:#a0aec0;font-style:italic;font-size:.85rem}
.time{font-size:.78rem;color:#a0aec0;white-space:nowrap}
.actions{display:flex;gap:.35rem;flex-wrap:wrap}
.actions form{display:inline}

select.mini{padding:.35rem .5rem;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.8rem;font-family:inherit;color:#2d3748;background:#fff;cursor:pointer}
select.mini:focus{outline:none;border-color:#003986}

.empty{padding:4rem 2rem;text-align:center;color:#a0aec0}
.empty i{font-size:3rem;display:block;margin-bottom:.75rem;color:#d1d3e2}
</style>
</head>
<body>

<div id="sidebar-container"></div>

<div class="main">
    <div class="head">
        <h1><i class="fas fa-headset"></i> Support Tickets</h1>
        <a class="btn btn-primary" href="create_ticket.php">
            <i class="fas fa-plus"></i> New Ticket
        </a>
    </div>

    <!-- STATUS FILTER PILLS -->
    <div class="filters">
        <?php
        $pills = [
            'all'         => 'All',
            'open'        => 'Open',
            'in_progress' => 'In Progress',
            'resolved'    => 'Resolved',
            'closed'      => 'Closed',
        ];
        foreach ($pills as $key => $label):
            $active = ($filter_status === $key) ? ' active' : '';
            $count  = $counts[$key] ?? 0;
        ?>
            <a class="pill<?= $active ?>" href="?status=<?= $key ?>">
                <?= $label ?> <span class="num"><?= $count ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- TICKETS TABLE -->
    <div class="card">
        <?php if (empty($tickets)): ?>
            <div class="empty">
                <i class="fas fa-inbox"></i>
                No tickets found<?= $filter_status !== 'all' ? ' for this filter' : '' ?>.
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Reported By</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($tickets as $t):
                    $reporter_name = trim($t['reporter_first'].' '.$t['reporter_last']) ?: 'Unknown';
                    $assignee_name = $t['assignee_first'] ? trim($t['assignee_first'].' '.$t['assignee_last']) : null;
                ?>
                    <tr>
                        <td style="max-width:280px">
                            <span class="subject"><?= htmlspecialchars($t['subject']) ?></span>
                            <span class="desc"><?= htmlspecialchars($t['description']) ?></span>
                        </td>
                        <td>
                            <div class="reporter">
                                <div class="avatar"><?= strtoupper(substr($t['reporter_first'] ?? 'U', 0, 1)) ?></div>
                                <div>
                                    <div class="name"><?= htmlspecialchars($reporter_name) ?></div>
                                    <div class="email"><?= htmlspecialchars($t['reporter_email'] ?? '') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= priority_badge($t['priority']) ?></td>
                        <td><?= status_badge($t['status']) ?></td>
                        <td>
                            <?php if ($assignee_name): ?>
                                <div class="assignee">
                                    <div class="avatar"><?= strtoupper(substr($t['assignee_first'], 0, 1)) ?></div>
                                    <span><?= htmlspecialchars($assignee_name) ?></span>
                                </div>
                            <?php else: ?>
                                <span class="muted">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="time"><?= time_ago($t['created_at']) ?></td>

                        <td>
                            <div class="actions">
                                <!-- Assign -->
                                <form method="POST">
                                    <input type="hidden" name="action" value="assign">
                                    <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
                                    <select name="assigned_to" class="mini" onchange="this.form.submit()">
                                        <option value="0">— Assign to —</option>
                                        <?php foreach ($allStaff as $s): ?>
                                            <option value="<?= (int)$s['id'] ?>"
                                                <?= ((int)$t['assigned_to'] === (int)$s['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($s['first_name'].' '.$s['last_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>

                                <!-- Status -->
                                <form method="POST">
                                    <input type="hidden" name="action" value="status">
                                    <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
                                    <select name="status" class="mini" onchange="this.form.submit()">
                                        <option value="open"        <?= $t['status']==='open'?'selected':'' ?>>Open</option>
                                        <option value="in_progress" <?= $t['status']==='in_progress'?'selected':'' ?>>In Progress</option>
                                        <option value="resolved"    <?= $t['status']==='resolved'?'selected':'' ?>>Resolved</option>
                                        <option value="closed"      <?= $t['status']==='closed'?'selected':'' ?>>Closed</option>
                                    </select>
                                </form>

                                <!-- Delete (admin/superadmin only) -->
                                <?php if ($can_delete): ?>
                                <form method="POST" onsubmit="return confirm('Delete this ticket?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
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