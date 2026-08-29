<?php
// client-reports.php - View Previously Generated Reports
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

require_once '../db.php';

$first_name = $_SESSION['first_name'] ?? 'User';
$role = $_SESSION['role'] ?? 'Client';

// Reports directory
$reports_dir = __DIR__ . '/reports/';

// Create reports directory if it doesn't exist
if (!is_dir($reports_dir)) {
    mkdir($reports_dir, 0755, true);
}

// Get all report files
$report_files = [];
if (is_dir($reports_dir)) {
    $files = scandir($reports_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'html') {
            $filepath = $reports_dir . $file;
            $report_files[] = [
                'filename' => $file,
                'filepath' => $filepath,
                'url' => 'reports/' . $file,
                'size' => filesize($filepath),
                'created' => filemtime($filepath),
                'date' => date('Y-m-d H:i:s', filemtime($filepath))
            ];
        }
    }
    // Sort by creation date (newest first)
    usort($report_files, function($a, $b) {
        return $b['created'] - $a['created'];
    });
}

// Delete report if requested
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = $reports_dir . $filename;
    if (file_exists($filepath) && is_file($filepath)) {
        unlink($filepath);
        $_SESSION['report_message'] = 'Report deleted successfully!';
        $_SESSION['report_message_type'] = 'success';
    } else {
        $_SESSION['report_message'] = 'Report not found!';
        $_SESSION['report_message_type'] = 'danger';
    }
    header('Location: client-reports.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Marlani Admin - Client Reports</title>

    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        /* ===== SIDEBAR LOADING FIXES - ORIGINAL STYLE ===== */
        #sidebar-container {
            min-height: 100vh;
            position: sticky;
            top: 0;
            align-self: flex-start;
        }

        #sidebar-container .sidebar {
            min-height: 100vh;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner {
            background: transparent !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-item {
            color: rgba(255,255,255,0.7) !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-item:hover {
            background: rgba(255,255,255,0.08) !important;
            color: #fff !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-item.active {
            background: rgba(255,255,255,0.15) !important;
            color: #fff !important;
        }

        #sidebar-container .sidebar .nav-item .collapse .collapse-inner .collapse-header {
            color: rgba(255,255,255,0.4) !important;
            border-bottom: 1px solid rgba(255,255,255,0.08) !important;
        }

        /* ===== SIDEBAR OVERLAY FIX ===== */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            display: none;
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* ===== SIDEBAR COLLAPSE ON ALL SCREENS ===== */
        #sidebarToggleTop {
            display: inline-flex !important;
        }

        .sidebar.collapsed {
            width: 6.5rem !important;
        }

        .sidebar.collapsed .sidebar-brand-text {
            display: none !important;
        }

        .sidebar.collapsed .nav-link span {
            display: none !important;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0 !important;
            font-size: 1.3rem !important;
        }

        .sidebar.collapsed .sidebar-heading {
            text-align: center !important;
            font-size: 0.55rem !important;
        }

        .sidebar.collapsed .sidebar-card {
            display: none !important;
        }

        /* ===== FOOTER - FIXED AT BOTTOM ===== */
        html, body {
            height: 100%;
            margin: 0;
        }

        #wrapper {
            display: flex !important;
            align-items: flex-start !important;
            min-height: 100vh;
            height: 100%;
        }

        #content-wrapper {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 100vh;
            height: 100%;
        }

        #content {
            flex: 1 0 auto;
        }

        footer.sticky-footer {
            flex-shrink: 0;
            width: 100%;
            height: 36px;
            min-height: 36px;
            padding: 8px 0;
            background: #002a66;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: auto;
        }

        footer.sticky-footer .copyright {
            margin: 0;
            font-size: 12px;
            line-height: 1;
        }

        /* ===== SIDEBAR FIX - STICKY ===== */
        #sidebar-container {
            display: flex;
            flex-shrink: 0;
            height: 100vh;
            position: sticky;
            top: 0;
            align-self: flex-start;
            overflow: hidden;
        }

        .sidebar {
            position: relative !important;
            height: 100vh !important;
            min-height: 100vh !important;
            width: 6.5rem !important;
            flex-shrink: 0 !important;
            display: flex !important;
            flex-direction: column !important;
        }

        #wrapper {
            display: flex !important;
            align-items: flex-start !important;
        }

        #wrapper #content-wrapper {
            flex: 1 1 auto !important;
            width: auto !important;
            min-width: 0 !important;
            overflow-x: hidden !important;
        }

        @media (min-width: 768px) {
            .sidebar {
                width: 14rem !important;
            }
        }

        @media (max-width: 768px) {
            #sidebar-container {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                height: 100vh !important;
                z-index: 1050 !important;
                width: 0 !important;
                overflow: hidden !important;
                transition: width 0.3s ease !important;
                align-self: auto !important;
            }
            
            #sidebar-container.toggled {
                width: 14rem !important;
            }
            
            .sidebar {
                width: 100% !important;
                height: 100vh !important;
                position: relative !important;
            }
        }

        /* ===== ORIGINAL SIDEBAR DROPDOWN STYLES ===== */
        .sidebar .nav-item .collapse {
            position: absolute;
            left: calc(6.5rem + 1.5rem / 2);
            z-index: 1;
            top: 2px;
        }

        .sidebar .nav-item .collapse .collapse-inner {
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 0.5rem 0;
            min-width: 12rem;
            font-size: 0.85rem;
            background: #ffffff;
            border: 1px solid #e3e6f0;
        }

        @media (min-width: 768px) {
            .sidebar .nav-item .collapse {
                position: relative;
                left: 0;
                z-index: 1;
                top: 0;
                animation: none;
            }

            .sidebar .nav-item .collapse .collapse-inner {
                border-radius: 0;
                box-shadow: none;
                background: transparent !important;
                border: none;
                padding: 0.25rem 0;
                min-width: auto;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-header {
                color: rgba(255,255,255,0.4) !important;
                border-bottom: 1px solid rgba(255,255,255,0.08) !important;
                padding: 0.5rem 1rem;
                font-size: 0.6rem;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-item {
                color: rgba(255,255,255,0.7) !important;
                padding: 0.4rem 1rem;
                border-left: 3px solid transparent;
                font-weight: 400;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-item:hover {
                background: rgba(255,255,255,0.08) !important;
                color: #fff !important;
                border-left-color: #fff;
            }

            .sidebar .nav-item .collapse .collapse-inner .collapse-item.active {
                background: rgba(255,255,255,0.15) !important;
                color: #fff !important;
                border-left-color: #fff;
            }

            .sidebar .nav-item .collapsing {
                display: block;
                transition: height 0.15s ease;
            }

            .sidebar .nav-item .collapse,
            .sidebar .nav-item .collapsing {
                margin: 0 1rem;
            }

            .sidebar .nav-item .nav-link[data-toggle="collapse"]::after {
                width: 1rem;
                text-align: center;
                float: right;
                vertical-align: 0;
                border: 0;
                font-weight: 900;
                content: '\f107';
                font-family: 'Font Awesome 5 Free';
                margin-left: auto;
            }

            .sidebar .nav-item .nav-link[data-toggle="collapse"].collapsed::after {
                content: '\f105';
            }
        }

        @media (min-width: 768px) {
            .sidebar.toggled .nav-item .collapse {
                position: absolute;
                left: calc(6.5rem + 0.5rem);
                z-index: 1;
                top: 0;
                animation-name: growIn;
                animation-duration: 200ms;
                animation-timing-function: transform cubic-bezier(0.18, 1.25, 0.4, 1), opacity cubic-bezier(0, 1, 0.4, 1);
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner {
                box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
                border-radius: 0.35rem;
                background: #ffffff !important;
                border: 1px solid #e3e6f0;
                min-width: 12rem;
                padding: 0.5rem 0;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-header {
                color: #b7b9cc !important;
                border-bottom: 1px solid #e3e6f0 !important;
                padding: 0.5rem 1.5rem;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item {
                color: #3a3b45 !important;
                padding: 0.5rem 1.5rem;
                border-left: 3px solid transparent;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item:hover {
                background: #f0f4ff !important;
                color: #003986 !important;
                border-left-color: #003986;
            }

            .sidebar.toggled .nav-item .collapse .collapse-inner .collapse-item.active {
                background: #003986 !important;
                color: #ffffff !important;
                border-left-color: #ffffff;
            }

            .sidebar.toggled .nav-item .collapsing {
                display: none;
                transition: none;
            }

            .sidebar.toggled .nav-item .collapse,
            .sidebar.toggled .nav-item .collapsing {
                margin: 0;
            }

            .sidebar.toggled .nav-item .nav-link[data-toggle="collapse"]::after {
                display: none;
            }

            .sidebar.toggled .sidebar-brand .sidebar-brand-icon i {
                font-size: 2rem;
                display: block;
                margin-right: 0;
                margin-bottom: 4px;
            }

            .sidebar.toggled .sidebar-brand .sidebar-brand-text {
                display: none;
            }

            .sidebar.toggled .sidebar-heading {
                text-align: center;
            }

            .sidebar.toggled .sidebar-card {
                display: none;
            }
        }

        @keyframes growIn {
            0% { transform: scale(0.9); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .animated--grow-in {
            animation-name: growIn;
            animation-duration: 200ms;
            animation-timing-function: transform cubic-bezier(0.18, 1.25, 0.4, 1), opacity cubic-bezier(0, 1, 0.4, 1);
        }

        /* ===== CUSTOM REPORT STYLES ===== */
        .stat-card {
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.12) !important;
        }
        
        .stat-card .stat-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-card .stat-icon.primary { background: rgba(78, 115, 223, 0.12); color: #4e73df; }
        .stat-card .stat-icon.success { background: rgba(28, 200, 138, 0.12); color: #1cc88a; }
        .stat-card .stat-icon.info { background: rgba(54, 185, 204, 0.12); color: #36b9cc; }
        .stat-card .stat-icon.warning { background: rgba(246, 194, 62, 0.12); color: #f6c23e; }
        
        .stat-card .stat-number {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e3c72;
            line-height: 1.2;
        }
        
        .stat-card .stat-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #858796;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-card {
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            overflow: hidden;
        }
        
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.12) !important;
        }
        
        .report-card .report-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(78, 115, 223, 0.12);
            color: #4e73df;
        }
        
        .report-card .report-name {
            font-weight: 600;
            color: #1e3c72;
            font-size: 0.95rem;
        }
        
        .report-card .report-date {
            font-size: 0.75rem;
            color: #858796;
        }
        
        .report-card .report-size {
            font-size: 0.7rem;
            color: #858796;
        }

        .alert-message {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }
        .alert-danger {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        .alert-info {
            background: #dbeafe;
            border: 1px solid #bfdbfe;
            color: #2563eb;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }
        .empty-state i {
            font-size: 1rem;
            color: #d1d3e2;
            margin-bottom: 1rem;
        }
        .empty-state h5 {
            color: #1e3c72;
            font-weight: 700;
        }
        .empty-state p {
            color: #858796;
        }

        .btn:hover {
        color: #ffffff !important;
        text-decoration: none;
        }

        .btn-generate {
            background-color: #6484e4 !important;
            border-color: #4e73df;
            color: #ffffff !important;
        }

        .btn-generate:hover {
            background-color: #0330b8d3 !important;
            border-color: #4e73df;
            color: #ffffff !important;
        }

    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-container"></div>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>

                    <h1 class="h3 mb-0 text-gray-800">
                         Client Reports
                    </h1>

                    <ul class="navbar-nav ml-auto">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                                    <?php echo htmlspecialchars($first_name); ?>
                                    <span class="badge badge-primary ml-1"><?php echo htmlspecialchars($role); ?></span>
                                </span>
                                <img class="img-profile rounded-circle" src="../img/undraw_profile.svg">
                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div>
                            <a href="clients.php" class="btn btn-sm btn-secondary shadow-sm">
                                <i class="fas fa-arrow-left"></i> Back to Clients
                            </a>
                        </div>
                        <div>
                            
                                <a href="reprorts.php" class="btn btn-generate btn-sm btn-secondary shadow-sm">
                                <i class="fa-regular fa-pen-to-square"></i> Generate Report
                            </a>
                            
                        </div>
                    </div>

                    <!-- Alert Messages -->
                    <?php if (isset($_SESSION['report_message'])): ?>
                    <div class="alert-message alert-<?php echo $_SESSION['report_message_type'] ?? 'info'; ?>">
                        <i class="fas <?php echo $_SESSION['report_message_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                        <?php echo htmlspecialchars($_SESSION['report_message']); ?>
                    </div>
                    <?php 
                    unset($_SESSION['report_message']);
                    unset($_SESSION['report_message_type']);
                    endif; 
                    ?>

                    <!-- Stats Cards -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow stat-card border-left-primary">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon primary mr-3">
                                            <i class="fas fa-file"></i>
                                        </div>
                                        <div>
                                            <div class="stat-number"><?php echo count($report_files); ?></div>
                                            <div class="stat-label">Total Reports</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow stat-card border-left-success">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon success mr-3">
                                            <i class="fas fa-calendar-week"></i>
                                        </div>
                                        <div>
                                            <div class="stat-number"><?php 
                                                $week_count = 0;
                                                $week_ago = strtotime('-7 days');
                                                foreach ($report_files as $report) {
                                                    if ($report['created'] >= $week_ago) $week_count++;
                                                }
                                                echo $week_count;
                                            ?></div>
                                            <div class="stat-label">This Week</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow stat-card border-left-info">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon info mr-3">
                                            <i class="fas fa-calendar-month"></i>
                                        </div>
                                        <div>
                                            <div class="stat-number"><?php 
                                                $month_count = 0;
                                                $month_ago = strtotime('-30 days');
                                                foreach ($report_files as $report) {
                                                    if ($report['created'] >= $month_ago) $month_count++;
                                                }
                                                echo $month_count;
                                            ?></div>
                                            <div class="stat-label">This Month</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card shadow stat-card border-left-warning">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon warning mr-3">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div>
                                            <div class="stat-number"><?php 
                                                $latest = !empty($report_files) ? date('d/m/Y', $report_files[0]['created']) : 'N/A';
                                                echo $latest;
                                            ?></div>
                                            <div class="stat-label">Latest Report</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reports List -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        <i class="fas fa-list"></i> Generated Reports
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($report_files)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-file-export" style="font-size: 4rem;"></i>
                                        <h5>No Reports Found</h5>
                                        <p>Generate your first report by clicking the "Generate Report" button.</p>
                                        <a href="reports.php" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Generate Report
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Report Name</th>
                                                    <th>Generated On</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $count = 1; foreach ($report_files as $report): ?>
                                                <tr>
                                                    <td><?php echo $count++; ?></td>
                                                    <td>  
                                                        <?php echo htmlspecialchars($report['filename']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($report['date']); ?></td>
                                                    <td>
                                                        <div class="d-flex gap-2" style="gap: 0.3rem; flex-wrap: wrap;">
                                                            <a href="<?php echo htmlspecialchars($report['url']); ?>" target="_blank" class="btn btn-sm btn-primary" title="View">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="<?php echo htmlspecialchars($report['url']); ?>" download="<?php echo htmlspecialchars($report['filename']); ?>" class="btn btn-sm btn-success" title="Download">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <a href="?delete=<?php echo urlencode($report['filename']); ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this report?')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-blue">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Marlani Technologies <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>

    <script>
    // Load sidebar from external file - same as index.php
    $(function() {
        $("#sidebar-container").load("../sidebar.html", function() {
            console.log("Sidebar loaded successfully");
            
            // Set active state for current page
            $('#sidebar-container .nav-item').removeClass('active');
            $('#sidebar-container .nav-item a[href*="client-reports"]').closest('.nav-item').addClass('active');
            
            // Find and highlight the parent menu if in submenu
            $('#sidebar-container .nav-item .collapse .collapse-item').each(function() {
                if ($(this).attr('href') && $(this).attr('href').includes('client-reports')) {
                    $(this).addClass('active');
                    $(this).closest('.collapse').addClass('show');
                    $(this).closest('.nav-item').find('.nav-link').removeClass('collapsed');
                }
            });
            
            // Fix sidebar toggle for mobile - toggle the container
            $('#sidebarToggleTop').on('click', function(e) {
                e.preventDefault();
                $('#sidebar-container').toggleClass('toggled');
                // Add overlay for mobile
                if ($('#sidebar-container').hasClass('toggled')) {
                    if ($(window).width() <= 768) {
                        $('body').append('<div class="sidebar-overlay active"></div>');
                    }
                } else {
                    $('.sidebar-overlay').remove();
                }
            });
            
            // Close sidebar when clicking overlay
            $(document).on('click', '.sidebar-overlay', function() {
                $('#sidebar-container').removeClass('toggled');
                $('.sidebar-overlay').remove();
            });
            
            // Handle window resize
            $(window).resize(function() {
                if ($(window).width() > 768) {
                    $('#sidebar-container').removeClass('toggled');
                    $('.sidebar-overlay').remove();
                }
            });
        });
    });
    </script>
</body>
</html>