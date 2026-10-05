<?php
// view-staff.php - View All Staff Members
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

// Include database connection
require_once '../db.php';

// Get user data from session
$current_staff_id = $_SESSION['staff_id'] ?? 1;
$first_name = $_SESSION['first_name'] ?? 'User';

// Flash message
$success_message = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// ===== SEARCH & FILTER PARAMETERS =====
$search     = trim($_GET['search'] ?? '');
$status     = trim($_GET['status'] ?? '');
$staff_type = trim($_GET['staff_type'] ?? '');
$sort       = trim($_GET['sort'] ?? 'newest');
$page       = max(1, intval($_GET['page'] ?? 1));
$per_page   = 10;
$offset     = ($page - 1) * $per_page;

// ===== BUILD QUERY =====
$where = ["status != 'terminated'"];
$params = [];

if (!empty($search)) {
    $where[] = "(first_name LIKE :search 
              OR last_name LIKE :search 
              OR CONCAT(first_name, ' ', last_name) LIKE :search 
              OR email LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($status)) {
    $where[] = "status = :status";
    $params[':status'] = $status;
}

if (!empty($staff_type)) {
    $where[] = "staff_type = :staff_type";
    $params[':staff_type'] = $staff_type;
}

$where_sql = implode(' AND ', $where);

// Sort options
$sort_map = [
    'newest'    => 'id DESC',
    'oldest'    => 'id ASC',
    'name_asc'  => 'first_name ASC, last_name ASC',
    'name_desc' => 'first_name DESC, last_name DESC',
];
$order_by = $sort_map[$sort] ?? 'id DESC';

// ===== COUNT TOTAL ROWS =====
$count_sql = "SELECT COUNT(*) FROM staff WHERE $where_sql";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_rows = (int) $stmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));

// ===== FETCH STAFF =====
$sql = "SELECT id, first_name, last_name, email, phone_no, staff_type, status, created_at
        FROM staff 
        WHERE $where_sql 
        ORDER BY $order_by 
        LIMIT $per_page OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== STATS CARDS =====
$stats = [
    'total'     => (int) $conn->query("SELECT COUNT(*) FROM staff WHERE status != 'terminated'")->fetchColumn(),
    'active'    => (int) $conn->query("SELECT COUNT(*) FROM staff WHERE status = 'active'")->fetchColumn(),
    'inactive'  => (int) $conn->query("SELECT COUNT(*) FROM staff WHERE status = 'inactive'")->fetchColumn(),
    'suspended' => (int) $conn->query("SELECT COUNT(*) FROM staff WHERE status = 'suspended'")->fetchColumn(),
    'terminated'=> (int) $conn->query("SELECT COUNT(*) FROM staff WHERE status = 'terminated'")->fetchColumn(),
];

// ===== GET DISTINCT STAFF TYPES FOR FILTER =====
$staff_types = $conn->query("SELECT DISTINCT staff_type FROM staff WHERE staff_type IS NOT NULL AND staff_type != '' ORDER BY staff_type")->fetchAll(PDO::FETCH_COLUMN);

// ===== HELPER: BUILD QUERY STRING =====
function buildQuery($overrides = []) {
    $params = array_merge($_GET, $overrides);
    return http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Marlani Admin - View Staff</title>

    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #4e73df;
            --success: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --info: #36b9cc;
        }
        
        body {
            font-family: "Nunito", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8f9fc;
            color: #858796;
        }
        
        /* ===== LAYOUT ===== */
        #wrapper { display: flex; min-height: 100vh; width: 100%; }
        #sidebar-container { 
            display: flex; 
            flex-shrink: 0; 
            height: 100vh; 
            position: sticky; 
            top: 0; 
            align-self: flex-start;
            width: 14rem;
        }
        .sidebar { 
            position: relative !important; 
            height: 100vh !important; 
            width: 14rem !important; 
            display: flex !important; 
            flex-direction: column !important; 
            box-shadow: 2px 0 15px rgba(0,0,0,0.15); 
        }
        #content-wrapper { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; }
        #content { flex: 1 0 auto; display: flex; flex-direction: column; }
        
        @media (min-width: 768px) { 
            .sidebar { width: 14rem !important; } 
        }
        @media (max-width: 768px) {
            #sidebar-container { 
                position: fixed !important; 
                top: 0; left: 0; 
                height: 100vh; 
                z-index: 1050; 
                width: 0; 
                overflow: hidden; 
                transition: width 0.3s ease; 
            }
            #sidebar-container.toggled { width: 14rem !important; }
            .sidebar { width: 100% !important; }
        }
        
        footer.sticky-footer {
            margin-top: auto;
            width: 100%;
            height: 36px;
            min-height: 36px;
            padding: 8px 0;
            background: #002a66;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        footer.sticky-footer .copyright { margin: 0; font-size: 12px; line-height: 1; }
        
        .sidebar-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1040; display: none;
        }
        .sidebar-overlay.active { display: block; }
        
        /* Success alert */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* ===== STATS CARDS ===== */
        .stat-card {
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
            background: #fff;
            border-left: 4px solid var(--primary);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-left-color 0.2s ease;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2); }
        .stat-card.active    { border-left-color: var(--success); }
        .stat-card.inactive  { border-left-color: var(--warning); }
        .stat-card.suspended { border-left-color: var(--danger); }
        .stat-card.terminated{ border-left-color: #858796; }
        
        .stat-card .stat-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #858796;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #5a5c69;
            line-height: 1;
        }
        .stat-card .stat-icon {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            background: #eaecf4;
            color: var(--primary);
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        
        /* Default icon colors */
        .stat-card.active .stat-icon     { background: #d4f5e7; color: var(--success); }
        .stat-card.inactive .stat-icon   { background: #fdf1d6; color: var(--warning); }
        .stat-card.suspended .stat-icon  { background: #fbe0dd; color: var(--danger); }
        .stat-card.terminated .stat-icon { background: #ececec; color: #bd0606; }
        
        /* ✅ HOVER: icon becomes solid-colored with white glyph */
        .stat-card:hover .stat-icon                          { background: var(--primary); color: #fff; }
        .stat-card.active:hover .stat-icon                   { background: var(--success); color: #fff; }
        .stat-card.inactive:hover .stat-icon                 { background: var(--warning); color: #fff; }
        .stat-card.suspended:hover .stat-icon                { background: var(--danger);  color: #fff; }
        .stat-card.terminated:hover .stat-icon               { background: #e74a3b;        color: #fff; }
        
        /* ✅ HOVER: left-border accent turns red for terminated */
        .stat-card.terminated:hover                          { border-left-color: #e74a3b; }
        
        /* ============================================================
           ✅ TOP PAGE-HEADING BUTTONS
           Solid colour by default, darker shade on hover
           White text + icon in every state
           ============================================================ */
        
        /* Smooth transitions */
        .d-sm-flex .btn,
        .d-sm-flex .btn i,
        .d-sm-flex .btn span {
            transition: background-color 0.2s ease, 
                        border-color 0.2s ease, 
                        color 0.2s ease, 
                        transform 0.2s ease, 
                        box-shadow 0.2s ease;
        }
        
        /* ---------- Back to Dashboard — solid blue ---------- */
        .btn-dashboard {
            color: #ffffff !important;
            background-color: #4e73df !important;
            border-color: #4e73df !important;
        }
        .btn-dashboard i,
        .btn-dashboard span {
            color: #ffffff !important;
        }
        .btn-dashboard:hover,
        .btn-dashboard:focus,
        .btn-dashboard:active,
        .btn-dashboard.active {
            background-color: #2e59d9 !important;
            border-color: #2653d4 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(78, 115, 223, 0.4) !important;
            transform: translateY(-1px);
        }
        .btn-dashboard:hover i,
        .btn-dashboard:hover span,
        .btn-dashboard:focus i,
        .btn-dashboard:focus span {
            color: #ffffff !important;
        }
        
        /* ---------- Terminated Staff — solid red ---------- */
        .btn-terminated {
            color: #ffffff !important;
            background-color: #e74a3b !important;
            border-color: #e74a3b !important;
        }
        .btn-terminated i,
        .btn-terminated span {
            color: #ffffff !important;
        }
        .btn-terminated:hover,
        .btn-terminated:focus,
        .btn-terminated:active,
        .btn-terminated.active {
            background-color: #c0392b !important;
            border-color: #b03024 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(231, 74, 59, 0.4) !important;
            transform: translateY(-1px);
        }
        .btn-terminated:hover i,
        .btn-terminated:hover span,
        .btn-terminated:focus i,
        .btn-terminated:focus span {
            color: #ffffff !important;
        }
        
        /* ---------- Add New Staff — solid green ---------- */
        .btn-add-staff {
            color: #ffffff !important;
            background-color: #1cc88a !important;
            border-color: #1cc88a !important;
        }
        .btn-add-staff i,
        .btn-add-staff span {
            color: #ffffff !important;
        }
        .btn-add-staff:hover,
        .btn-add-staff:focus,
        .btn-add-staff:active,
        .btn-add-staff.active {
            background-color: #17a673 !important;
            border-color: #169b6b !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(28, 200, 138, 0.4) !important;
            transform: translateY(-1px);
        }
        .btn-add-staff:hover i,
        .btn-add-staff:hover span,
        .btn-add-staff:focus i,
        .btn-add-staff:focus span {
            color: #ffffff !important;
        }
        
        /* Toolbar */
        .toolbar {
            background: #fff;
            border-radius: 0.5rem;
            padding: 1rem 1.25rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.25rem;
        }
        .toolbar .form-control,
        .toolbar .btn {
            border-radius: 0.35rem;
            font-size: 0.9rem;
        }
        .toolbar .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        /* Table */
        .staff-table {
            background: #fff;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        .staff-table table { margin-bottom: 0; }
        .staff-table thead th {
            background: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: #5a5c69;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.9rem 1rem;
            white-space: nowrap;
        }
        .staff-table tbody td {
            padding: 0.9rem 1rem;
            vertical-align: middle;
            border-top: 1px solid #e3e6f0;
            font-size: 0.9rem;
            color: #5a5c69;
        }
        .staff-table tbody tr:hover { background: #f8f9fc; }
        .staff-table tbody tr:last-child td { border-bottom: none; }
        
        /* Avatar */
        .staff-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        
        .staff-name { font-weight: 700; color: #3a3b45; }
        .staff-meta { font-size: 0.8rem; color: #858796; }
        
        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.7rem;
            border-radius: 2rem;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .status-badge::before {
            content: '';
            width: 0.45rem;
            height: 0.45rem;
            border-radius: 50%;
            background: currentColor;
        }
        .status-active    { background: #d4f5e7; color: #0f7552; }
        .status-inactive  { background: #fdf1d6; color: #a07b12; }
        .status-suspended { background: #fbe0dd; color: #5c0b38; }
        .status-terminated{ background: #fbe0dd; color: #b32b1e; }
        
        /* Action buttons */
        .btn-action {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }
        .btn-action:hover { transform: translateY(-2px); }
        .btn-action-view { background: #e7f1ff; color: #4e73df; }
        .btn-action-view:hover { background: #4e73df; color: #fff; }
        .btn-action-edit { background: #fdf1d6; color: #a07b12; }
        .btn-action-edit:hover { background: #f6c23e; color: #fff; }
        .btn-action-terminate { background: #fbe0dd; color: #b32b1e; }
        .btn-action-terminate:hover { background: #e74a3b; color: #fff; }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #858796;
        }
        .empty-state i {
            font-size: 3rem;
            color: #d1d3e2;
            margin-bottom: 1rem;
            display: block;
        }
        .empty-state h5 { color: #5a5c69; margin-bottom: 0.5rem; }
        
        /* Pagination */
        .pagination .page-link {
            color: #4e73df;
            border-color: #e3e6f0;
            font-size: 0.85rem;
            padding: 0.4rem 0.75rem;
        }
        .pagination .page-item.active .page-link {
            background-color: #4e73df;
            border-color: #4e73df;
            color: #fff;
        }
        .pagination .page-link:hover {
            background-color: #eaecf4;
            color: #224abe;
        }
        .pagination .page-item.disabled .page-link {
            color: #b7b9cc;
        }
        
        /* Filter chips */
        .filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #e7f1ff;
            color: #4e73df;
            padding: 0.25rem 0.6rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.35rem;
            margin-bottom: 0.35rem;
        }
        .filter-chip a { color: inherit; text-decoration: none; }
        .filter-chip a:hover { color: #e74a3b; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stat-card { padding: 0.85rem 1rem; }
            .stat-card .stat-value { font-size: 1.25rem; }
            .staff-table thead { display: none; }
            .staff-table tbody td { display: block; padding: 0.5rem 1rem; border: none; }
            .staff-table tbody tr { display: block; border-bottom: 1px solid #e3e6f0; padding: 0.75rem 0; }
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <!-- Sidebar container (loaded via JS from ../sidebar.php) -->
        <div id="sidebar-container"></div>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h1 class="h3 mb-0 text-gray-800">
                        <i class="fas fa-users"></i> Staff Management
                    </h1>

                    <ul class="navbar-nav ml-auto">
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($first_name); ?>
                                </span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                                <a class="dropdown-item" href="../logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div>
                            <a href="../index.php" class="btn btn-sm btn-dashboard shadow-sm">
                                <i class="fas fa-fw fa-tachometer-alt"></i> Back to Dashboard
                            </a>
                        </div>
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <a href="view-terminated-staff.php" class="btn btn-sm btn-terminated shadow-sm">
                                <i class="fas fa-user-slash"></i> Terminated Staff
                            </a>
                            <a href="staff-add.php" class="btn btn-sm btn-add-staff shadow-sm">
                                <i class="fas fa-user-plus"></i> Add New Staff
                            </a>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <?php if (!empty($success_message)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card">
                                <div>
                                    <div class="stat-label">Total Staff</div>
                                    <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-users"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card active">
                                <div>
                                    <div class="stat-label">Active</div>
                                    <div class="stat-value"><?php echo number_format($stats['active']); ?></div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card inactive">
                                <div>
                                    <div class="stat-label">Inactive</div>
                                    <div class="stat-value"><?php echo number_format($stats['inactive']); ?></div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="stat-card suspended">
                                <div>
                                    <div class="stat-label">Suspended</div>
                                    <div class="stat-value"><?php echo number_format($stats['suspended']); ?></div>
                                </div>
                                <div class="stat-icon"><i class="fas fa-user-lock"></i></div>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar: Search & Filters -->
                    <div class="toolbar">
                        <form method="GET" action="view-staff.php" id="filterForm">
                            <div class="row">
                                <div class="col-lg-5 col-md-6 mb-2">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white border-right-0">
                                                <i class="fas fa-search text-gray-400"></i>
                                            </span>
                                        </div>
                                        <input type="text" 
                                               class="form-control border-left-0" 
                                               name="search" 
                                               value="<?php echo htmlspecialchars($search); ?>"
                                               placeholder="Search by name, email...">
                                    </div>
                                </div>
                                
                                <div class="col-lg-2 col-md-3 col-6 mb-2">
                                    <select class="form-control" name="status">
                                        <option value="">All Status</option>
                                        <option value="active"     <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive"   <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="suspended"  <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                                
                                <div class="col-lg-2 col-md-3 col-6 mb-2">
                                    <select class="form-control" name="staff_type">
                                        <option value="">All Types</option>
                                        <?php foreach ($staff_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" 
                                                <?php echo $staff_type === $type ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-lg-2 col-md-3 col-6 mb-2">
                                    <select class="form-control" name="sort">
                                        <option value="newest"    <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                        <option value="oldest"    <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                        <option value="name_asc"  <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name A–Z</option>
                                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name Z–A</option>
                                    </select>
                                </div>
                                
                                <div class="col-lg-1 col-md-3 col-6 mb-2">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-filter"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Active Filter Chips -->
                            <?php if (!empty($search) || !empty($status) || !empty($staff_type)): ?>
                            <div class="mt-2">
                                <span class="small text-gray-600 mr-2">Active filters:</span>
                                <?php if (!empty($search)): ?>
                                <span class="filter-chip">
                                    <i class="fas fa-search"></i> "<?php echo htmlspecialchars($search); ?>"
                                    <a href="?<?php echo buildQuery(['search' => '', 'page' => 1]); ?>"><i class="fas fa-times"></i></a>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($status)): ?>
                                <span class="filter-chip">
                                    <i class="fas fa-info-circle"></i> <?php echo ucfirst(htmlspecialchars($status)); ?>
                                    <a href="?<?php echo buildQuery(['status' => '', 'page' => 1]); ?>"><i class="fas fa-times"></i></a>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($staff_type)): ?>
                                <span class="filter-chip">
                                    <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($staff_type); ?>
                                    <a href="?<?php echo buildQuery(['staff_type' => '', 'page' => 1]); ?>"><i class="fas fa-times"></i></a>
                                </span>
                                <?php endif; ?>
                                <a href="view-staff.php" class="small text-danger ml-2">
                                    <i class="fas fa-times-circle"></i> Clear all
                                </a>
                            </div>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Staff Table -->
                    <div class="staff-table">
                        <?php if (empty($staff_members)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-slash"></i>
                            <h5>No staff members found</h5>
                            <p class="mb-3">
                                <?php if (!empty($search) || !empty($status) || !empty($staff_type)): ?>
                                    Try adjusting your search or filter criteria.
                                <?php else: ?>
                                    Get started by adding your first staff member.
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($search) || !empty($status) || !empty($staff_type)): ?>
                                <a href="view-staff.php" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-times"></i> Clear Filters
                                </a>
                            <?php else: ?>
                                <a href="staff-add.php" class="btn btn-sm btn-add-staff">
                                    <i class="fas fa-user-plus"></i> Add Staff
                                </a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Staff Member</th>
                                        <th>Contact</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Added</th>
                                        <th class="text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff_members as $staff): 
                                        $initials = strtoupper(
                                            substr($staff['first_name'], 0, 1) . 
                                            substr($staff['last_name'] ?? '', 0, 1)
                                        );
                                        $status_class = 'status-' . $staff['status'];
                                        $created = !empty($staff['created_at']) 
                                            ? date('d M Y', strtotime($staff['created_at'])) 
                                            : '—';
                                        $is_self = ($staff['id'] == $current_staff_id);
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                                <div class="staff-avatar"><?php echo $initials; ?></div>
                                                <div>
                                                    <div class="staff-name">
                                                        <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                                        <?php if ($is_self): ?>
                                                            <span class="badge badge-info" style="font-size:0.65rem;">You</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="staff-meta">ID: #<?php echo str_pad($staff['id'], 4, '0', STR_PAD_LEFT); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div><i class="fas fa-envelope text-gray-400 mr-1"></i> <?php echo htmlspecialchars($staff['email']); ?></div>
                                            <?php if (!empty($staff['phone_no'])): ?>
                                            <div class="staff-meta"><i class="fas fa-phone text-gray-400 mr-1"></i> <?php echo htmlspecialchars($staff['phone_no']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($staff['staff_type'])): ?>
                                                <span class="badge badge-light border">
                                                    <?php echo htmlspecialchars($staff['staff_type']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($staff['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="staff-meta"><?php echo $created; ?></span>
                                        </td>
                                        <td class="text-right">
                                            <div style="display:inline-flex; gap:0.35rem;">
                                                <a href="staff-view.php?id=<?php echo $staff['id']; ?>" 
                                                   class="btn-action btn-action-view" 
                                                   title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="staff-edit.php?id=<?php echo $staff['id']; ?>" 
                                                   class="btn-action btn-action-edit" 
                                                   title="Edit Staff">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <?php if (!$is_self): ?>
                                                <a href="staff-remove.php?select_id=<?php echo $staff['id']; ?>" 
                                                   class="btn-action btn-action-terminate" 
                                                   title="Terminate Staff">
                                                    <i class="fas fa-user-slash"></i>
                                                </a>
                                                <?php else: ?>
                                                <button class="btn-action btn-action-terminate" disabled 
                                                        title="You cannot terminate your own account"
                                                        style="opacity:0.4; cursor:not-allowed;">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination & Result count -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between p-3 border-top">
                            <div class="small text-gray-600 mb-2 mb-sm-0">
                                Showing 
                                <strong><?php echo $offset + 1; ?></strong> 
                                to 
                                <strong><?php echo min($offset + $per_page, $total_rows); ?></strong> 
                                of 
                                <strong><?php echo number_format($total_rows); ?></strong> 
                                staff members
                            </div>
                            
                            <?php if ($total_pages > 1): ?>
                            <nav>
                                <ul class="pagination pagination-sm mb-0">
                                    <!-- Previous -->
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo buildQuery(['page' => $page - 1]); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <!-- Page numbers -->
                                    <?php 
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    
                                    if ($start > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo buildQuery(['page' => 1]); ?>">1</a>
                                        </li>
                                        <?php if ($start > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo buildQuery(['page' => $i]); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end < $total_pages): ?>
                                        <?php if ($end < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">…</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo buildQuery(['page' => $total_pages]); ?>">
                                                <?php echo $total_pages; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Next -->
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo buildQuery(['page' => $page + 1]); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                    
                    <!-- Terminated Staff Link -->
                    <?php if ($stats['terminated'] > 0): ?>
                    <div class="text-center mt-4">
                        <a href="view-terminated-staff.php" class="small text-gray-600">
                            <i class="fas fa-user-slash"></i> 
                            View <?php echo $stats['terminated']; ?> terminated staff 
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Footer -->
                <footer class="sticky-footer bg-blue">
                    <div class="copyright text-center">
                        <span>Copyright &copy; Marlani Technologies 2026</span>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="../js/sb-admin-2.min.js"></script>

    <script>
    // ============================================================
    // Load sidebar from external file with fallback
    // ============================================================
    $(function() {
        $("#sidebar-container").load("../sidebar.php", function(response, status) {
            if (status === "error") {
                console.warn("Sidebar file failed to load. Using inline fallback.");
                $("#sidebar-container").html(getFallbackSidebar());
            } else {
                console.log("Sidebar loaded successfully");
            }
            bindSidebarEvents();
        });
    });

    // ============================================================
    // Inline fallback sidebar (only used if ../sidebar.php missing)
    // ============================================================
    function getFallbackSidebar() {
        return `
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="../index.php">
                <div class="sidebar-brand-text mx-3">Marlani Admin</div>
            </a>
            <hr class="sidebar-divider my-0">
            <li class="nav-item">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Management</div>
            <li class="nav-item">
                <a class="nav-link" href="view-staff.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Staff</span>
                </a>
            </li>
        </ul>`;
    }

    // ============================================================
    // Bind sidebar toggle & overlay behaviour
    // ============================================================
    function bindSidebarEvents() {
        // Mobile topbar hamburger — toggles the sidebar container
        $('#sidebarToggleTop').off('click').on('click', function(e) {
            e.preventDefault();
            $('#sidebar-container').toggleClass('toggled');

            if ($('#sidebar-container').hasClass('toggled')) {
                if (!$('.sidebar-overlay').length) {
                    $('body').append('<div class="sidebar-overlay active"></div>');
                } else {
                    $('.sidebar-overlay').addClass('active');
                }
            } else {
                $('.sidebar-overlay').removeClass('active');
            }
        });

        // Click overlay to close
        $(document).off('click', '.sidebar-overlay').on('click', '.sidebar-overlay', function() {
            $('#sidebar-container').removeClass('toggled');
            $('.sidebar-overlay').removeClass('active');
        });

        // Desktop sidebar toggle button inside sidebar.php
        $('#sidebarToggle').off('click').on('click', function(e) {
            e.preventDefault();
            $('body').toggleClass('sidebar-toggled');
            $('.sidebar').toggleClass('toggled');
        });
    }
    </script>
</body>
</html>