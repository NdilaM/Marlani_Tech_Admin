<?php
// index.php - Dashboard
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.html');
    exit();
}

require_once 'db.php';

$first_name = $_SESSION['first_name'] ?? 'User';
$last_name  = $_SESSION['last_name']  ?? '';
$email      = $_SESSION['email']      ?? '';
$staff_id   = (int)($_SESSION['staff_id'] ?? 0);

/* =========================================================
   STAFF STATS
   ========================================================= */
$stmt = $conn->query("SELECT COUNT(*) as total FROM staff");
$total_staff = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("SELECT first_name, last_name, email, created_at FROM staff ORDER BY created_at DESC LIMIT 5");
$recent_staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT COUNT(*) as new FROM staff WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$new_count = $stmt->fetch(PDO::FETCH_ASSOC)['new'];

/* =========================================================
   CLIENT STATS
   ========================================================= */
$stmt = $conn->query("SELECT COUNT(*) as total FROM clients");
$total_clients = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active'    THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'pending'   THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'inactive'  THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended
    FROM clients
");
$client_stats = $stmt->fetch(PDO::FETCH_ASSOC);

$active_clients    = $client_stats['active']    ?? 0;
$pending_clients   = $client_stats['pending']   ?? 0;
$inactive_clients  = $client_stats['inactive']  ?? 0;
$suspended_clients = $client_stats['suspended'] ?? 0;

$stmt = $conn->query("SELECT company_name, contact_person, email, status, created_at FROM clients ORDER BY created_at DESC LIMIT 5");
$recent_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT COUNT(*) as total FROM quotations");
$total_quotes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

/* =========================================================
   MESSAGES (staff-to-staff) — for the topbar dropdown
   ========================================================= */
$messages = [];
$unread_messages = 0;

try {
    $stmt = $conn->prepare("
        SELECT m.*, s.first_name, s.last_name
        FROM messages m
        JOIN staff s ON s.id = m.sender_id
        WHERE m.recipient_id = :me
          AND m.id = (
              SELECT MAX(id) FROM messages
              WHERE sender_id = m.sender_id AND recipient_id = :me2
          )
        ORDER BY m.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([':me' => $staff_id, ':me2' => $staff_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT COUNT(*) AS u FROM messages WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$staff_id]);
    $unread_messages = (int)$stmt->fetch(PDO::FETCH_ASSOC)['u'];
} catch (PDOException $e) {
    error_log('Messages query failed: ' . $e->getMessage());
}

/* Helper: time ago */
function time_ago($dt) {
    $d = time() - strtotime($dt);
    if ($d < 60)    return $d . 's ago';
    if ($d < 3600)  return floor($d/60) . 'm ago';
    if ($d < 86400) return floor($d/3600) . 'h ago';
    return floor($d/86400) . 'd ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Marlani Admin - Dashboard</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="vendor/chart.js/Chart.min.js"></script>

<style>
:root {
  --blue:#002a66; --primary:#4e73df; --secondary:#858796;
  --success:#1cc88a; --info:#36b9cc; --warning:#f6c23e;
  --danger:#e74a3b; --light:#f8f9fc; --dark:#5a5c69;
}

*, *::before, *::after { box-sizing: border-box; }

html { font-family: sans-serif; line-height:1.15; -webkit-text-size-adjust:100%; position:relative; min-height:100%; }

body {
  margin:0;
  font-family:"Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  font-size:1rem; font-weight:400; line-height:1.5;
  color:#858796; text-align:left; background-color:#fff; height:100%;
}

h1,h2,h3,h4,h5,h6 { margin-top:0; margin-bottom:0.5rem; }
p { margin-top:0; margin-bottom:1rem; }
a { color:#4e73df; text-decoration:none; background-color:transparent; }
a:hover { color:#224abe; text-decoration:underline; }
img { vertical-align:middle; border-style:none; }
hr { margin-top:1rem; margin-bottom:1rem; border:0; border-top:1px solid rgba(0,0,0,0.1); }

#wrapper { display:flex; min-height:100vh; align-items:flex-start; }
#sidebar-container { display:flex; flex-shrink:0; height:100vh; position:sticky; top:0; align-self:flex-start; z-index:100; }
#content-wrapper { display:flex; flex-direction:column; flex:1 1 auto; width:auto; min-width:0; min-height:100vh; background-color:#f8f9fc; overflow-x:hidden; }
#content { flex:1 0 auto; display:flex; flex-direction:column; }
.container-fluid { width:100%; padding-right:1.5rem; padding-left:1.5rem; margin-right:auto; margin-left:auto; flex:1 0 auto; }

/* GRID */
.row { display:flex; flex-wrap:wrap; margin-right:-0.75rem; margin-left:-0.75rem; }
.no-gutters { margin-right:0; margin-left:0; }
.no-gutters > .col, .no-gutters > [class*="col-"] { padding-right:0; padding-left:0; }
.col-1,.col-2,.col-3,.col-4,.col-5,.col-6,.col-7,.col-8,.col-9,.col-10,.col-11,.col-12,.col,
.col-sm-1,.col-sm-2,.col-sm-3,.col-sm-4,.col-sm-5,.col-sm-6,.col-sm-7,.col-sm-8,.col-sm-9,.col-sm-10,.col-sm-11,.col-sm-12,.col-sm,
.col-md-1,.col-md-2,.col-md-3,.col-md-4,.col-md-5,.col-md-6,.col-md-7,.col-md-8,.col-md-9,.col-md-10,.col-md-11,.col-md-12,.col-md,
.col-lg-1,.col-lg-2,.col-lg-3,.col-lg-4,.col-lg-5,.col-lg-6,.col-lg-7,.col-lg-8,.col-lg-9,.col-lg-10,.col-lg-11,.col-lg-12,.col-lg,
.col-xl-1,.col-xl-2,.col-xl-3,.col-xl-4,.col-xl-5,.col-xl-6,.col-xl-7,.col-xl-8,.col-xl-9,.col-xl-10,.col-xl-11,.col-xl-12,.col-xl {
  position:relative; width:100%; padding-right:0.75rem; padding-left:0.75rem;
}
.col{flex-basis:0;flex-grow:1;max-width:100%}
.col-12{flex:0 0 100%;max-width:100%}
@media (min-width:576px){ .col-sm{flex-basis:0;flex-grow:1;max-width:100%} .col-sm-6{flex:0 0 50%;max-width:50%} .col-sm-12{flex:0 0 100%;max-width:100%} }
@media (min-width:768px){ .col-md{flex-basis:0;flex-grow:1;max-width:100%} .col-md-6{flex:0 0 50%;max-width:50%} .col-md-12{flex:0 0 100%;max-width:100%} }
@media (min-width:992px){ .col-lg{flex-basis:0;flex-grow:1;max-width:100%} .col-lg-6{flex:0 0 50%;max-width:50%} .col-lg-12{flex:0 0 100%;max-width:100%} }
@media (min-width:1200px){ .col-xl{flex-basis:0;flex-grow:1;max-width:100%} .col-xl-3{flex:0 0 25%;max-width:25%} .col-xl-12{flex:0 0 100%;max-width:100%} }

/* TOPBAR */
.navbar { position:relative; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; padding:0.5rem 1rem; }
.navbar-expand { flex-flow:row nowrap; justify-content:flex-start; }
.navbar-nav { display:flex; flex-direction:column; padding-left:0; margin-bottom:0; list-style:none; }
.navbar-expand .navbar-nav { flex-direction:row; }
.nav-link { display:block; padding:0.5rem 1rem; }
.nav-link:hover, .nav-link:focus { text-decoration:none; }
.navbar-light .navbar-nav .nav-link { color:rgba(0,0,0,0.5); }
.navbar-light .navbar-nav .nav-link:hover { color:rgba(0,0,0,0.7); }

.topbar { height:4.375rem; position:relative; z-index:10; }
.topbar .nav-item .nav-link {
    height:4.375rem; display:flex; align-items:center;
    padding:0 0.75rem; color:#d1d3e2; position:relative;
}
.topbar .nav-item .nav-link:hover { color:#b7b9cc; }
.topbar .nav-item .nav-link .img-profile { height:2rem; width:2rem; border-radius:50%; }
.topbar .topbar-divider { width:0; border-right:1px solid #e3e6f0; height:calc(4.375rem - 2rem); margin:auto 1rem; }
.topbar .dropdown-menu { right:0; left:auto; min-width:18rem; }

/* DROPDOWNS */
.dropdown { position:relative; }
.dropdown-toggle { white-space:nowrap; }
.dropdown-toggle::after {
    display:inline-block; margin-left:0.255em; vertical-align:0.255em;
    content:""; border-top:0.3em solid; border-right:0.3em solid transparent;
    border-bottom:0; border-left:0.3em solid transparent;
}
.dropdown-menu {
    position:absolute; top:100%; left:0; z-index:1000;
    display:none; float:left; min-width:10rem; padding:0.5rem 0;
    margin:0.125rem 0 0; font-size:0.85rem; color:#858796;
    text-align:left; list-style:none; background-color:#fff;
    background-clip:padding-box; border:1px solid #e3e6f0;
    border-radius:0.35rem;
}
.dropdown-menu.show { display:block; }
.dropdown-menu-right { right:0; left:auto; }
.dropdown-item {
    display:block; width:100%; padding:0.25rem 1.5rem; clear:both;
    font-weight:400; color:#3a3b45; text-align:inherit;
    white-space:nowrap; background-color:transparent; border:0;
    text-decoration:none;
}
.dropdown-item:hover { background-color:#eaecf4; text-decoration:none; }
.dropdown-divider { height:0; margin:0.5rem 0; overflow:hidden; border-top:1px solid #eaecf4; }
.dropdown-header { display:block; padding:0.5rem 1.5rem; margin-bottom:0; font-size:0.875rem; color:#858796; white-space:nowrap; }
.dropdown-list { padding:0; border:none; overflow:hidden; }
.dropdown-list .dropdown-header {
    background-color:#4e73df; border:1px solid #4e73df;
    padding-top:0.75rem; padding-bottom:0.75rem; color:#fff;
}
.dropdown-list .dropdown-item {
    white-space:normal; padding-top:0.5rem; padding-bottom:0.5rem;
    border-left:1px solid #e3e6f0; border-right:1px solid #e3e6f0;
    border-bottom:1px solid #e3e6f0; line-height:1.3rem;
}
.dropdown-list .dropdown-item .dropdown-list-image {
    position:relative; height:2.5rem; width:2.5rem;
}
.dropdown-list .dropdown-item .dropdown-list-image img {
    height:2.5rem; width:2.5rem; border-radius:50%;
}
.dropdown-list .dropdown-item .dropdown-list-image .status-indicator {
    background-color:#eaecf4; height:0.75rem; width:0.75rem;
    border-radius:100%; position:absolute; bottom:0; right:0;
    border:0.125rem solid #fff;
}
.status-indicator.bg-success { background-color:#1cc88a !important; }

.badge { display:inline-block; padding:0.25em 0.4em; font-size:75%; font-weight:700; line-height:1; text-align:center; white-space:nowrap; vertical-align:baseline; border-radius:0.35rem; }
.badge-danger { color:#fff; background-color:#e74a3b; }
.badge-active { background:#dcfce7; color:#16a34a; }
.badge-pending { background:#fef3c7; color:#d97706; }
.badge-inactive { background:#fee2e2; color:#dc2626; }
.badge-suspended { background:#f3f4f6; color:#6b7280; }
.badge-counter {
    position:absolute; transform:scale(0.7); transform-origin:top right;
    right:.25rem; margin-top:-.25rem;
}

.icon-circle { height:2.5rem; width:2.5rem; border-radius:100%; display:flex; align-items:center; justify-content:center; }
.bg-primary { background-color:#4e73df !important; }
.bg-success { background-color:#1cc88a !important; }
.bg-info { background-color:#36b9cc !important; }
.bg-warning { background-color:#f6c23e !important; }
.bg-danger { background-color:#e74a3b !important; }

.text-gray-500 { color:#b7b9cc !important; }
.text-gray-600 { color:#858796 !important; }
.text-gray-800 { color:#5a5c69 !important; }
.text-gray-300 { color:#dddfeb !important; }
.text-gray-400 { color:#d1d3e2 !important; }
.text-primary { color:#4e73df !important; }
.text-success { color:#1cc88a !important; }
.text-info { color:#36b9cc !important; }
.text-warning { color:#f6c23e !important; }
.text-danger { color:#e74a3b !important; }
.text-secondary { color:#858796 !important; }
.text-white { color:#fff !important; }
.text-uppercase { text-transform:uppercase !important; }
.font-weight-bold { font-weight:700 !important; }
.text-xs { font-size:.7rem; }
.small { font-size:80%; font-weight:400; }
.text-truncate { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.text-center { text-align:center !important; }

.d-flex { display:flex !important; }
.d-none { display:none !important; }
.d-md-none { display:none !important; }
.d-md-inline { display:inline !important; }
.d-lg-inline { display:inline !important; }
.d-sm-block { display:block !important; }
.align-items-center { align-items:center !important; }
.justify-content-between { justify-content:space-between !important; }
.flex-row { flex-direction:row !important; }
.flex-column { flex-direction:column !important; }
.mr-2 { margin-right:0.5rem !important; }
.mr-3 { margin-right:1rem !important; }
.mb-0 { margin-bottom:0 !important; }
.mb-1 { margin-bottom:0.25rem !important; }
.mb-2 { margin-bottom:0.5rem !important; }
.mb-4 { margin-bottom:1.5rem !important; }
.mt-4 { margin-top:1.5rem !important; }
.mx-1 { margin-left:0.25rem !important; margin-right:0.25rem !important; }
.ml-auto { margin-left:auto !important; }
.my-0 { margin-top:0 !important; margin-bottom:0 !important; }
.py-2 { padding-top:0.5rem !important; padding-bottom:0.5rem !important; }
.py-3 { padding-top:1rem !important; padding-bottom:1rem !important; }
.p-0 { padding:0 !important; }
.pt-4 { padding-top:1.5rem !important; }
.pb-2 { padding-bottom:0.5rem !important; }
.h-100 { height:100% !important; }
.w-100 { width:100% !important; }
.rounded-circle { border-radius:50% !important; }
.shadow { box-shadow:0 0.15rem 1.75rem 0 rgba(58,59,69,0.15) !important; }

@media (max-width:576px){ .d-sm-block { display:none !important; } }
@media (min-width:576px){ .d-sm-block { display:block !important; } }
@media (min-width:768px){ .d-md-none { display:none !important; } .d-md-inline { display:inline !important; } }
@media (min-width:992px){ .d-lg-inline { display:inline !important; } }

/* CARDS */
.card { position:relative; display:flex; flex-direction:column; min-width:0; word-wrap:break-word; background-color:#fff; background-clip:border-box; border:1px solid #e3e6f0; border-radius:0.35rem; }
.card-body { flex:1 1 auto; min-height:1px; padding:1.25rem; }
.card-header { padding:0.75rem 1.25rem; margin-bottom:0; background-color:#f8f9fc; border-bottom:1px solid #e3e6f0; }
.card-header:first-child { border-radius:calc(0.35rem - 1px) calc(0.35rem - 1px) 0 0; }
.border-left-primary { border-left:0.25rem solid #4e73df !important; }
.border-left-success { border-left:0.25rem solid #1cc88a !important; }
.border-left-info { border-left:0.25rem solid #36b9cc !important; }
.border-left-warning { border-left:0.25rem solid #f6c23e !important; }
.border-left-danger { border-left:0.25rem solid #e74a3b !important; }
.border-left-secondary { border-left:0.25rem solid #858796 !important; }

.table { width:100%; margin-bottom:1rem; color:#858796; border-collapse:collapse; }
.table th, .table td { padding:0.75rem; vertical-align:top; border-top:1px solid #e3e6f0; }
.table thead th { vertical-align:bottom; border-bottom:2px solid #e3e6f0; }
.table-bordered { border:1px solid #e3e6f0; }
.table-bordered th, .table-bordered td { border:1px solid #e3e6f0; }
.table-responsive { display:block; width:100%; overflow-x:auto; -webkit-overflow-scrolling:touch; }

.chart-pie { position:relative; height:15rem; width:100%; }
.chart-pie canvas { width:100% !important; height:100% !important; max-height:100%; }

.h3 { font-size:1.75rem; font-weight:400; line-height:1.2; }
.h5 { font-size:1.25rem; font-weight:400; line-height:1.2; }
.h6 { font-size:1rem; font-weight:400; line-height:1.2; }

footer.sticky-footer {
    margin-top:auto; width:100%; height:36px; min-height:36px;
    padding:8px 0; background:#002a66; color:#fff;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
footer.sticky-footer .copyright { margin:0; font-size:12px; line-height:1; }

@media (max-width:768px){
    #sidebar-container { position:fixed !important; top:0 !important; left:0 !important;
        height:100vh !important; z-index:1050 !important; width:auto !important;
        overflow:visible !important; align-self:auto !important; }
}
</style>
</head>
<body id="page-top">
    <div id="wrapper">
        <div id="sidebar-container"></div>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">

                <!-- ==================== TOPBAR ==================== -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h1 class="h3 mb-0 text-gray-800">
                        Welcome, <?php echo htmlspecialchars($first_name); ?>!
                    </h1>

                    <ul class="navbar-nav ml-auto">

                        <!-- ============ MESSAGES ============ -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown"
                               role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-envelope fa-fw"></i>
                                <?php if ($unread_messages > 0): ?>
                                    <span class="badge badge-danger badge-counter">
                                        <?php echo $unread_messages > 9 ? '9+' : $unread_messages; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                 aria-labelledby="messagesDropdown">
                                <h6 class="dropdown-header">Message Center</h6>

                                <?php if (empty($messages)): ?>
                                    <div class="dropdown-item text-center small text-gray-500">
                                        No messages yet
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($messages as $m):
                                        $sender_name = trim($m['first_name'] . ' ' . $m['last_name']);
                                        $initial     = strtoupper(substr($m['first_name'], 0, 1));
                                    ?>
                                        <a class="dropdown-item d-flex align-items-center"
                                           href="Messages/message-view.php?with=<?php echo (int)$m['sender_id']; ?>">
                                            <div class="dropdown-list-image mr-3">
                                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                                     style="width:2.5rem;height:2.5rem;font-weight:700;">
                                                    <?php echo htmlspecialchars($initial); ?>
                                                </div>
                                                <?php if (!$m['is_read']): ?>
                                                    <div class="status-indicator bg-success"></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="<?php echo $m['is_read'] ? '' : 'font-weight-bold'; ?>">
                                                <div class="text-truncate">
                                                    <?php echo htmlspecialchars($m['body']); ?>
                                                </div>
                                                <div class="small text-gray-500">
                                                    <?php echo htmlspecialchars($sender_name); ?>
                                                    · <?php echo time_ago($m['created_at']); ?>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <a class="dropdown-item text-center small text-gray-500"
                                   href="Messages/messages.php">
                                    Read More Messages
                                </a>
                                <a class="dropdown-item text-center small text-primary font-weight-bold"
                                   href="Messages/messages.php">
                                    <i class="fas fa-pen"></i> New Message
                                </a>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- ============ USER ============ -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown"
                               role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="img/undraw_profile.svg" alt="profile">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                 aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile
                                </a>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cogs fa-sm fa-fw mr-2 text-gray-400"></i> Settings
                                </a>
                                <a class="dropdown-item" href="activity.php">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i> Activity Log
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- ==================== /TOPBAR ==================== -->

                <div class="container-fluid">

                    <!-- STAT CARDS -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Employees</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_staff; ?></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Clients</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_clients; ?></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-building fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Quotes</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_quotes; ?></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-file-invoice fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">New This Week</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $new_count; ?></div>
                                        </div>
                                        <div class="col-auto"><i class="fas fa-user-plus fa-2x text-gray-300"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CLIENT STATUS -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Client Status Overview</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-primary shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Clients</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $active_clients; ?></div>
                                                        </div>
                                                        <div class="col-auto"><i class="fas fa-check-circle fa-2x" style="color:#4e73df;"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-warning shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Clients</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_clients; ?></div>
                                                        </div>
                                                        <div class="col-auto"><i class="fas fa-clock fa-2x" style="color:#f6c23e;"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-danger shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Inactive Clients</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $inactive_clients; ?></div>
                                                        </div>
                                                        <div class="col-auto"><i class="fas fa-times-circle fa-2x" style="color:#e74a3b;"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-md-6 mb-4">
                                            <div class="card border-left-secondary shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Suspended</div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $suspended_clients; ?></div>
                                                        </div>
                                                        <div class="col-auto"><i class="fas fa-ban fa-2x" style="color:#858796;"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="chart-pie pt-4 pb-2" style="height:300px;min-height:300px;width:100%;">
                                        <canvas id="clientPieChart"></canvas>
                                    </div>
                                    <div class="mt-4 text-center small">
                                        <span class="mr-2"><i class="fas fa-circle" style="color:#4e73df;"></i> Active</span>
                                        <span class="mr-2"><i class="fas fa-circle" style="color:#f6c23e;"></i> Pending</span>
                                        <span class="mr-2"><i class="fas fa-circle" style="color:#e74a3b;"></i> Inactive</span>
                                        <span class="mr-2"><i class="fas fa-circle" style="color:#858796;"></i> Suspended</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RECENT STAFF / CLIENTS -->
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Staff Registrations</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead><tr><th>Name</th><th>Email</th><th>Registered</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($recent_staff as $staff): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($staff['created_at'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Clients</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead><tr><th>Company</th><th>Contact</th><th>Status</th><th>Registered</th></tr></thead>
                                            <tbody>
                                                <?php foreach ($recent_clients as $client): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($client['company_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($client['contact_person'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $client['status']; ?>">
                                                            <?php echo ucfirst($client['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($client['created_at'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- /.container-fluid -->

            </div><!-- /#content -->

            <footer class="sticky-footer bg-blue">
                <div class="copyright text-center">
                    <span>Copyright &copy; Marlani Technologies <?php echo date('Y'); ?></span>
                </div>
            </footer>
        </div><!-- /#content-wrapper -->
    </div><!-- /#wrapper -->

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>

<script>
$(function() {
    /* Load sidebar */
    $("#sidebar-container").load("sidebar.php", function(response, status, xhr) {
        if (status === "error") {
            console.error("Sidebar load failed:", xhr.status, xhr.statusText);
        }
    });

    /* Topbar dropdowns (works even without Bootstrap JS) */
    $(document).on('click', '[data-toggle="dropdown"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $menu = $(this).next('.dropdown-menu');
        if (!$menu.length) $menu = $(this).siblings('.dropdown-menu');
        var isOpen = $menu.hasClass('show');
        $('.dropdown-menu.show').removeClass('show');
        if (!isOpen) $menu.addClass('show');
    });

    $(document).on('click', function() {
        $('.dropdown-menu.show').removeClass('show');
    });

    $(document).on('click', '.dropdown-menu', function(e) {
        e.stopPropagation();
    });

    /* Mobile topbar toggle */
    $('#sidebarToggleTop').on('click', function() {
        var $sidebar = $('.sidebar');
        if (!$sidebar.length) return;
        $sidebar.toggleClass('toggled');
        if ($sidebar.hasClass('toggled')) {
            if (!$('.sidebar-overlay').length) {
                $('body').append('<div class="sidebar-overlay active"></div>');
            }
        } else {
            $('.sidebar-overlay').remove();
        }
    });

    $(document).on('click', '.sidebar-overlay', function() {
        $('.sidebar').removeClass('toggled');
        $('.sidebar-overlay').remove();
    });
});

/* Client Pie Chart */
var ctx = document.getElementById("clientPieChart");
if (ctx) {
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ["Active", "Pending", "Inactive", "Suspended"],
            datasets: [{
                data: [
                    <?php echo (int)$active_clients; ?>,
                    <?php echo (int)$pending_clients; ?>,
                    <?php echo (int)$inactive_clients; ?>,
                    <?php echo (int)$suspended_clients; ?>
                ],
                backgroundColor: ['#4e73df', '#f6c23e', '#e74a3b', '#858796'],
                hoverBackgroundColor: ['#2e59d9', '#dda20a', '#c0392b', '#6b6d7d'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: { display: false },
            cutoutPercentage: 80,
        },
    });
}
</script>
</body>
</html>